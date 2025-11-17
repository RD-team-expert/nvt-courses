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

                    $updated = $this->sessionService->updateHeartbeat(
                        $session->id,
                        $request->input('current_position', 0),
                        $request->input('watch_time_increment', 0),
                        $request->input('skip_count_increment', 0),
                        $request->input('seek_count_increment', 0),
                        $request->input('pause_count_increment', 0)
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

                    $ended = $this->sessionService->endSession(
                        $session->id,
                        $request->input('final_position', 0),
                        $request->input('completion_percentage', 0),
                        $request->input('final_watch_time', 0),
                        $request->input('final_skip', 0),
                        $request->input('final_seek', 0),
                        $request->input('final_pause', 0)
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

            return response()->json([
                'success' => false,
                'message' => 'Failed to complete content: ' . $e->getMessage()
            ], 500);
        }
    }



}
