<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ModuleContent;
use App\Services\ContentView\ContentAccessService;
use App\Services\ContentView\VideoStreamingService;
use App\Services\ContentView\ContentProgressService;
use App\Services\ContentView\ContentNavigationService;
use App\Services\ContentView\ContentDataService;
use App\Services\ContentView\LearningSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\LearningSession;
use Illuminate\Support\Facades\Auth;

class ContentViewController extends Controller
{
    public function __construct(
        protected ContentAccessService $accessService,
        protected VideoStreamingService $videoService,
        protected ContentProgressService $progressService,
        protected ContentNavigationService $navigationService,
        protected ContentDataService $dataService,
        protected LearningSessionService $sessionService
    ) {
        Log::info('🏗️ ContentViewController initialized with all services', [
            'timestamp' => now(),
            'services_loaded' => [
                'access', 'video', 'progress', 'navigation', 'data', 'session'
            ],
        ]);
    }

    /**
     * Display content viewer
     */
    public function show(ModuleContent $content)
    {
        $user = auth()->user();

        Log::info('🎬 === CONTENT VIEWER SHOW START ===', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'content_id' => $content->id,
            'content_title' => $content->title,
            'content_type' => $content->content_type,
            'module_id' => $content->module_id,
            'course_id' => $content->module->course_online_id,
        ]);

