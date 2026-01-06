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

    }

    /**
     * Display content viewer
     */
    public function show(ModuleContent $content)
    {
        $user = auth()->user();



        try {
            // 1. CHECK ACCESS
            $assignment = $this->accessService->verifyAccessOrFail($user, $content);



            // 2. LOAD RELATIONSHIPS
            $content->load(['module.courseOnline', 'video']);

            // 3. GET/CREATE PROGRESS
            $progress = $this->progressService->getOrCreateProgress($user, $content);



            // 4. GET VIDEO STREAMING URL (if video content)
            $streamingData = null;
            if ($content->content_type === 'video' && $content->video) {
                $streamingData = $this->videoService->getStreamingUrl($content->video, $content);


            }

            // 5. GET NAVIGATION
            $navigation = $this->navigationService->getNavigationWithProgress($content, $user->id);



            // 6. BUILD RESPONSE
            $responseData = $this->dataService->buildInertiaResponse(
                $content,
                $content->video,
                $streamingData,
                $progress,
                $navigation
            );




            return Inertia::render('User/ContentViewer/Show', $responseData);

        } catch (\Exception $e) {


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



        try {
            // Verify access
            $this->accessService->verifyAccessOrFail($user, $content);

            switch ($action) {
                case 'start':
    // ✅ Get key_id from request (sent by frontend) OR session storage as fallback
    $keyId = $request->input('api_key_id') ?? session("content_{$content->id}_key_id");



    $session = $this->sessionService->startSession(
        $user,
        $content,
        $request->input('position', 0),
        $keyId  // ✅ Pass the key_id
    );



    return response()->json([
        'success' => true,
        'session_id' => $session->id,
        'message' => 'Session started successfully',
    ]);

                case 'heartbeat':
                    $session = $this->sessionService->getActiveSession($user->id, $content->id);

                    if (!$session) {
                        return response()->json(['success' => false, 'message' => 'No active session'], 404);
                    }

                    // ✅ NEW: Update active playback time if provided (Task 6.5)
                    if ($request->has('active_playback_time')) {
                        $this->sessionService->updateActivePlaybackTime(
                            $session->id,
                            $request->input('active_playback_time', 0),
                            [] // Video events are only sent on session end
                        );
                    }

                    $updated = $this->sessionService->updateHeartbeat(
                        $session->id,
                        $request->input('current_position', 0),
                        $request->input('watch_time', 0), // ✅ FIXED: Changed from watch_time_increment to watch_time
                        $request->input('skip_count', 0), // ✅ FIXED: Changed from skip_count_increment to skip_count
                        $request->input('seek_count', 0), // ✅ FIXED: Changed from seek_count_increment to seek_count
                        $request->input('pause_count', 0), // ✅ FIXED: Changed from pause_count_increment to pause_count
                        $request->input('completion_percentage', 0) // ✅ NEW: Pass completion percentage
                    );



                    return response()->json([
                        'success' => true,
                        'duration_minutes' => $updated->total_duration_minutes,
                        'total_watch_time' => $updated->video_watch_time,
                    ]);

                case 'end':
                    $session = $this->sessionService->getActiveSession($user->id, $content->id);

                    if (!$session) {
                        return response()->json(['success' => false, 'message' => 'No active session'], 404);
                    }

                    // ✅ NEW: Update active playback time and video events before ending (Task 6.5)
                    if ($request->has('active_playback_time')) {
                        $videoEvents = $request->input('video_events', []);
                        
                        // Handle video_events as JSON string (from FormData) or array
                        if (is_string($videoEvents)) {
                            $videoEvents = json_decode($videoEvents, true) ?? [];
                        }
                        
                        $this->sessionService->updateActivePlaybackTime(
                            $session->id,
                            $request->input('active_playback_time', 0),
                            $videoEvents
                        );
                    }

                    $ended = $this->sessionService->endSession(
                        $session->id,
                        $request->input('final_position', $request->input('current_position', 0)),
                        $request->input('completion_percentage', 0),
                        $request->input('final_watch_time', $request->input('watch_time', 0)),
                        $request->input('final_skip', $request->input('skip_count', 0)),
                        $request->input('final_seek', $request->input('seek_count', 0)),
                        $request->input('final_pause', $request->input('pause_count', 0))
                    );

                    // ✅ NEW: Update user progress with active playback time from this session
                    $progress = $this->progressService->getOrCreateProgress($user, $content);
                    
                    // Calculate total watch time: previous sessions + this session's active playback
                    $activePlaybackMinutes = ($ended->active_playback_time ?? 0) / 60;
                    $totalWatchTime = ($progress->watch_time ?? 0) + $activePlaybackMinutes;
                    
                    // Update progress with new watch time
                    $this->progressService->updateProgress(
                        $progress->id,
                        $request->input('final_position', 0),
                        $request->input('completion_percentage', 0),
                        (int) $totalWatchTime
                    );

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



            return response()->json([
                'success' => true,
                'is_completed' => $updated->is_completed,
                'completion_percentage' => $updated->completion_percentage,
                'message' => 'Progress updated successfully',
            ]);

        } catch (\Exception $e) {


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



        try {
            // Verify access
            $this->accessService->verifyAccessOrFail($user, $content);

            // Get progress
            $progress = $this->progressService->getOrCreateProgress($user, $content);

            // Mark as completed via service
            $completed = $this->progressService->markAsCompleted($progress);

            // Get module safely to avoid lazy loading
            $module = $content->relationLoaded('module') ? $content->module : $content->load('module')->module;

            // Calculate course progress
            $courseProgress = $this->progressService->calculateCourseProgress(
                $module->course_online_id,
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

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete content: ' . $e->getMessage()
            ], 500);
        }
    }



}
