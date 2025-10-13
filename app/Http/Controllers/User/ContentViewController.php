<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ModuleContent;
use App\Models\UserContentProgress;
use App\Models\CourseOnlineAssignment;
use App\Models\LearningSession;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class ContentViewController extends Controller
{
    protected $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;

        Log::info('🏗️ ContentViewController constructed', [
            'timestamp' => now(),
            'google_drive_service' => class_basename($googleDriveService)
        ]);
    }

    /**
     * ✅ ENHANCED: Display content viewer with PDF page count and Google Drive support
     */
    public function show(ModuleContent $content)
    {
        $user = auth()->user();

        Log::info('🔍 === CONTENT VIEWER SHOW START ===', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'content_id' => $content->id,
            'content_title' => $content->title,
            'content_type' => $content->content_type,
            'module_id' => $content->module_id,
            'course_id' => $content->module->course_online_id,
            'pdf_page_count' => $content->pdf_page_count,
            'has_pdf_page_count' => !is_null($content->pdf_page_count),
        ]);

        // Check if user has access to this content
        $assignment = CourseOnlineAssignment::where('course_online_id', $content->module->course_online_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$assignment) {
            Log::error('❌ User access denied to content', [
                'user_id' => $user->id,
                'content_id' => $content->id,
                'course_id' => $content->module->course_online_id,
                'reason' => 'No assignment found'
            ]);

            abort(403, 'Access denied to this content');
        }

        Log::info('✅ User access verified', [
            'user_id' => $user->id,
            'content_id' => $content->id,
            'assignment_id' => $assignment->id,
            'assignment_status' => $assignment->status
        ]);

        // Load content relationships
        $content->load(['module.courseOnline', 'video']);

        // Get or create user progress
        $progress = UserContentProgress::firstOrCreate([
            'user_id' => $user->id,
            'content_id' => $content->id,
        ], [
            'course_online_id' => $content->module->course_online_id,
            'module_id' => $content->module_id,
            'video_id' => $content->video_id,
            'content_type' => $content->content_type,
            'watch_time' => 0,
            'completion_percentage' => 0,
            'is_completed' => false,
            'task_completed' => false,
        ]);

        // ✅ ENHANCED: Get PDF file URL with Google Drive support
        $fileUrl = null;
        $pdfSourceType = 'unknown';

        if ($content->content_type === 'pdf') {
            if ($content->file_path) {
                $fileUrl = asset('storage/' . $content->file_path);
                $pdfSourceType = 'local_storage';
            } elseif ($content->google_drive_pdf_url) {
                $fileUrl = $this->processGoogleDrivePdfUrl($content->google_drive_pdf_url);
                $pdfSourceType = 'google_drive';
            }

            Log::info('📄 PDF Details Enhanced', [
                'content_id' => $content->id,
                'pdf_name' => $content->pdf_name,
                'file_url' => $fileUrl,
                'original_google_url' => $content->google_drive_pdf_url,
                'pdf_page_count' => $content->pdf_page_count,
                'has_page_count' => !is_null($content->pdf_page_count),
                'source_type' => $pdfSourceType,
                'file_path' => $content->file_path,
            ]);
        }

        // Get video streaming URL
        $streamingUrl = null;
        if ($content->content_type === 'video' && $content->video) {
            $streamingUrl = $this->googleDriveService->processUrl($content->video->google_drive_url);
        }

        // Get navigation
        $navigation = $this->getContentNavigation($content, $user->id);

        // ✅ ENHANCED: Response data with comprehensive PDF support
        $responseData = [
            'content' => [
                'id' => $content->id,
                'title' => $content->title,
                'description' => $content->description,
                'content_type' => $content->content_type,
                'duration' => $content->duration,
                'is_required' => $content->is_required,
                'video' => $content->video ? [
                    'id' => $content->video->id,
                    'name' => $content->video->name,
                    'duration' => $content->video->duration,
                    'thumbnail_url' => null,
                    'streaming_url' => $streamingUrl,
                ] : null,
                'pdf_name' => $content->pdf_name ?? ($content->title . '.pdf'),
                'file_url' => $fileUrl,
                // ✅ ENHANCED: PDF page count and metadata
                'pdf_page_count' => $content->pdf_page_count,
                'has_pdf_page_count' => !is_null($content->pdf_page_count),
                'estimated_reading_time' => $content->pdf_page_count ? ($content->pdf_page_count * 2) : null,
                'pdf_source_type' => $pdfSourceType,
                // ✅ NEW: Google Drive specific data
                'google_drive_pdf_url' => $content->google_drive_pdf_url,
                'file_path' => $content->file_path,
            ],
            'module' => [
                'id' => $content->module->id,
                'name' => $content->module->name,
                'order_number' => $content->module->order_number,
            ],
            'course' => [
                'id' => $content->module->courseOnline->id,
                'name' => $content->module->courseOnline->name,
            ],
            'userProgress' => [
                'id' => $progress->id,
                'current_position' => $progress->playback_position ?? 0,
                'completion_percentage' => $progress->completion_percentage,
                'is_completed' => $progress->is_completed,
                'time_spent' => $progress->watch_time ?? 0,
            ],
            'navigation' => $navigation,
        ];

        Log::info('📤 Sending enhanced response to frontend', [
            'content_id' => $content->id,
            'content_type' => $content->content_type,
            'pdf_page_count' => $responseData['content']['pdf_page_count'],
            'has_pdf_page_count' => $responseData['content']['has_pdf_page_count'],
            'estimated_reading_time' => $responseData['content']['estimated_reading_time'],
            'pdf_source_type' => $responseData['content']['pdf_source_type'],
            'google_drive_url' => $responseData['content']['google_drive_pdf_url'] ? 'present' : 'null',
        ]);

        return Inertia::render('User/ContentViewer/Show', $responseData);
    }

    /**
     * ✅ ENHANCED: Process Google Drive PDF URL for embed viewing
     */
    private function processGoogleDrivePdfUrl($url)
    {
        if (empty($url)) {
            return null;
        }

        // Extract file ID from various Google Drive URL formats
        $patterns = [
            '/\/file\/d\/([a-zA-Z0-9-_]+)/',
            '/id=([a-zA-Z0-9-_]+)/',
            '/\/open\?id=([a-zA-Z0-9-_]+)/',
            '/\/view\?id=([a-zA-Z0-9-_]+)/',
        ];

        $fileId = null;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                $fileId = $matches[1];
                break;
            }
        }

        if (!$fileId) {
            Log::warning('Could not extract file ID from Google Drive URL', ['url' => $url]);
            return $url;
        }

        // ✅ ENHANCED: Return embed URL for better compatibility
        $embedUrl = "https://drive.google.com/file/d/{$fileId}/preview";

        Log::info('Google Drive PDF URL processed', [
            'original' => $url,
            'file_id' => $fileId,
            'embed_url' => $embedUrl,
        ]);

        return $embedUrl;
    }

    /**
     * ✅ NEW: Get PDF page count for AJAX calls
     */
    public function getPdfPageCount(ModuleContent $content)
    {
        try {
            $user = auth()->user();

            // Check access
            $assignment = CourseOnlineAssignment::where('course_online_id', $content->module->course_online_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            // Return page count from database
            return response()->json([
                'success' => true,
                'pdf_page_count' => $content->pdf_page_count,
                'has_page_count' => !is_null($content->pdf_page_count),
                'estimated_reading_time' => $content->pdf_page_count ? ($content->pdf_page_count * 2) : null,
                'source' => 'database',
                'content_id' => $content->id,
                'content_title' => $content->title,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to get PDF page count', [
                'content_id' => $content->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get page count'
            ], 500);
        }
    }

    /**
     * ✅ ENHANCED: Session management with PDF support
     */
    /**
     * ✅ FIXED: Enhanced session management with REAL data collection
     */
    /**
     * ✅ FIXED: Enhanced session management with CUMULATIVE data collection
     */
    public function manageSession(Request $request, ModuleContent $content)
    {
        $user = auth()->user();
        $assignment = CourseOnlineAssignment::where('course_online_id', $content->module->course_online_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Access denied'], 403);
        }

        $action = $request->input('action');
        $currentPosition = $request->input('current_position', 0);

        // ✅ NEW: Get INCREMENTAL tracking data from frontend
        $skipCountIncrement = $request->input('skip_count', 0);  // New skips since last heartbeat
        $seekCountIncrement = $request->input('seek_count', 0);  // New seeks since last heartbeat
        $pauseCountIncrement = $request->input('pause_count', 0); // New pauses since last heartbeat
        $watchTimeIncrement = $request->input('watch_time', 0);   // New watch time since last heartbeat

        Log::info('📹 FIXED session management with CUMULATIVE data', [
            'user_id' => $user->id,
            'content_id' => $content->id,
            'action' => $action,
            'position' => $currentPosition,
            'new_skip_count' => $skipCountIncrement,
            'new_seek_count' => $seekCountIncrement,
            'new_pause_count' => $pauseCountIncrement,
            'new_watch_time' => $watchTimeIncrement,
            'content_type' => $content->content_type,
        ]);

        try {
            switch ($action) {
                case 'start':
                    // ✅ FIXED: End existing sessions properly
                    $existingSessions = LearningSession::where('user_id', $user->id)
                        ->where('content_id', $content->id)
                        ->whereNull('session_end')
                        ->get();

                    foreach ($existingSessions as $existingSession) {
                        $realDuration = max(0, now()->diffInMinutes($existingSession->session_start));

                        $existingSession->update([
                            'session_end' => now(),
                            'total_duration_minutes' => $realDuration,
                        ]);

                        Log::info('🔄 Ended existing session', [
                            'session_id' => $existingSession->id,
                            'real_duration_minutes' => $realDuration,
                        ]);
                    }

                    // Create new session with zero counters
                    $session = LearningSession::create([
                        'user_id' => $user->id,
                        'course_online_id' => $content->module->course_online_id,
                        'content_id' => $content->id,
                        'session_start' => now(),
                        'current_position' => $currentPosition,
                        'total_duration_minutes' => 0,
                        'video_watch_time' => 0,
                        'video_skip_count' => 0,
                        'seek_count' => 0,
                        'pause_count' => 0,
                    ]);

                    return response()->json([
                        'success' => true,
                        'session_id' => $session->id,
                        'message' => 'Session started with cumulative tracking'
                    ]);

                case 'heartbeat':
                    $session = LearningSession::where('user_id', $user->id)
                        ->where('content_id', $content->id)
                        ->whereNull('session_end')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($session) {
                        $currentDuration = max(0, now()->diffInMinutes($session->session_start));

                        // ✅ FIXED: ACCUMULATE data, don't overwrite
                        $session->update([
                            'current_position' => $currentPosition,
                            'last_heartbeat' => now(),
                            'total_duration_minutes' => $currentDuration,

                            // ✅ ACCUMULATE: Add increments to existing values
                            'video_watch_time' => ($session->video_watch_time ?? 0) + $watchTimeIncrement,
                            'video_skip_count' => ($session->video_skip_count ?? 0) + $skipCountIncrement,
                            'seek_count' => ($session->seek_count ?? 0) + $seekCountIncrement,
                            'pause_count' => ($session->pause_count ?? 0) + $pauseCountIncrement,
                        ]);

                        Log::info('💓 FIXED session heartbeat with CUMULATIVE data', [
                            'session_id' => $session->id,
                            'duration_minutes' => $currentDuration,
                            'total_watch_time' => $session->video_watch_time,
                            'total_skip_count' => $session->video_skip_count,
                            'total_seek_count' => $session->seek_count,
                            'position' => $currentPosition,
                        ]);

                        return response()->json([
                            'success' => true,
                            'duration_minutes' => $currentDuration,
                            'total_watch_time' => $session->video_watch_time,
                            'total_skip_count' => $session->video_skip_count,
                        ]);
                    }

                    return response()->json(['success' => false, 'message' => 'No active session'], 404);

                case 'end':
                    $session = LearningSession::where('user_id', $user->id)
                        ->where('content_id', $content->id)
                        ->whereNull('session_end')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($session) {
                        $totalDuration = max(0, now()->diffInMinutes($session->session_start));

                        // ✅ FIXED: Calculate completion percentage
                        $completionPercentage = 0;
                        if ($content->content_type === 'pdf' && $content->pdf_page_count) {
                            $completionPercentage = ($currentPosition / $content->pdf_page_count) * 100;
                        } elseif ($content->content_type === 'video' && $content->video) {
                            $completionPercentage = ($currentPosition / $content->video->duration) * 100;
                        }

                        // ✅ FIXED: Add final increments to cumulative totals
                        $session->update([
                            'session_end' => now(),
                            'current_position' => $currentPosition,
                            'total_duration_minutes' => $totalDuration,

                            // ✅ ADD final increments to existing totals
                            'video_watch_time' => ($session->video_watch_time ?? 0) + $watchTimeIncrement,
                            'video_skip_count' => ($session->video_skip_count ?? 0) + $skipCountIncrement,
                            'seek_count' => ($session->seek_count ?? 0) + $seekCountIncrement,
                            'pause_count' => ($session->pause_count ?? 0) + $pauseCountIncrement,

                            'video_completion_percentage' => min(100, max(0, $completionPercentage)),

                            // ✅ Calculate final scores
                            'attention_score' => $this->calculateAttentionScore($totalDuration, $content, $completionPercentage),
                            'cheating_score' => $this->calculateCheatingScore($totalDuration, $content, $session->video_skip_count + $skipCountIncrement, $completionPercentage),
                            'is_suspicious_activity' => $this->isSuspiciousSession($totalDuration, $content, $session->video_skip_count + $skipCountIncrement, $completionPercentage),
                        ]);

                        // ✅ DEBUGGING: Log final session data
                        Log::info('✅ FIXED session ended with COMPLETE CUMULATIVE DATA', [
                            'session_id' => $session->id,
                            'total_duration_minutes' => $totalDuration,
                            'final_watch_time' => $session->video_watch_time,
                            'final_completion_percentage' => $completionPercentage,
                            'final_skip_count' => $session->video_skip_count,
                            'final_seek_count' => $session->seek_count,
                            'final_pause_count' => $session->pause_count,
                            'attention_score' => $session->attention_score,
                            'cheating_score' => $session->cheating_score,
                            'is_suspicious' => $session->is_suspicious_activity,
                        ]);

                        return response()->json([
                            'success' => true,
                            'total_duration_minutes' => $totalDuration,
                            'total_watch_time' => $session->video_watch_time,
                            'total_skip_count' => $session->video_skip_count,
                            'completion_percentage' => $completionPercentage,
                            'attention_score' => $session->attention_score,
                            'cheating_score' => $session->cheating_score,
                        ]);
                    }

                    return response()->json(['success' => false, 'message' => 'No active session'], 404);
            }

        } catch (\Exception $e) {
            Log::error('❌ FIXED session management error', [
                'user_id' => $user->id,
                'content_id' => $content->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NEW: Calculate attention score based on real data
     */
    private function calculateAttentionScore($duration, $content, $completion): int
    {
        $score = 50; // Base score

        // Get expected duration
        $expectedDuration = 0;
        if ($content->content_type === 'pdf' && $content->pdf_page_count) {
            $expectedDuration = $content->pdf_page_count * 2; // 2 minutes per page
        } elseif ($content->content_type === 'video' && $content->video) {
            $expectedDuration = $content->video->duration / 60; // Convert to minutes
        }

        // Time efficiency scoring
        if ($expectedDuration > 0) {
            $timeRatio = $duration / $expectedDuration;
            if ($timeRatio >= 0.8 && $timeRatio <= 1.5) {
                $score += 25; // Good pace
            } elseif ($timeRatio < 0.3) {
                $score -= 30; // Too fast
            }
        }

        // Completion scoring
        if ($completion >= 90) {
            $score += 20;
        } elseif ($completion < 20) {
            $score -= 25;
        }

        return max(0, min(100, $score));
    }

    /**
     * ✅ NEW: Calculate cheating score based on real data
     */
    private function calculateCheatingScore($duration, $content, $skipCount, $completion): int
    {
        $score = 0;

        // Duration analysis
        if ($duration < 2 && $duration > 0) {
            $score += 60; // Extremely short
        } elseif ($duration < 5) {
            $score += 30; // Very short
        }

        // Skip analysis
        if ($skipCount > 15) {
            $score += 40; // Excessive skipping
        } elseif ($skipCount > 8) {
            $score += 20; // High skipping
        }

        // Time vs completion analysis
        $expectedDuration = 0;
        if ($content->content_type === 'pdf' && $content->pdf_page_count) {
            $expectedDuration = $content->pdf_page_count * 2;
        } elseif ($content->content_type === 'video' && $content->video) {
            $expectedDuration = $content->video->duration / 60;
        }

        if ($expectedDuration > 0 && $duration > 0) {
            $efficiency = $duration / $expectedDuration;
            if ($efficiency < 0.2 && $completion > 70) {
                $score += 50; // Impossibly fast completion
            }
        }

        return max(0, min(100, $score));
    }

    /**
     * ✅ NEW: Determine if session is suspicious
     */
    private function isSuspiciousSession($duration, $content, $skipCount, $completion): bool
    {
        // Very short session with high completion
        if ($duration < 2 && $completion > 50) {
            return true;
        }

        // Excessive skipping
        if ($skipCount > 20) {
            return true;
        }

        // Impossibly fast completion
        $expectedDuration = 0;
        if ($content->content_type === 'pdf' && $content->pdf_page_count) {
            $expectedDuration = $content->pdf_page_count * 2;
        } elseif ($content->content_type === 'video' && $content->video) {
            $expectedDuration = $content->video->duration / 60;
        }

        if ($expectedDuration > 0 && $duration > 0) {
            $efficiency = $duration / $expectedDuration;
            if ($efficiency < 0.15 && $completion > 80) {
                return true; // Completed 80%+ in less than 15% of expected time
            }
        }

        return false;
    }

    /**
     * ✅ ENHANCED: Update progress with comprehensive PDF support
     */
    /**
     * ✅ FIXED: Create separate progress records for each session
     */
    /**
     * ✅ SIMPLE: Auto-detect skips from position changes
     */
    public function updateProgress(Request $request, ModuleContent $content)
    {
        $user = auth()->user();

        Log::info('🔍 === SMART SKIP DETECTION UPDATE PROGRESS ===', [
            'user_id' => $user->id,
            'content_id' => $content->id,
            'request_data' => $request->all(),
        ]);

        try {
            $validated = $request->validate([
                'current_position' => 'required|numeric|min:0',
                'completion_percentage' => 'required|numeric|min:0|max:100',
                'watch_time' => 'nullable|integer|min:0',
            ]);

            // ✅ SMART: Get PREVIOUS position to detect skips
            $previousProgress = UserContentProgress::where('user_id', $user->id)
                ->where('content_id', $content->id)
                ->first();

            $previousPosition = $previousProgress?->playback_position ?? 0;
            $currentPosition = $validated['current_position'];

            // ✅ AUTO-DETECT: Calculate skips from position jump
            $skipCount = 0;
            $positionJump = $currentPosition - $previousPosition;

            if ($content->content_type === 'video') {
                // For video: if user jumps forward more than 30 seconds = skip
                if ($positionJump > 30) {
                    $skipCount = 1; // Detected a skip!
                    Log::info("🎥 VIDEO SKIP DETECTED!", [
                        'previous_position' => $previousPosition,
                        'current_position' => $currentPosition,
                        'position_jump' => $positionJump,
                        'skip_detected' => true,
                    ]);
                }
            } elseif ($content->content_type === 'pdf') {
                // For PDF: if user jumps forward more than 2 pages = skip
                if ($positionJump > 2) {
                    $skipCount = 1; // Detected a skip!
                    Log::info("📄 PDF SKIP DETECTED!", [
                        'previous_page' => $previousPosition,
                        'current_page' => $currentPosition,
                        'page_jump' => $positionJump,
                        'skip_detected' => true,
                    ]);
                }
            }

            // ✅ SMART: Calculate REAL watch time based on position change
            $realWatchTime = $validated['watch_time'] ?? 0;

            if ($skipCount > 0) {
                // If skip detected, don't count the skipped time as "watched"
                if ($content->content_type === 'video') {
                    $skippedSeconds = max(0, $positionJump - 5); // Subtract skipped time
                    $realWatchTime = max(0, $realWatchTime - $skippedSeconds);
                }
                Log::info("⚠️ Adjusted watch time due to skip", [
                    'original_watch_time' => $validated['watch_time'],
                    'adjusted_watch_time' => $realWatchTime,
                    'skipped_amount' => $positionJump,
                ]);
            }

            // ✅ UPDATE: UserContentProgress (keep your existing logic)
            $progress = UserContentProgress::updateOrCreate([
                'user_id' => $user->id,
                'content_id' => $content->id,
            ], [
                'course_online_id' => $content->module->course_online_id,
                'module_id' => $content->module_id,
                'content_type' => $content->content_type,
                'video_id' => $content->video_id,
                'playback_position' => $currentPosition,
                'completion_percentage' => $validated['completion_percentage'],
                'watch_time' => $realWatchTime, // ✅ Adjusted for skips
                'is_completed' => $validated['completion_percentage'] >= 95,
                'last_accessed_at' => now(),
                'completed_at' => $validated['completion_percentage'] >= 95 ? now() : null,
            ]);

            // ✅ SMART: Update current session with detected skip data
            $currentSession = LearningSession::where('user_id', $user->id)
                ->where('content_id', $content->id)
                ->whereNull('session_end')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($currentSession) {
                $sessionDuration = max(0, now()->diffInMinutes($currentSession->session_start));

                // ✅ INCREMENT: Add detected skip to session total
                $currentSkipCount = $currentSession->video_skip_count ?? 0;
                $newSkipCount = $currentSkipCount + $skipCount;

                $currentSession->update([
                    'current_position' => $currentPosition,
                    'total_duration_minutes' => $sessionDuration,
                    'video_watch_time' => $realWatchTime,
                    'video_skip_count' => $newSkipCount, // ✅ Cumulative skip count
                    'video_completion_percentage' => $validated['completion_percentage'],
                    'last_heartbeat' => now(),
                ]);

                Log::info('✅ SMART: Updated session with auto-detected skip data', [
                    'session_id' => $currentSession->id,
                    'session_duration' => $sessionDuration,
                    'real_watch_time' => $realWatchTime,
                    'new_skip_detected' => $skipCount,
                    'total_skip_count' => $newSkipCount,
                    'completion_percentage' => $validated['completion_percentage'],
                ]);
            }

            Log::info('✅ SMART: Progress updated with auto-skip detection', [
                'progress_id' => $progress->id,
                'skip_detected_this_update' => $skipCount,
                'position_jump' => $positionJump,
                'completion_percentage' => $progress->completion_percentage,
            ]);

            return response()->json([
                'success' => true,
                'is_completed' => $progress->is_completed,
                'completion_percentage' => $progress->completion_percentage,
                'total_watch_time' => $realWatchTime,
                'skip_detected' => $skipCount > 0,
                'position_jump' => $positionJump,
                'message' => 'Progress updated with smart skip detection'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Smart skip detection error', [
                'user_id' => $user->id,
                'content_id' => $content->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update progress: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ ENHANCED: Complete method with PDF support
     */
    public function complete(ModuleContent $content)
    {
        $user = auth()->user();

        Log::info('🎯 === ENHANCED CONTENT COMPLETE START ===', [
            'user_id' => $user->id,
            'content_id' => $content->id,
            'content_title' => $content->title,
            'content_type' => $content->content_type,
            'pdf_page_count' => $content->pdf_page_count,
            'has_pdf_page_count' => !is_null($content->pdf_page_count),
        ]);

        try {
            // Check access
            $assignment = CourseOnlineAssignment::where('course_online_id', $content->module->course_online_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            // ✅ ENHANCED: Set final position based on content type and page count
            $finalPosition = 0;
            if ($content->content_type === 'pdf' && $content->pdf_page_count) {
                $finalPosition = $content->pdf_page_count; // Last page from database
            } elseif ($content->content_type === 'video' && $content->video) {
                $finalPosition = $content->video->duration; // End of video
            }

            // Calculate final watch time
            $finalWatchTime = 0;
            if ($content->content_type === 'pdf' && $content->pdf_page_count) {
                $finalWatchTime = $content->pdf_page_count * 2; // 2 minutes per page
            }

            // ✅ DISABLE MODEL EVENTS to prevent errors
            \App\Models\UserContentProgress::withoutEvents(function () use ($content, $user, $finalPosition, $finalWatchTime) {
                UserContentProgress::updateOrCreate([
                    'user_id' => $user->id,
                    'content_id' => $content->id,
                ], [
                    'course_online_id' => $content->module->course_online_id,
                    'module_id' => $content->module_id,
                    'content_type' => $content->content_type,
                    'video_id' => $content->video_id,
                    'playback_position' => $finalPosition,
                    'completion_percentage' => 100,
                    'watch_time' => $finalWatchTime,
                    'is_completed' => true,
                    'completed_at' => now(),
                    'last_accessed_at' => now(),
                ]);
            });

            Log::info('✅ Enhanced content marked complete', [
                'final_position' => $finalPosition,
                'final_watch_time' => $finalWatchTime,
                'pdf_page_count' => $content->pdf_page_count,
                'content_type' => $content->content_type,
            ]);

            // Manual progress calculation
            $totalContent = ModuleContent::whereHas('module', function($query) use ($content) {
                $query->where('course_online_id', $content->module->course_online_id);
            })->count();

            $completedContent = UserContentProgress::where('user_id', $user->id)
                ->where('course_online_id', $content->module->course_online_id)
                ->where('is_completed', true)
                ->count();

            $progressPercentage = $totalContent > 0 ? round(($completedContent / $totalContent) * 100, 2) : 0;

            // Update assignment manually
            $assignment->updateProgress($progressPercentage);

            Log::info('✅ Enhanced assignment progress updated', [
                'assignment_id' => $assignment->id,
                'progress_percentage' => $progressPercentage,
                'total_content' => $totalContent,
                'completed_content' => $completedContent,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Content completed successfully!',
                'is_completed' => true,
                'completion_percentage' => 100,
                'course_progress' => $progressPercentage,
                // ✅ ENHANCED: Return PDF completion data
                'pdf_page_count' => $content->pdf_page_count,
                'final_position' => $finalPosition,
                'final_watch_time' => $finalWatchTime,
                'content_type' => $content->content_type,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Enhanced content complete error', [
                'user_id' => $user->id,
                'content_id' => $content->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete content: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Manual assignment progress update (no events)
     */
    private function updateAssignmentProgressManually(int $courseId, int $userId): void
    {
        try {
            $assignment = CourseOnlineAssignment::where('course_online_id', $courseId)
                ->where('user_id', $userId)
                ->first();

            if (!$assignment) return;

            $totalContent = ModuleContent::whereHas('module', function($query) use ($courseId) {
                $query->where('course_online_id', $courseId);
            })->count();

            if ($totalContent === 0) return;

            $completedContent = UserContentProgress::where('user_id', $userId)
                ->where('course_online_id', $courseId)
                ->where('is_completed', true)
                ->count();

            $progressPercentage = round(($completedContent / $totalContent) * 100, 2);

            // Update assignment manually (no events)
            \App\Models\CourseOnlineAssignment::withoutEvents(function () use ($assignment, $progressPercentage) {
                $assignment->updateProgress($progressPercentage);
            });

            Log::info('📊 Assignment progress updated manually', [
                'assignment_id' => $assignment->id,
                'progress_percentage' => $progressPercentage,
                'total_content' => $totalContent,
                'completed_content' => $completedContent,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Manual assignment progress update failed', [
                'course_id' => $courseId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ===== HELPER METHODS =====

    private function getContentNavigation(ModuleContent $content, int $userId): array
    {
        return [
            'previous' => $this->getPreviousContent($content),
            'next' => $this->getNextContent($content),
        ];
    }

    private function getPreviousContent(ModuleContent $content): ?array
    {
        $previousInModule = $content->module->content()
            ->where('order_number', '<', $content->order_number)
            ->orderBy('order_number', 'desc')
            ->first();

        if ($previousInModule) {
            return [
                'id' => $previousInModule->id,
                'title' => $previousInModule->title,
                'content_type' => $previousInModule->content_type,
            ];
        }

        return null;
    }

    private function getNextContent(ModuleContent $content): ?array
    {
        $nextInModule = $content->module->content()
            ->where('order_number', '>', $content->order_number)
            ->orderBy('order_number')
            ->first();

        if ($nextInModule) {
            return [
                'id' => $nextInModule->id,
                'title' => $nextInModule->title,
                'content_type' => $nextInModule->content_type,
                'is_unlocked' => true, // Simplified for now
            ];
        }

        return null;
    }
}
