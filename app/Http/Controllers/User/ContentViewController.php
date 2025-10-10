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

        // ✅ ENHANCED: Log session data including PDF info
        Log::info('📹 Enhanced session management', [
            'user_id' => $user->id,
            'content_id' => $content->id,
            'action' => $action,
            'position' => $currentPosition,
            'content_type' => $content->content_type,
            'pdf_page_count' => $content->content_type === 'pdf' ? $content->pdf_page_count : null,
            'has_pdf_page_count' => $content->content_type === 'pdf' ? !is_null($content->pdf_page_count) : false,
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

                    // ✅ ENHANCED: Return PDF metadata with session
                    $responseData = [
                        'success' => true,
                        'session_id' => $session->id,
                        'message' => 'Session started'
                    ];

                    if ($content->content_type === 'pdf') {
                        $responseData['pdf_data'] = [
                            'pdf_page_count' => $content->pdf_page_count,
                            'has_page_count' => !is_null($content->pdf_page_count),
                            'estimated_reading_time' => $content->pdf_page_count ? ($content->pdf_page_count * 2) : null,
                        ];
                    }

                    Log::info('✅ Enhanced session started', [
                        'session_id' => $session->id,
                        'content_type' => $content->content_type,
                        'pdf_page_count' => $content->pdf_page_count ?? 'N/A',
                        'has_pdf_data' => isset($responseData['pdf_data']),
                    ]);

                    return response()->json($responseData);

                case 'heartbeat':
                    $session = LearningSession::where('user_id', $user->id)
                        ->where('content_id', $content->id)
                        ->whereNull('session_end')
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($session) {
                        $currentDuration = max(0, now()->diffInMinutes($session->session_start));

                        // ✅ ENHANCED: Validate position for PDF content
                        if ($content->content_type === 'pdf' && $content->pdf_page_count) {
                            $currentPosition = max(1, min($content->pdf_page_count, $currentPosition));
                        }

                        $session->update([
                            'current_position' => $currentPosition,
                            'last_heartbeat' => now(),
                            'total_duration_minutes' => $currentDuration,
                        ]);

                        Log::info('💓 Enhanced session heartbeat', [
                            'session_id' => $session->id,
                            'duration_minutes' => $currentDuration,
                            'position' => $currentPosition,
                            'content_type' => $content->content_type,
                            'pdf_page_validation' => $content->content_type === 'pdf' ? "max_{$content->pdf_page_count}" : 'N/A',
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

                        // ✅ ENHANCED: Validate final position for PDF
                        if ($content->content_type === 'pdf' && $content->pdf_page_count) {
                            $currentPosition = max(1, min($content->pdf_page_count, $currentPosition));
                        }

                        $session->update([
                            'session_end' => now(),
                            'current_position' => $currentPosition,
                            'total_duration_minutes' => $totalDuration,
                        ]);

                        Log::info('✅ Enhanced session ended', [
                            'session_id' => $session->id,
                            'total_duration_minutes' => $totalDuration,
                            'final_position' => $currentPosition,
                            'content_type' => $content->content_type,
                            'pdf_page_count' => $content->pdf_page_count ?? 'N/A',
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
            Log::error('❌ Enhanced session management error', [
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
     * ✅ ENHANCED: Update progress with comprehensive PDF support
     */
    public function updateProgress(Request $request, ModuleContent $content)
    {
        $user = auth()->user();

        Log::info('🔍 === ENHANCED UPDATE PROGRESS START ===', [
            'user_id' => $user->id,
            'content_id' => $content->id,
            'content_type' => $content->content_type,
            'pdf_page_count' => $content->pdf_page_count,
            'has_pdf_page_count' => !is_null($content->pdf_page_count),
            'request_data' => $request->all(),
        ]);

        try {
            // ✅ ENHANCED VALIDATION with PDF page validation
            $rules = [
                'current_position' => 'required|numeric|min:0',
                'completion_percentage' => 'required|numeric|min:0|max:100',
            ];

            // Enhanced validation based on content type
            if ($content->content_type === 'video') {
                $rules['watch_time'] = 'nullable|integer|min:0';
            } elseif ($content->content_type === 'pdf') {
                // For PDF, validate current position against page count if available
                if ($content->pdf_page_count) {
                    $rules['current_position'] = "required|numeric|min:1|max:{$content->pdf_page_count}";
                }
            }

            $validated = $request->validate($rules);

            Log::info('✅ Enhanced validation passed', [
                'user_id' => $user->id,
                'content_id' => $content->id,
                'validated_data' => $validated,
                'pdf_page_validation' => $content->content_type === 'pdf' && $content->pdf_page_count ?
                    "range_1_to_{$content->pdf_page_count}" : 'N/A',
            ]);

            $completionPercentage = max(0, min(100, $validated['completion_percentage']));
            $currentPosition = max(0, $validated['current_position']);

            // ✅ ENHANCED: PDF-specific position validation and completion calculation
            if ($content->content_type === 'pdf' && $content->pdf_page_count) {
                $currentPosition = max(1, min($content->pdf_page_count, $currentPosition));

                // Recalculate completion based on page count from database
                $pageBasedCompletion = ($currentPosition / $content->pdf_page_count) * 100;
                $completionPercentage = max($completionPercentage, $pageBasedCompletion);

                Log::info('📄 Enhanced PDF progress calculated', [
                    'current_page' => $currentPosition,
                    'total_pages' => $content->pdf_page_count,
                    'page_based_completion' => $pageBasedCompletion,
                    'final_completion' => $completionPercentage,
                    'original_completion' => $validated['completion_percentage'],
                ]);
            }

            // ✅ Enhanced watch_time calculation
            $watchTime = 0;
            if ($content->content_type === 'video') {
                $watchTime = max(0, $validated['watch_time'] ?? 0);
            } elseif ($content->content_type === 'pdf') {
                // For PDF, calculate reading time based on pages read and database count
                if ($content->pdf_page_count && $currentPosition > 0) {
                    $pagesRead = $currentPosition;
                    $watchTime = $pagesRead * 2; // 2 minutes per page average
                    Log::info('📖 PDF reading time calculated', [
                        'pages_read' => $pagesRead,
                        'total_pages' => $content->pdf_page_count,
                        'calculated_time' => $watchTime,
                    ]);
                } else {
                    $watchTime = max(0, intval($request->input('reading_time', 0)));
                }
            }

            Log::info('📊 Enhanced progress values calculated', [
                'user_id' => $user->id,
                'content_id' => $content->id,
                'content_type' => $content->content_type,
                'current_position' => $currentPosition,
                'completion_percentage' => $completionPercentage,
                'watch_time' => $watchTime,
                'pdf_page_count' => $content->pdf_page_count,
                'calculation_method' => $content->content_type === 'pdf' ? 'page_based' : 'time_based',
            ]);

            // ✅ DISABLE EVENTS to prevent calculateProgress errors
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

            Log::info('✅ Enhanced progress updated successfully', [
                'user_id' => $user->id,
                'content_id' => $content->id,
                'progress_id' => $progress->id,
                'completion_percentage' => $progress->completion_percentage,
                'is_completed' => $progress->is_completed,
                'playback_position' => $progress->playback_position,
                'watch_time' => $progress->watch_time,
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
                // ✅ ENHANCED: Return comprehensive PDF info
                'pdf_page_count' => $content->pdf_page_count,
                'current_page' => $content->content_type === 'pdf' ? $currentPosition : null,
                'has_pdf_page_count' => !is_null($content->pdf_page_count),
                'estimated_reading_time' => $content->pdf_page_count ? ($content->pdf_page_count * 2) : null,
                'message' => 'Progress updated successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Enhanced validation error in updateProgress', [
                'user_id' => $user->id,
                'content_id' => $content->id,
                'errors' => $e->errors(),
                'request_data' => $request->all(),
                'pdf_page_count' => $content->pdf_page_count,
                'validation_rules' => 'enhanced_pdf_aware',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ Enhanced progress update error', [
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
