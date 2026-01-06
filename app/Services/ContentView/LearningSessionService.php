<?php

namespace App\Services\ContentView;

use App\Models\User;
use App\Models\ModuleContent;
use App\Models\LearningSession;
use App\Models\CourseModule;
use Carbon\Carbon;

class LearningSessionService
{
    protected ContentProgressService $progressService;

    public function __construct(ContentProgressService $progressService)
    {
        $this->progressService = $progressService;
    }
    
    /**
     * Get the module for content, safely handling lazy loading
     */
    private function getContentModule(ModuleContent $content): CourseModule
    {
        return $content->relationLoaded('module') ? $content->module : $content->load('module')->module;
    }

    /**
     * Start a new learning session
     * Ends any existing active sessions first
     */
  public function startSession(
    User $user,
    ModuleContent $content,
    float $position = 0,
    ?int $apiKeyId = null
): LearningSession {
    // Get module safely to avoid lazy loading
    $module = $this->getContentModule($content);
    
    // ✅ NEW: Check if course is already completed
    $assignment = \App\Models\CourseOnlineAssignment::where('user_id', $user->id)
        ->where('course_online_id', $module->course_online_id)
        ->first();
    
    if ($assignment && $assignment->status === 'completed') {
        // Course is already completed - don't allow new sessions
        throw new \Exception('Cannot start session: Course is already completed');
    }
    
    // End existing sessions
    // End existing sessions (Bulk Update)
    LearningSession::where('user_id', $user->id)
        ->where('content_id', $content->id)
        ->whereNull('session_end')
        ->update(['session_end' => now()]);

    // Release keys if necessary (this still needs a loop if keys are involved, but we can optimize)
    // Note: If keys need to be released individually via an external service, we might still need to fetch them.
    // However, for the session end timestamp, the bulk update above is sufficient for the DB.
    // If you strictly need to release keys, we should fetch only those with keys.
    
    $sessionsWithKeys = LearningSession::where('user_id', $user->id)
        ->where('content_id', $content->id)
        ->whereNotNull('api_key_id')
        ->where('session_end', now()) // We just updated them
        ->get();

    foreach ($sessionsWithKeys as $sessionWithKey) {
         try {
            app(\App\Services\GoogleDriveService::class)->releaseKey($sessionWithKey->api_key_id);
         } catch (\Exception $e) {
             // Ignore errors during cleanup
         }
    }

    // ✅ NEW: INCREMENT active_users when play button is pressed
    if ($apiKeyId) {
        try {
            $driveKeyManager = app(\App\Services\DriveKeyManager::class);
            $driveKeyManager->incrementActiveUsers($apiKeyId); // ✅ NEW METHOD


        } catch (\Exception $e) {

        }
    }

    // Create new session
    $session = LearningSession::create([
        'user_id' => $user->id,
        'course_online_id' => $module->course_online_id,
        'content_id' => $content->id,
        'api_key_id' => $apiKeyId,
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



    return $session;
}





    /**
     * Update session with active playback time
     * 
     * @param int $sessionId
     * @param int $activePlaybackSeconds Active playback time in seconds
     * @param array $videoEvents Array of video events (pause, resume, rewind)
     * @return LearningSession
     */
    public function updateActivePlaybackTime(
        int $sessionId,
        int $activePlaybackSeconds,
        array $videoEvents = []
    ): LearningSession {
        $session = LearningSession::findOrFail($sessionId);

        if ($session->session_end) {
            throw new \Exception('Cannot update ended session');
        }

        $session->update([
            'active_playback_time' => $activePlaybackSeconds,
            'video_events' => $videoEvents,
        ]);

        return $session->fresh();
    }

    /**
     * Check if session is within allowed time window
     * Allowed time = Video Duration × 2
     * 
     * @param LearningSession $session
     * @return bool
     */
    public function isWithinAllowedTime(LearningSession $session): bool
    {
        $content = $session->content;
        $videoDurationMinutes = $this->getExpectedDuration($content);
        
        // Calculate allowed time as Duration × 2
        $allowedTimeMinutes = $videoDurationMinutes * 2;
        
        // Convert active playback time from seconds to minutes
        $activePlaybackMinutes = ($session->active_playback_time ?? 0) / 60;
        
        return $activePlaybackMinutes <= $allowedTimeMinutes;
    }

    /**
     * Update session with heartbeat data (called every ~30 seconds)
     * Uses cumulative/incremental tracking
     */
    public function updateHeartbeat(
    int $sessionId,
    float $currentPosition,  // Keep parameter for API compatibility
    int $watchTimeIncrement = 0,
    int $skipCountIncrement = 0,
    int $seekCountIncrement = 0,
    int $pauseCountIncrement = 0,
    float $completionPercentage = 0  // ✅ NEW: Track completion percentage in heartbeat
): LearningSession {
    $session = LearningSession::findOrFail($sessionId);

    if ($session->session_end) {
        throw new \Exception('Cannot update ended session');
    }

    // ✅ FIXED: Calculate total duration in seconds, then convert to minutes (rounded up)
    $currentDurationSeconds = max(0, now()->diffInSeconds($session->session_start));
    $currentDuration = (int) ceil($currentDurationSeconds / 60);

    // Accumulate incremental data
    $session->update([
        // ❌ REMOVED: current_position
        'last_heartbeat' => now(),  // ✅ FIXED: Update last_heartbeat timestamp
        'total_duration_minutes' => $currentDuration,
        'video_watch_time' => ($session->video_watch_time ?? 0) + $watchTimeIncrement,
        'video_skip_count' => ($session->video_skip_count ?? 0) + $skipCountIncrement,
        'seek_count' => ($session->seek_count ?? 0) + $seekCountIncrement,
        'pause_count' => ($session->pause_count ?? 0) + $pauseCountIncrement,
        'video_completion_percentage' => $completionPercentage,  // ✅ NEW: Save completion percentage
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

        return $session;
    }

    // ✅ IMPORTANT: Release the API key BEFORE ending the session
    if ($session->api_key_id) {


        try {
            // Get the GoogleDriveService and release the key
            $driveService = app(\App\Services\GoogleDriveService::class);
            $driveService->releaseKey($session->api_key_id);


        } catch (\Exception $e) {

        }
    } else {

    }

    // ✅ FIXED: Calculate total duration in seconds, then convert to minutes (rounded up)
    $totalDurationSeconds = max(0, now()->diffInSeconds($session->session_start));
    $totalDuration = (int) ceil($totalDurationSeconds / 60);

    // Add final increments to cumulative totals
    $totalWatchTime = ($session->video_watch_time ?? 0) + $finalWatchTimeIncrement;
    $totalSkips = ($session->video_skip_count ?? 0) + $finalSkipIncrement;
    $totalSeeks = ($session->seek_count ?? 0) + $finalSeekIncrement;
    $totalPauses = ($session->pause_count ?? 0) + $finalPauseIncrement;

    // Calculate if within allowed time (Duration × 2)
    $isWithinAllowedTime = $this->isWithinAllowedTime($session);

    // Calculate scores using active playback time if available
    $durationForScore = $totalDuration;
    if ($session->active_playback_time) {
        // Use active playback time (convert from seconds to minutes)
        $durationForScore = (int) ceil($session->active_playback_time / 60);
    }

    $attentionScore = $this->calculateAttentionScoreWithActiveTime(
        $durationForScore,
        $content,
        $completionPercentage,
        $isWithinAllowedTime
    );

    $cheatingScore = $this->calculateCheatingScore(
        $durationForScore,
        $content,
        $totalSkips,
        $completionPercentage
    );

    $isSuspicious = $this->detectSuspiciousBehavior(
        $durationForScore,
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
        'is_within_allowed_time' => $isWithinAllowedTime,
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
     * Calculate attention score using active playback time
     * No penalties for pauses/rewinds within allowed time (Duration × 2)
     * 
     * @param int $activePlaybackMinutes Active playback time in minutes
     * @param ModuleContent $content
     * @param float $completionPercentage
     * @param bool $isWithinAllowedTime Whether session is within allowed time window
     * @return int Score from 0-100
     */
    public function calculateAttentionScoreWithActiveTime(
        int $activePlaybackMinutes,
        ModuleContent $content,
        float $completionPercentage,
        bool $isWithinAllowedTime
    ): int {
        $score = 50; // Base score

        // Get expected duration
        $expectedDuration = $this->getExpectedDuration($content);

        if ($expectedDuration > 0) {
            $timeRatio = $activePlaybackMinutes / $expectedDuration;

            // Within allowed time window - no "too long" penalty
            if ($isWithinAllowedTime) {
                // Good active playback time
                if ($timeRatio >= 0.8 && $timeRatio <= 2.0) {
                    $score += 25; // Good pace, within allowed window
                } elseif ($timeRatio >= 0.5) {
                    $score += 15; // Acceptable pace
                } elseif ($timeRatio < 0.3) {
                    $score -= 30; // Too fast (suspicious)
                }
            } else {
                // Exceeded allowed time window - apply penalty
                $score -= 20; // Penalty for exceeding allowed time
            }
        }

        // Completion scoring
        if ($completionPercentage >= 90) {
            $score += 20;
        } elseif ($completionPercentage >= 70) {
            $score += 10;
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
            // ✅ FIXED: Calculate duration in seconds, then convert to minutes (rounded up)
            $realDurationSeconds = max(0, now()->diffInSeconds($session->session_start));
            $realDuration = (int) ceil($realDurationSeconds / 60);

            $session->update([
                'session_end' => now(),
                'total_duration_minutes' => $realDuration,
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
            // ✅ FIXED: Calculate duration in seconds, then convert to minutes (rounded up)
            $durationSeconds = max(0, $session->session_start->diffInSeconds($cutoff));
            $duration = (int) ceil($durationSeconds / 60);

            $session->update([
                'session_end' => $session->session_start->addMinutes($duration),
                'total_duration_minutes' => $duration,
            ]);
        }



        return $abandoned->count();
    }
}
