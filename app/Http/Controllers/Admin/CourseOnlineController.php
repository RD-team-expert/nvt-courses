<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseOnline;
use App\Models\CourseModule;
use App\Models\ModuleContent;
use App\Models\Video;
use App\Services\FileUploadService;
use App\Services\ThumbnailService;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CourseOnlineController extends Controller
{
    public function __construct(
        protected FileUploadService $fileService,
        protected ThumbnailService $thumbnailService,
        protected GoogleDriveService $googleDriveService
    ) {}

    /**
     * Display listing of online courses
     */
    public function index()
    {
        $courses = CourseOnline::with(['creator', 'modules', 'assignments'])
            ->withCount(['modules', 'assignments'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return Inertia::render('Admin/CourseOnline/Index', [
            'courses' => $courses->through(fn($course) => [
                'id' => $course->id,
                'name' => $course->name,
                'description' => $course->description,
                'image_path' => $course->image_path ? asset('storage/' . $course->image_path) : null,
                'thumbnails' => $course->image_path ? [
                    'small' => $this->thumbnailService->getThumbnailUrl($course->image_path, 'small'),
                    'medium' => $this->thumbnailService->getThumbnailUrl($course->image_path, 'medium'),
                    'large' => $this->thumbnailService->getThumbnailUrl($course->image_path, 'large'),
                ] : null,
                'difficulty_level' => $course->difficulty_level,
                'estimated_duration' => $course->estimated_duration,
                'is_active' => $course->is_active,
                'creator' => $course->creator ? [
                    'id' => $course->creator->id,
                    'name' => $course->creator->name,
                ] : null,
                'modules_count' => $course->modules_count,
                'assignments_count' => $course->assignments_count,
                'completion_rate' => $this->calculateCourseCompletionRate($course),
                'created_at' => $course->created_at->toDateTimeString(),
            ])
        ]);
    }

    /**
     * ✅ FIXED: Calculate completion rate for a course using average progress
     */
    private function calculateCourseCompletionRate($course)
    {
        if ($course->assignments_count === 0) {
            return 0;
        }

        // ✅ FIXED: Use average progress percentage instead of only counting completed
        $averageProgress = $course->assignments()->avg('progress_percentage') ?? 0;

        return round($averageProgress, 2);
    }

    /**
     * Show form for creating new course
     */
    public function create()
    {
        $availableVideos = Video::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'duration', 'google_drive_url'])
            ->map(function($video) {
                return [
                    'id' => $video->id,
                    'name' => $video->name,
                    'duration' => $video->duration,
                    'formatted_duration' => gmdate('H:i:s', $video->duration),
                    'google_drive_url' => $video->google_drive_url,
                    'streaming_url' => $this->googleDriveService->processUrl($video->google_drive_url),
                    'thumbnail_url' => $video->thumbnail_url,
                ];
            });

        return Inertia::render('Admin/CourseOnline/Create', [
            'availableVideos' => $availableVideos,
        ]);
    }

    /**
     * Store newly created course
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:course_online,name',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'estimated_duration' => 'nullable|integer|min:1|max:10000',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'is_active' => 'boolean',
            'modules' => 'required|array|min:1',
            'modules.*.name' => 'required|string|max:255',
            'modules.*.description' => 'nullable|string|max:2000',
            'modules.*.estimated_duration' => 'nullable|integer|min:1|max:1000',
            'modules.*.is_required' => 'boolean',
            'modules.*.is_active' => 'boolean',
            'modules.*.content' => 'nullable|array',
            'modules.*.content.*.title' => 'required|string|max:255',
            'modules.*.content.*.content_type' => 'required|in:video,pdf',
            'modules.*.content.*.is_required' => 'boolean',
            'modules.*.content.*.is_active' => 'boolean',
            'modules.*.content.*.video_id' => 'nullable|exists:videos,id',
            // ✅ FIXED: Add validation for Google Drive PDF URL
            'modules.*.content.*.google_drive_pdf_url' => 'nullable|string|url',
            'modules.*.content.*.pdf_name' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imageData = $this->fileService->uploadCourseImage($request->file('image'));
                $imagePath = $imageData['path'];
                $thumbnails = $this->thumbnailService->generateMultipleThumbnails($imagePath);
            }

            $course = CourseOnline::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'image_path' => $imagePath,
                'estimated_duration' => $validated['estimated_duration'],
                'difficulty_level' => $validated['difficulty_level'],
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['modules'] as $moduleIndex => $moduleData) {
                $module = $course->modules()->create([
                    'name' => $moduleData['name'],
                    'description' => $moduleData['description'],
                    'order_number' => $moduleIndex + 1,
                    'estimated_duration' => $moduleData['estimated_duration'],
                    'is_required' => $moduleData['is_required'] ?? true,
                    'is_active' => $moduleData['is_active'] ?? true,
                ]);

                if (isset($moduleData['content']) && is_array($moduleData['content'])) {
                    foreach ($moduleData['content'] as $contentIndex => $contentData) {
                        $contentFields = [
                            'module_id' => $module->id,
                            'content_type' => $contentData['content_type'],
                            'title' => $contentData['title'],
                            'order_number' => $contentIndex + 1,
                            'is_required' => $contentData['is_required'] ?? true,
                            'is_active' => $contentData['is_active'] ?? true,
                        ];

                        // ✅ FIXED: Handle video content
                        if ($contentData['content_type'] === 'video' && !empty($contentData['video_id'])) {
                            $video = Video::find($contentData['video_id']);
                            if ($video) {
                                $contentFields['video_id'] = $video->id;

                                if (!$video->streaming_url) {
                                    $streamingUrl = $this->googleDriveService->processUrl($video->google_drive_url);
                                    if ($streamingUrl) {
                                        $video->update(['streaming_url' => $streamingUrl]);
                                    }
                                }
                            }
                        }

                        // ✅ FIXED: Handle PDF content (both file upload and Google Drive)
                        if ($contentData['content_type'] === 'pdf') {
                            $fileKey = "modules.{$moduleIndex}.content.{$contentIndex}.pdf_file";

                            // Priority 1: Check for uploaded file
                            if ($request->hasFile($fileKey)) {
                                Log::info('📄 Processing uploaded PDF file', [
                                    'file_key' => $fileKey,
                                    'course_id' => $course->id,
                                ]);

                                $pdfData = $this->fileService->uploadCoursePdf(
                                    $request->file($fileKey),
                                    $course->id
                                );
                                $contentFields['file_path'] = $pdfData['path'];
                                $contentFields['pdf_name'] = $contentData['pdf_name'] ?? $pdfData['original_name'] ?? 'PDF Document';
                            }
                            // Priority 2: Check for Google Drive URL
                            elseif (!empty($contentData['google_drive_pdf_url'])) {
                                Log::info('📄 Processing Google Drive PDF URL', [
                                    'url' => $contentData['google_drive_pdf_url'],
                                    'course_id' => $course->id,
                                ]);

                                // ✅ FIXED: Process Google Drive URL properly
                                $processedUrl = $this->processGoogleDrivePdfUrl($contentData['google_drive_pdf_url']);

                                $contentFields['google_drive_pdf_url'] = $processedUrl;
                                $contentFields['pdf_name'] = $contentData['pdf_name'] ?? 'PDF Document';

                                Log::info('📄 Google Drive PDF processed', [
                                    'original_url' => $contentData['google_drive_pdf_url'],
                                    'processed_url' => $processedUrl,
                                    'pdf_name' => $contentFields['pdf_name'],
                                ]);
                            }
                            // No PDF source provided - this might be an error
                            else {
                                Log::warning('📄 PDF content with no source', [
                                    'module_index' => $moduleIndex,
                                    'content_index' => $contentIndex,
                                    'title' => $contentData['title'],
                                ]);
                            }
                        }

                        // ✅ Create the content record
                        $createdContent = ModuleContent::create($contentFields);

                        Log::info('📄 Content created successfully', [
                            'content_id' => $createdContent->id,
                            'content_type' => $createdContent->content_type,
                            'title' => $createdContent->title,
                            'has_file_path' => !empty($createdContent->file_path),
                            'has_google_drive_url' => !empty($createdContent->google_drive_pdf_url),
                        ]);
                    }
                }
            }

            DB::commit();

            Log::info('📄 Course created successfully', [
                'course_id' => $course->id,
                'course_name' => $course->name,
            ]);

            return redirect()->route('admin.course-online.index')
                ->with('success', 'Course created successfully with all modules and content!');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('📄 Course creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($imagePath) {
                $this->fileService->deleteFile($imagePath);
                $this->thumbnailService->deleteThumbnails($imagePath);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create course: ' . $e->getMessage());
        }
    }

    /**
     * ✅ NEW: Process Google Drive PDF URL to make it viewable
     */
    private function processGoogleDrivePdfUrl($url)
    {
        if (empty($url)) {
            return null;
        }

        // ✅ Extract file ID from different Google Drive URL formats
        $patterns = [
            '/\/file\/d\/([a-zA-Z0-9-_]+)/',           // /file/d/FILE_ID/
            '/id=([a-zA-Z0-9-_]+)/',                  // ?id=FILE_ID
            '/\/open\?id=([a-zA-Z0-9-_]+)/',          // /open?id=FILE_ID
            '/\/view\?id=([a-zA-Z0-9-_]+)/',          // /view?id=FILE_ID
        ];

        $fileId = null;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                $fileId = $matches[1];
                break;
            }
        }

        if (!$fileId) {
            Log::warning('📄 Could not extract file ID from Google Drive URL', ['url' => $url]);
            return $url; // Return original URL if we can't process it
        }

        // ✅ Convert to viewable/embeddable URL
        $viewUrl = "https://drive.google.com/file/d/{$fileId}/preview";

        Log::info('📄 Google Drive URL processed', [
            'original' => $url,
            'file_id' => $fileId,
            'processed' => $viewUrl,
        ]);

        return $viewUrl;
    }
    /**
     * Display the specified course
     */
    public function show(CourseOnline $courseOnline)
    {
        $courseOnline->load([
            'creator',
            'modules.content.video',
            'assignments.user',
        ]);

        $analytics = $courseOnline->getAnalytics();

        $thumbnails = [];
        if ($courseOnline->image_path) {
            $thumbnails = [
                'small' => $this->thumbnailService->getThumbnailUrl($courseOnline->image_path, 'small'),
                'medium' => $this->thumbnailService->getThumbnailUrl($courseOnline->image_path, 'medium'),
                'large' => $this->thumbnailService->getThumbnailUrl($courseOnline->image_path, 'large'),
            ];
        }

        return Inertia::render('Admin/CourseOnline/Show', [
            'course' => [
                'id' => $courseOnline->id,
                'name' => $courseOnline->name,
                'description' => $courseOnline->description,
                'image_path' => $courseOnline->image_path ? asset('storage/' . $courseOnline->image_path) : null,
                'thumbnails' => $thumbnails,
                'difficulty_level' => $courseOnline->difficulty_level,
                'estimated_duration' => $courseOnline->estimated_duration,
                'is_active' => $courseOnline->is_active,
                'creator' => $courseOnline->creator ? [
                    'id' => $courseOnline->creator->id,
                    'name' => $courseOnline->creator->name,
                ] : null,
                'modules' => $courseOnline->modules->map(fn($module) => [
                    'id' => $module->id,
                    'name' => $module->name,
                    'description' => $module->description,
                    'order_number' => $module->order_number,
                    'content' => $module->content->map(function($content) {
                        return [
                            'id' => $content->id,
                            'title' => $content->title,
                            'content_type' => $content->content_type,
                            'video' => $content->video ? [
                                'id' => $content->video->id,
                                'name' => $content->video->name,
                                'duration' => $content->video->duration,
                                'streaming_url' => $this->googleDriveService->processUrl($content->video->google_drive_url),
                                'thumbnail_url' => $content->video->thumbnail_url,
                            ] : null,
                            'pdf_name' => $content->pdf_name ?? 'PDF Document',
                            'pdf_info' => $content->file_path ? $this->fileService->getFileInfo($content->file_path) : null,
                        ];
                    }),
                ]),
                'assignments' => $courseOnline->assignments->map(fn($assignment) => [
                    'id' => $assignment->id,
                    'user' => [
                        'id' => $assignment->user->id,
                        'name' => $assignment->user->name,
                        'email' => $assignment->user->email,
                    ],
                    'status' => $assignment->status,
                    'progress_percentage' => $assignment->progress_percentage,
                    'assigned_at' => $assignment->assigned_at->toDateTimeString(),
                    'started_at' => $assignment->started_at?->toDateTimeString(),
                    'completed_at' => $assignment->completed_at?->toDateTimeString(),
                ]),
                'analytics' => [
                    'total_enrollments' => $analytics->total_enrollments,
                    'completion_rate' => $analytics->completion_rate,
                    'average_session_duration' => $analytics->average_session_duration_minutes,
                    'engagement_score' => $analytics->engagement_score,
                ],
                'created_at' => $courseOnline->created_at->toDateTimeString(),
            ]
        ]);
    }

    /**
     * Show form for editing course
     */
    public function edit(CourseOnline $courseOnline)
    {
        $thumbnails = [];
        if ($courseOnline->image_path) {
            $thumbnails = [
                'small' => $this->thumbnailService->getThumbnailUrl($courseOnline->image_path, 'small'),
                'medium' => $this->thumbnailService->getThumbnailUrl($courseOnline->image_path, 'medium'),
                'large' => $this->thumbnailService->getThumbnailUrl($courseOnline->image_path, 'large'),
            ];
        }

        return Inertia::render('Admin/CourseOnline/Edit', [
            'course' => [
                'id' => $courseOnline->id,
                'name' => $courseOnline->name,
                'description' => $courseOnline->description,
                'image_path' => $courseOnline->image_path ? asset('storage/' . $courseOnline->image_path) : null,
                'thumbnails' => $thumbnails,
                'difficulty_level' => $courseOnline->difficulty_level,
                'estimated_duration' => $courseOnline->estimated_duration,
                'is_active' => $courseOnline->is_active,
            ]
        ]);
    }

    /**
     * Update specified course
     */
    public function update(Request $request, CourseOnline $courseOnline)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:course_online,name,' . $courseOnline->id,
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'estimated_duration' => 'nullable|integer|min:1|max:10000',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'is_active' => 'boolean',
            'remove_image' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            $imagePath = $courseOnline->image_path;

            if ($request->boolean('remove_image')) {
                if ($imagePath) {
                    $this->fileService->deleteFile($imagePath);
                    $this->thumbnailService->deleteThumbnails($imagePath);
                }
                $imagePath = null;
            }

            if ($request->hasFile('image')) {
                if ($imagePath) {
                    $this->fileService->deleteFile($imagePath);
                    $this->thumbnailService->deleteThumbnails($imagePath);
                }

                $imageData = $this->fileService->uploadCourseImage(
                    $request->file('image'),
                    $courseOnline->id
                );
                $imagePath = $imageData['path'];
                $this->thumbnailService->generateMultipleThumbnails($imagePath);
            }

            $courseOnline->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'image_path' => $imagePath,
                'estimated_duration' => $validated['estimated_duration'],
                'difficulty_level' => $validated['difficulty_level'],
                'is_active' => $validated['is_active'] ?? $courseOnline->is_active,
            ]);

            DB::commit();

            return redirect()->route('admin.course-online.index')
                ->with('success', 'Course updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update course. Please try again.');
        }
    }

    /**
     * Remove specified course
     */
    public function destroy(CourseOnline $courseOnline)
    {
        $assignmentCount = $courseOnline->assignments()->count();
        if ($assignmentCount > 0) {
            return redirect()->route('admin.course-online.index')
                ->with('error', "Cannot delete course. It has {$assignmentCount} assignments.");
        }

        DB::beginTransaction();

        try {
            $imagePath = $courseOnline->image_path;
            $courseName = $courseOnline->name;

            $pdfFiles = ModuleContent::where('content_type', 'pdf')
                ->whereHas('module', function($query) use ($courseOnline) {
                    $query->where('course_online_id', $courseOnline->id);
                })
                ->whereNotNull('file_path')
                ->pluck('file_path')
                ->toArray();

            $courseOnline->delete();

            if ($imagePath) {
                $this->fileService->deleteFile($imagePath);
                $this->thumbnailService->deleteThumbnails($imagePath);
            }

            foreach ($pdfFiles as $pdfPath) {
                $this->fileService->deleteFile($pdfPath);
            }

            DB::commit();

            return redirect()->route('admin.course-online.index')
                ->with('success', 'Course deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()
                ->with('error', 'Failed to delete course. Please try again.');
        }
    }

    /**
     * Toggle course active status
     */
    public function toggleActive(CourseOnline $courseOnline)
    {
        $courseOnline->update([
            'is_active' => !$courseOnline->is_active
        ]);

        return redirect()->back()
            ->with('success', 'Course status updated successfully.');
    }

    /**
     * Refresh all video streaming URLs in course
     */
    public function refreshVideoUrls(CourseOnline $courseOnline)
    {
        try {
            $updatedCount = 0;

            $videos = Video::whereHas('moduleContent', function($query) use ($courseOnline) {
                $query->whereHas('module', function($subQuery) use ($courseOnline) {
                    $subQuery->where('course_online_id', $courseOnline->id);
                });
            })->get();

            foreach ($videos as $video) {
                $newStreamingUrl = $this->googleDriveService->processUrl($video->google_drive_url);
                if ($newStreamingUrl && $newStreamingUrl !== $video->streaming_url) {
                    $video->update(['streaming_url' => $newStreamingUrl]);
                    $updatedCount++;
                }
            }

            return redirect()->back()
                ->with('success', "Refreshed {$updatedCount} video streaming URLs.");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to refresh video URLs. Please try again.');
        }
    }

    /**
     * Get course statistics
     */
    public function statistics(CourseOnline $courseOnline)
    {
        $analytics = $courseOnline->getAnalytics();

        return response()->json([
            'success' => true,
            'statistics' => [
                'total_enrollments' => $analytics->total_enrollments,
                'completion_rate' => $analytics->completion_rate,
                'average_session_duration' => $analytics->average_session_duration_minutes,
                'engagement_score' => $analytics->engagement_score,
                'cheating_incidents' => $analytics->cheating_incidents_count,
                'last_updated' => $analytics->last_calculated_at?->toDateTimeString(),
            ]
        ]);
    }
}