        try {
            // 1. CHECK ACCESS
            $assignment = $this->accessService->verifyAccessOrFail($user, $content);
            
            Log::info('✅ Access verified via service', [
                'assignment_id' => $assignment->id,
                'status' => $assignment->status,
            ]);

            // 2. LOAD RELATIONSHIPS
            $content->load(['module.courseOnline', 'video']);

            // 3. GET/CREATE PROGRESS
            $progress = $this->progressService->getOrCreateProgress($user, $content);
            
            Log::info('📊 Progress retrieved via service', [
                'progress_id' => $progress->id,
                'completion' => $progress->completion_percentage,
                'is_new' => $progress->wasRecentlyCreated,
            ]);

            // 4. GET VIDEO STREAMING URL (if video content)
            $streamingData = null;
            if ($content->content_type === 'video' && $content->video) {
                $streamingData = $this->videoService->getStreamingUrl($content->video, $content);
                
                Log::info('🎥 Video streaming URL generated via service', [
                    'has_url' => !is_null($streamingData),
                    'key_used' => $streamingData['key_name'] ?? null,
                ]);
            }

            // 5. GET NAVIGATION
            $navigation = $this->navigationService->getNavigationWithProgress($content, $user->id);
            
            Log::info('🧭 Navigation retrieved via service', [
                'has_previous' => !is_null($navigation['previous']),
                'has_next' => !is_null($navigation['next']),
            ]);

            // 6. BUILD RESPONSE
            $responseData = $this->dataService->buildInertiaResponse(
                $content,
                $content->video,
                $streamingData,
                $progress,
                $navigation
            );

            Log::info('📦 Response built via service', [
                'content_type' => $content->content_type,
                'has_video_data' => isset($responseData['video']),
                'has_pdf_data' => isset($responseData['pdf']),
            ]);

            Log::info('✅ === CONTENT VIEWER SHOW COMPLETE ===');

            return Inertia::render('User/ContentViewer/Show', $responseData);

        } catch (\Exception $e) {
            Log::error('❌ === CONTENT VIEWER SHOW FAILED ===', [
                'user_id' => $user->id,
                'content_id' => $content->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Manage learning session (start/heartbeat/end)
     */
    public function manageSession(Request $request, ModuleContent $content)
    {
        $user = auth()->user();
        $action = $request->input('action');

        Log::info('🎬 === SESSION MANAGEMENT START ===', [
            'user_id' => $user->id,
            'content_id' => $content->id,
            'action' => $action,
        ]);

        try {
            // Verify access
            $this->accessService->verifyAccessOrFail($user, $content);

            switch ($action) {
                case 'start':
    // ✅ Get key_id from request (sent by frontend) OR session storage as fallback
    $keyId = $request->input('api_key_id') ?? session("content_{$content->id}_key_id");
    
    Log::info('🔍 Retrieved key_id for session start', [
        'content_id' => $content->id,
        'key_id_from_request' => $request->input('api_key_id'),
        'key_id_from_session' => session("content_{$content->id}_key_id"),
        'final_key_id' => $keyId
    ]);
    
    $session = $this->sessionService->startSession(
        $user,
        $content,
        $request->input('position', 0),
        $keyId  // ✅ Pass the key_id
    );

    Log::info('▶️ Session started via service', [
        'session_id' => $session->id,
        'api_key_id' => $session->api_key_id  // ✅ Verify it was saved
    ]);

    return response()->json([
        'success' => true,
        'session_id' => $session->id,
        'message' => 'Session started successfully',
    ]);

                case 'heartbeat':
                    $session = $this->sessionService->getActiveSession($user->id, $content->id);

                    if (!$session) {
                        Log::warning('⚠️ No active session for heartbeat');
                        return response()->json(['success' => false, 'message' => 'No active session'], 404);
                    }

                    $updated = $this->sessionService->updateHeartbeat(
                        $session->id,
                        $request->input('current_position', 0),
                        $request->input('watch_time_increment', 0),
                        $request->input('skip_count_increment', 0),
                        $request->input('seek_count_increment', 0),
                        $request->input('pause_count_increment', 0)
                    );

                    Log::debug('💓 Heartbeat updated via service', [
                        'session_id' => $updated->id,
                        'duration' => $updated->total_duration_minutes,
                    ]);

                    return response()->json([
                        'success' => true,
                        'duration_minutes' => $updated->total_duration_minutes,
                        'total_watch_time' => $updated->video_watch_time,
                    ]);

                case 'end':
                    $session = $this->sessionService->getActiveSession($user->id, $content->id);

                    if (!$session) {
                        Log::warning('⚠️ No active session to end');
                        return response()->json(['success' => false, 'message' => 'No active session'], 404);
                    }

                    $ended = $this->sessionService->endSession(
                        $session->id,
                        $request->input('final_position', 0),
                        $request->input('completion_percentage', 0),
                        $request->input('final_watch_time', 0),
                        $request->input('final_skip', 0),
                        $request->input('final_seek', 0),
                        $request->input('final_pause', 0)
                    );

                    Log::info('🏁 Session ended via service', [
                        'session_id' => $ended->id,
                        'attention_score' => $ended->attention_score,
                        'cheating_score' => $ended->cheating_score,
                        'is_suspicious' => $ended->is_suspicious_activity,
                    ]);

                    return response()->json([
                        'success' => true,
                        'attention_score' => $ended->attention_score,
                        'cheating_score' => $ended->cheating_score,
                        'is_suspicious' => $ended->is_suspicious_activity,
                    ]);

                default:
                    return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
            }

        } catch (\Exception $e) {
            Log::error('❌ === SESSION MANAGEMENT FAILED ===', [
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
     * Update user progress
     */
    public function updateProgress(Request $request, ModuleContent $content)
    {
        $user = auth()->user();

        Log::info('📊 === UPDATE PROGRESS START ===', [
            'user_id' => $user->id,
            'content_id' => $content->id,
        ]);

        try {
            $validated = $request->validate([
                'current_position' => 'required|numeric|min:0',
                'completion_percentage' => 'required|numeric|min:0|max:100',
                'watch_time' => 'nullable|integer|min:0',
            ]);

            // Get progress
            $progress = $this->progressService->getOrCreateProgress($user, $content);

            // Update progress via service
            $updated = $this->progressService->updateProgress(
                $progress->id,
                $validated['current_position'],
                $validated['completion_percentage'],
                $validated['watch_time']
            );

            Log::info('✅ Progress updated via service', [
                'progress_id' => $updated->id,
                'completion' => $updated->completion_percentage,
                'is_completed' => $updated->is_completed,
            ]);

            return response()->json([
                'success' => true,
                'is_completed' => $updated->is_completed,
                'completion_percentage' => $updated->completion_percentage,
                'message' => 'Progress updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('❌ === UPDATE PROGRESS FAILED ===', [
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
     * Mark content as complete
     */
    public function complete(ModuleContent $content)
    {
        $user = auth()->user();

        Log::info('🎯 === MARK COMPLETE START ===', [
            'user_id' => $user->id,
            'content_id' => $content->id,
        ]);

        try {
            // Verify access
            $this->accessService->verifyAccessOrFail($user, $content);

            // Get progress
            $progress = $this->progressService->getOrCreateProgress($user, $content);

            // Mark as completed via service
            $completed = $this->progressService->markAsCompleted($progress);

            Log::info('✅ Content marked complete via service', [
                'progress_id' => $completed->id,
                'completion' => $completed->completion_percentage,
            ]);

            // Calculate course progress
            $courseProgress = $this->progressService->calculateCourseProgress(
                $content->module->course_online_id,
                $user->id
            );

            return response()->json([
                'success' => true,
                'is_completed' => true,
                'completion_percentage' => 100,
                'course_progress' => $courseProgress,
                'message' => 'Content completed successfully!',
            ]);

        } catch (\Exception $e) {
            Log::error('❌ === MARK COMPLETE FAILED ===', [
                'user_id' => $user->id,
                'content_id' => $content->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete content: ' . $e->getMessage()
            ], 500);
        }
    }


    
}
