<?php

namespace App\Services\ContentView;

use App\Models\User;
use App\Models\ModuleContent;
use App\Models\LearningSession;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LearningSessionService
{
    protected ContentProgressService $progressService;

    public function __construct(ContentProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    /**
     * Start a new learning session
     * Ends any existing active sessions first
     */
  public function startSession(
    User $user,
    ModuleContent $content,
    float $position = 0,
    ?int $apiKeyId = null  // ✅ Accept the parameter
): LearningSession {
    // End existing sessions
    $existingSessions = LearningSession::where('user_id', $user->id)
        ->where('content_id', $content->id)
        ->whereNull('session_end')
        ->get();

    foreach ($existingSessions as $existingSession) {
        if ($existingSession->api_key_id) {
            app(\App\Services\GoogleDriveService::class)->releaseKey($existingSession->api_key_id);
        }
        $existingSession->session_end = now();
        $existingSession->save();
    }

    Log::info('🔍 Creating new session with key', [
        'user_id' => $user->id,
        'content_id' => $content->id,
        'api_key_id' => $apiKeyId,  // ✅ Log the parameter value
        'session_key_name' => "content_{$content->id}_key_id"
    ]);

    // Create new session
    $session = LearningSession::create([
        'user_id' => $user->id,
        'course_online_id' => $content->module->course_online_id,
        'content_id' => $content->id,
        'api_key_id' => $apiKeyId,  // ✅ Use the parameter!
        'session_start' => now(),
        'last_heartbeat' => now(),
        'current_position' => $position,
        'total_duration_minutes' => 0,
        'video_watch_time' => 0,
        'video_skip_count' => 0,
        'seek_count' => 0,
        'pause_count' => 0,
        'cheating_score' => 0,
        'attention_score' => 0,
    ]);

    Log::info('🎬 Learning session started', [
        'session_id' => $session->id,
        'user_id' => $user->id,
        'content_id' => $content->id,
        'api_key_id' => $session->api_key_id  // ✅ Verify it was saved
    ]);

    return $session;
}




    /**
     * Update session with heartbeat data (called every ~10 seconds)
     * Uses cumulative/incremental tracking
     */
    public function updateHeartbeat(
    int $sessionId,
    float $currentPosition,  // Keep parameter for API compatibility
    int $watchTimeIncrement = 0,
    int $skipCountIncrement = 0,
    int $seekCountIncrement = 0,
    int $pauseCountIncrement = 0
): LearningSession {
    $session = LearningSession::findOrFail($sessionId);

    if ($session->session_end) {
        throw new \Exception('Cannot update ended session');
    }

    // Calculate total duration
    $currentDuration = max(0, now()->diffInMinutes($session->session_start));

    // Accumulate incremental data
    $session->update([
        // ❌ REMOVED: current_position
        'total_duration_minutes' => $currentDuration,
        'video_watch_time' => ($session->video_watch_time ?? 0) + $watchTimeIncrement,
        'video_skip_count' => ($session->video_skip_count ?? 0) + $skipCountIncrement,
        'seek_count' => ($session->seek_count ?? 0) + $seekCountIncrement,
        'pause_count' => ($session->pause_count ?? 0) + $pauseCountIncrement,
    ]);

    Log::debug('💓 Session heartbeat updated', [
        'session_id' => $sessionId,
        'duration_minutes' => $currentDuration,
        'total_watch_time' => $session->video_watch_time,
        'total_skips' => $session->video_skip_count,
    ]);

    return $session->fresh();
}


    /**
     * End session and calculate final scores
     */
    public function endSession(
    int $sessionId,
    float $finalPosition,  // Keep parameter for API compatibility
    float $completionPercentage,
    int $finalWatchTimeIncrement = 0,
    int $finalSkipIncrement = 0,
    int $finalSeekIncrement = 0,
    int $finalPauseIncrement = 0
): LearningSession {
    $session = LearningSession::findOrFail($sessionId);
    $content = $session->content;

    if ($session->session_end) {
        Log::warning('⚠️ Attempted to end already-ended session', [
            'session_id' => $sessionId,
        ]);
        return $session;
    }

    // ✅ IMPORTANT: Release the API key BEFORE ending the session
    if ($session->api_key_id) {
        Log::info('🔓 Releasing API key from session service', [
            'session_id' => $sessionId,
            'api_key_id' => $session->api_key_id,
            'user_id' => $session->user_id,
            'content_id' => $session->content_id
        ]);
        
        try {
            // Get the GoogleDriveService and release the key
            $driveService = app(\App\Services\GoogleDriveService::class);
            $driveService->releaseKey($session->api_key_id);
            
            Log::info('✅ API key successfully released', [
                'session_id' => $sessionId,
                'api_key_id' => $session->api_key_id
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Failed to release API key', [
                'session_id' => $sessionId,
                'api_key_id' => $session->api_key_id,
                'error' => $e->getMessage()
            ]);
        }
    } else {
        Log::warning('⚠️ Session has no api_key_id to release', [
            'session_id' => $sessionId,
            'user_id' => $session->user_id,
            'content_id' => $session->content_id
        ]);
    }

    // Calculate total duration
    $totalDuration = max(0, now()->diffInMinutes($session->session_start));

    // Add final increments to cumulative totals
    $totalWatchTime = ($session->video_watch_time ?? 0) + $finalWatchTimeIncrement;
    $totalSkips = ($session->video_skip_count ?? 0) + $finalSkipIncrement;
    $totalSeeks = ($session->seek_count ?? 0) + $finalSeekIncrement;
    $totalPauses = ($session->pause_count ?? 0) + $finalPauseIncrement;

    // Calculate scores
    $attentionScore = $this->calculateAttentionScore(
        $totalDuration,
        $content,
        $completionPercentage
    );

    $cheatingScore = $this->calculateCheatingScore(
        $totalDuration,
        $content,
        $totalSkips,
        $completionPercentage
    );

    $isSuspicious = $this->detectSuspiciousBehavior(
        $totalDuration,
        $content,
        $totalSkips,
        $completionPercentage
    );

    // Update session with final data
    $session->update([
        'session_end' => now(),
        // ❌ REMOVED: current_position
        'total_duration_minutes' => $totalDuration,
        'video_watch_time' => $totalWatchTime,
        'video_skip_count' => $totalSkips,
        'seek_count' => $totalSeeks,
        'pause_count' => $totalPauses,
        'video_completion_percentage' => $completionPercentage,
        'attention_score' => $attentionScore,
        'cheating_score' => $cheatingScore,
        'is_suspicious_activity' => $isSuspicious,
    ]);

    Log::info('🏁 Learning session ended', [
        'session_id' => $sessionId,
        'duration_minutes' => $totalDuration,
        'watch_time' => $totalWatchTime,
        'completion' => $completionPercentage,
        'skips' => $totalSkips,
        'attention_score' => $attentionScore,
        'cheating_score' => $cheatingScore,
        'is_suspicious' => $isSuspicious,
    ]);

    return $session->fresh();
}



    /**
     * Calculate attention score (0-100)
     * Higher = more focused/engaged
     */
    public function calculateAttentionScore(
        int $durationMinutes,
        ModuleContent $content,
        float $completionPercentage
    ): int {
        $score = 50; // Base score

        // Get expected duration
        $expectedDuration = $this->getExpectedDuration($content);

        if ($expectedDuration > 0) {
            $timeRatio = $durationMinutes / $expectedDuration;

            // Time efficiency scoring
            if ($timeRatio >= 0.8 && $timeRatio <= 1.5) {
                $score += 25; // Good pace
            } elseif ($timeRatio < 0.3) {
                $score -= 30; // Too fast (suspicious)
            }
        }

        // Completion scoring
        if ($completionPercentage >= 90) {
            $score += 20;
        } elseif ($completionPercentage < 20) {
            $score -= 25;
        }

        return max(0, min(100, $score));
    }

    /**
     * Calculate cheating score (0-100)
     * Higher = more likely cheating
     */
    public function calculateCheatingScore(
        int $durationMinutes,
        ModuleContent $content,
        int $skipCount,
        float $completionPercentage
    ): int {
        $score = 0;

        // Duration analysis
        if ($durationMinutes < 2 && $durationMinutes > 0) {
            $score += 60; // Extremely short
        } elseif ($durationMinutes < 5) {
            $score += 30; // Very short
        }

        // Skip analysis
        if ($skipCount > 15) {
            $score += 40; // Excessive skipping
        } elseif ($skipCount > 8) {
            $score += 20; // High skipping
        }

        // Time vs completion analysis
        $expectedDuration = $this->getExpectedDuration($content);

        if ($expectedDuration > 0 && $durationMinutes > 0) {
            $efficiency = $durationMinutes / $expectedDuration;

            // Impossibly fast completion
            if ($efficiency < 0.2 && $completionPercentage > 70) {
                $score += 50;
            }
        }

        return max(0, min(100, $score));
    }

    /**
     * Detect suspicious behavior patterns
     */
    public function detectSuspiciousBehavior(
        int $durationMinutes,
        ModuleContent $content,
        int $skipCount,
        float $completionPercentage
    ): bool {
        // Very short session with high completion
        if ($durationMinutes < 2 && $completionPercentage > 50) {
            return true;
        }

        // Excessive skipping
        if ($skipCount > 20) {
            return true;
        }

        // Impossibly fast completion
        $expectedDuration = $this->getExpectedDuration($content);

        if ($expectedDuration > 0 && $durationMinutes > 0) {
            $efficiency = $durationMinutes / $expectedDuration;

            // Completed 80%+ in less than 15% of expected time
            if ($efficiency < 0.15 && $completionPercentage > 80) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get expected duration for content in minutes
     */
    protected function getExpectedDuration(ModuleContent $content): int
    {
        if ($content->content_type === 'pdf' && $content->pdf_page_count) {
            return $content->pdf_page_count * 2; // 2 minutes per page
        }

        if ($content->content_type === 'video' && $content->video) {
            return (int) ceil($content->video->duration / 60); // Convert seconds to minutes
        }

        return 0;
    }

    /**
     * End existing active sessions for user/content
     */
    protected function endExistingSessions(int $userId, int $contentId): void
    {
        $existingSessions = LearningSession::where('user_id', $userId)
            ->where('content_id', $contentId)
            ->whereNull('session_end')
            ->get();

        foreach ($existingSessions as $session) {
            $realDuration = max(0, now()->diffInMinutes($session->session_start));

            $session->update([
                'session_end' => now(),
                'total_duration_minutes' => $realDuration,
            ]);

            Log::info('🔒 Ended existing session', [
                'session_id' => $session->id,
                'duration_minutes' => $realDuration,
            ]);
        }
    }

    /**
     * Get active session for user/content
     */
    public function getActiveSession(int $userId, int $contentId): ?LearningSession
    {
        return LearningSession::where('user_id', $userId)
            ->where('content_id', $contentId)
            ->whereNull('session_end')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Cleanup abandoned sessions (older than 2 hours with no end)
     */
    public function cleanupAbandonedSessions(int $userId): int
    {
        $cutoff = now()->subHours(2);

        $abandoned = LearningSession::where('user_id', $userId)
            ->whereNull('session_end')
            ->where('session_start', '<', $cutoff)
            ->get();

        foreach ($abandoned as $session) {
            $duration = max(0, $session->session_start->diffInMinutes($cutoff));

            $session->update([
                'session_end' => $session->session_start->addMinutes($duration),
                'total_duration_minutes' => $duration,
            ]);
        }

        Log::info('🧹 Cleaned up abandoned sessions', [
            'user_id' => $userId,
            'count' => $abandoned->count(),
        ]);

        return $abandoned->count();
    }
}
