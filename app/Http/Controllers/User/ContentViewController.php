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
     * ✅ LOGGED: Display content viewer
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

        // Get PDF file URL
        $fileUrl = null;
        if ($content->content_type === 'pdf') {
            if ($content->file_path) {
                $fileUrl = asset('storage/' . $content->file_path);
            } elseif ($content->google_drive_pdf_url) {
                $fileUrl = $content->google_drive_pdf_url;
            }
        }

        // Get video streaming URL
        $streamingUrl = null;
        if ($content->content_type === 'video' && $content->video) {
            $streamingUrl = $this->googleDriveService->processUrl($content->video->google_drive_url);
        }

        // Get navigation
        $navigation = $this->getContentNavigation($content, $user->id);

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

        return Inertia::render('User/ContentViewer/Show', $responseData);
    }


    /**
     * ✅ ENHANCED: Video session management with better time tracking
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

        Log::info('📹 Video session management', [
            'user_id' => $user->id,
            'content_id' => $content->id,
            'action' => $action,
            'position' => $currentPosition,
            'content_type' => $content->content_type,
        ]);

        try {
            switch ($action) {
                case 'start':
                    // End any existing active sessions for this content
                    $existingSessions = LearningSession::where('user_id', $user->id)
                        ->where('content_id', $content->id)
                        ->whereNull('session_end')
                        ->get();

                    foreach ($existingSessions as $existingSession) {
                        $duration = max(0, now()->diffInMinutes($existingSession->session_start));
                        $existingSession->update([
                            'session_end' => now(),
                            'total_duration_minutes' => $duration,
                        ]);

                        Log::info('🔄 Ended existing session', [
                            'session_id' => $existingSession->id,
                            'duration_minutes' => $duration,
                        ]);
                    }

                    // Create new session
                    $session = LearningSession::create([
                        'user_id' => $user->id,
                        'course_online_id' => $content->module->course_online_id,
                        'content_id' => $content->id,
                        'session_start' => now(),
                        'current_position' => $currentPosition,
                        'total_duration_minutes' => 0,
                    ]);

                    Log::info('✅ New video session started', [
                        'session_id' => $session->id,
                        'content_type' => $content->content_type,
                    ]);

                    return response()->json([
                        'success' => true,
                        'session_id' => $session->id,
                        'message' => 'Video session started'
                    ]);

                case 'heartbeat':
                    $session = LearningSession::where('user_id', $user->id)
                        ->where('content_id', $content->id)
                        ->whereNull('session_end')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($session) {
                        $currentDuration = max(0, now()->diffInMinutes($session->session_start));

                        $session->update([
                            'current_position' => $currentPosition,
                            'last_heartbeat' => now(),
                            'total_duration_minutes' => $currentDuration,
                        ]);

                        Log::info('💓 Video session heartbeat', [
                            'session_id' => $session->id,
                            'duration_minutes' => $currentDuration,
                            'position' => $currentPosition,
                        ]);

                        return response()->json([
                            'success' => true,
                            'duration_minutes' => $currentDuration,
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

                        $session->update([
                            'session_end' => now(),
                            'current_position' => $currentPosition,
                            'total_duration_minutes' => $totalDuration,
                        ]);

                        Log::info('✅ Video session ended', [
                            'session_id' => $session->id,
                            'total_duration_minutes' => $totalDuration,
                            'final_position' => $currentPosition,
                        ]);

                        return response()->json([
                            'success' => true,
                            'total_duration_minutes' => $totalDuration,
                        ]);
                    }

                    return response()->json(['success' => false, 'message' => 'No active session'], 404);

                default:
                    return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
            }

        } catch (\Exception $e) {
            Log::error('❌ Video session management error', [
                'user_id' => $user->id,
                'content_id' => $content->id,
                'action' => $action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ LOGGED: Update progress with detailed logging
     */
    public function updateProgress(Request $request, ModuleContent $content)
    {
        $user = auth()->user();

        Log::info('🔍 === UPDATE PROGRESS START ===', [
            'user_id' => $user->id,
            'content_id' => $content->id,
            'content_type' => $content->content_type,
            'request_data' => $request->all(),
        ]);

        try {
            // ✅ FLEXIBLE VALIDATION - handle both video and PDF
            $rules = [
                'current_position' => 'required|numeric|min:0',
                'completion_percentage' => 'required|numeric|min:0|max:100',
            ];

            // ✅ Only validate watch_time for video content
            if ($content->content_type === 'video') {
                $rules['watch_time'] = 'nullable|integer|min:0';
            }

            $validated = $request->validate($rules);

            Log::info('✅ Validation passed', [
                'user_id' => $user->id,
                'content_id' => $content->id,
                'validated_data' => $validated,
            ]);

            $completionPercentage = max(0, min(100, $validated['completion_percentage']));
            $currentPosition = max(0, $validated['current_position']);

            // ✅ Handle watch_time based on content type
            $watchTime = 0;
            if ($content->content_type === 'video') {
                $watchTime = max(0, $validated['watch_time'] ?? 0);
            } elseif ($content->content_type === 'pdf') {
                // For PDF, calculate watch time from reading time or use a default
                $watchTime = max(0, intval($request->input('reading_time', 0)));
            }

            Log::info('📊 Progress values sanitized', [
                'user_id' => $user->id,
                'content_id' => $content->id,
                'original_completion' => $validated['completion_percentage'],
                'sanitized_completion' => $completionPercentage,
                'current_position' => $currentPosition,
                'watch_time' => $watchTime,
            ]);

            // ✅ DISABLE EVENTS to prevent the calculateProgress error
            $progress = null;
            \App\Models\UserContentProgress::withoutEvents(function () use ($user, $content, $currentPosition, $completionPercentage, $watchTime, &$progress) {
                $progress = UserContentProgress::updateOrCreate([
                    'user_id' => $user->id,
                    'content_id' => $content->id,
                ], [
                    'course_online_id' => $content->module->course_online_id,
                    'module_id' => $content->module_id,
                    'content_type' => $content->content_type,
                    'video_id' => $content->video_id,
                    'playback_position' => $currentPosition,
                    'completion_percentage' => $completionPercentage,
                    'watch_time' => $watchTime,
                    'is_completed' => $completionPercentage >= 95,
                    'last_accessed_at' => now(),
                    'completed_at' => $completionPercentage >= 95 ? now() : null,
                ]);
            });

            Log::info('✅ Progress updated successfully', [
                'user_id' => $user->id,
                'content_id' => $content->id,
                'progress_id' => $progress->id,
                'completion_percentage' => $progress->completion_percentage,
                'is_completed' => $progress->is_completed,
            ]);

            // ✅ Manual assignment progress update (without events)
            try {
                $this->updateAssignmentProgressManually($content->module->course_online_id, $user->id);
            } catch (\Exception $e) {
                Log::warning('Assignment progress update failed (non-critical)', [
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => true,
                'is_completed' => $progress->is_completed,
                'completion_percentage' => $progress->completion_percentage,
                'total_watch_time' => $watchTime,
                'message' => 'Progress updated successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validation error in updateProgress', [
                'user_id' => $user->id,
                'content_id' => $content->id,
                'errors' => $e->errors(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ Progress update error', [
                'user_id' => $user->id,
                'content_id' => $content->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update progress: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ NEW: Manual assignment progress update (no events)
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
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Manual assignment progress update failed', [
                'course_id' => $courseId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    /**
     * ✅ FIXED: Complete method that actually works
     */
    public function complete(ModuleContent $content)
    {
        $user = auth()->user();

        Log::info('🎯 === CONTENT COMPLETE START ===', [
            'user_id' => $user->id,
            'content_id' => $content->id,
            'content_title' => $content->title,
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

            // ✅ DISABLE MODEL EVENTS to prevent the error
            \App\Models\UserContentProgress::withoutEvents(function () use ($content, $user) {
                UserContentProgress::updateOrCreate([
                    'user_id' => $user->id,
                    'content_id' => $content->id,
                ], [
                    'course_online_id' => $content->module->course_online_id,
                    'module_id' => $content->module_id,
                    'content_type' => $content->content_type,
                    'video_id' => $content->video_id,
                    'completion_percentage' => 100,
                    'is_completed' => true,
                    'completed_at' => now(),
                    'last_accessed_at' => now(),
                ]);
            });

            Log::info('✅ Content marked complete (events disabled)');

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

            Log::info('✅ Assignment progress updated manually', [
                'progress_percentage' => $progressPercentage,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Content completed successfully!',
                'is_completed' => true,
                'completion_percentage' => 100,
                'course_progress' => $progressPercentage,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Content complete error', [
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
     * ✅ Helper method to update course assignment progress
     */
    /**
     * ✅ FIXED: Helper method to update course assignment progress
     */
    private function updateCourseAssignmentProgress(int $courseId, int $userId): void
    {
        try {
            $assignment = CourseOnlineAssignment::where('course_online_id', $courseId)
                ->where('user_id', $userId)
                ->first();

            if (!$assignment) {
                Log::warning('No assignment found for progress update', [
                    'course_id' => $courseId,
                    'user_id' => $userId,
                ]);
                return;
            }

            // Calculate total content in course
            $totalContent = ModuleContent::whereHas('module', function($query) use ($courseId) {
                $query->where('course_online_id', $courseId);
            })->count();

            if ($totalContent === 0) {
                Log::warning('No content found in course', [
                    'course_id' => $courseId,
                ]);
                return;
            }

            // Calculate completed content
            $completedContent = UserContentProgress::where('user_id', $userId)
                ->where('course_online_id', $courseId)
                ->where('is_completed', true)
                ->count();

            // Calculate progress percentage
            $progressPercentage = round(($completedContent / $totalContent) * 100, 2);

            Log::info('📊 Calculated progress', [
                'course_id' => $courseId,
                'user_id' => $userId,
                'total_content' => $totalContent,
                'completed_content' => $completedContent,
                'progress_percentage' => $progressPercentage,
            ]);

            // ✅ FIXED: Use the existing updateProgress method from your model
            $assignment->updateProgress($progressPercentage);

            Log::info('✅ Assignment progress updated', [
                'assignment_id' => $assignment->id,
                'progress' => $progressPercentage,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Failed to update assignment progress', [
                'course_id' => $courseId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
