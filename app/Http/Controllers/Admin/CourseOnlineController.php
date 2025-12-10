<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseOnline;
use App\Models\CourseModule;
use App\Models\ModuleContent;
use App\Models\VideoCategory;
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
                // ✅ NEW: Include deadline information
                'has_deadline' => $course->has_deadline,
                'deadline' => $course->deadline?->toDateTimeString(),
                'deadline_type' => $course->deadline_type,
                'deadline_status' => $course->deadline_status,
                'days_until_deadline' => $course->daysUntilDeadline(),
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
    // Get videos with categories
    $availableVideos = Video::with('category')
        ->where('is_active', true)
        ->orderBy('name')
        ->get()
        ->map(function ($video) {
            return [
                'id' => $video->id,
                'name' => $video->name,
                'formatted_duration' => $video->formatted_duration,
                'thumbnail_url' => $video->thumbnail_url,
                'google_drive_url' => $video->google_drive_url,
                'content_category_id' => $video->content_category_id,
                'category_name' => $video->category?->name,
            ];
        });

    // ✅ NEW: Get video categories with video counts
    $videoCategories = VideoCategory::withCount('videos')
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->get()
        ->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'videos_count' => $category->videos_count,
            ];
        });

    return Inertia::render('Admin/CourseOnline/Create', [
        'availableVideos' => $availableVideos,
        'videoCategories' => $videoCategories,  // ✅ NEW
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'estimated_duration' => 'nullable|integer|min:1|max:10000',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'is_active' => 'boolean',
            // ✅ FIXED: Always required deadline validation
            'deadline' => 'required|date|after:now',
            'deadline_type' => 'required|in:flexible,strict',
            'modules' => 'required|array|min:1',
            'modules.*.name' => 'required|string|max:255',
            'modules.*.description' => 'nullable|string|max:2000',
            'modules.*.estimated_duration' => 'nullable|integer|min:1|max:1000',
            'modules.*.is_required' => 'boolean',
            'modules.*.is_active' => 'boolean',
            'modules.*.content' => 'nullable|array',
            'modules.*.content.*.title' => 'nullable|string|max:255',
            'modules.*.content.*.content_type' => 'nullable|in:video,pdf',
            'modules.*.content.*.is_required' => 'boolean',
            'modules.*.content.*.is_active' => 'boolean',
            'modules.*.content.*.video_id' => 'nullable|exists:videos,id',
            'modules.*.content.*.google_drive_pdf_url' => 'nullable|string|url',
            'modules.*.content.*.pdf_name' => 'nullable|string|max:255',
            'modules.*.content.*.pdf_page_count' => 'nullable|integer|min:1|max:1000',
        ]);

        // ✅ CUSTOM: Smart validation after basic validation
        foreach ($validated['modules'] as $moduleIndex => $moduleData) {
            if (isset($moduleData['content']) && is_array($moduleData['content'])) {
                foreach ($moduleData['content'] as $contentIndex => $contentData) {
                    // Skip empty content items
                    if (empty($contentData['title']) && empty($contentData['content_type'])) {
                        continue;
                    }

                    // If content has data, validate it properly
                    if (!empty($contentData['title']) && !empty($contentData['content_type'])) {

                        // ✅ VIDEO VALIDATION: Only when content_type is 'video'
                        if ($contentData['content_type'] === 'video') {
                            if (empty($contentData['video_id'])) {
                                return redirect()->back()
                                    ->withInput()
                                    ->withErrors([
                                        "modules.{$moduleIndex}.content.{$contentIndex}.video_id" =>
                                            "Please select a video for Module " . ($moduleIndex + 1) . ", Content " . ($contentIndex + 1)
                                    ]);
                            }
                        }

                        // ✅ PDF VALIDATION: Only when content_type is 'pdf'
                        if ($contentData['content_type'] === 'pdf') {
                            // Check if page count is provided
                            if (empty($contentData['pdf_page_count']) || $contentData['pdf_page_count'] < 1) {
                                return redirect()->back()
                                    ->withInput()
                                    ->withErrors([
                                        "modules.{$moduleIndex}.content.{$contentIndex}.pdf_page_count" =>
                                            "PDF page count is required for Module " . ($moduleIndex + 1) . ", Content " . ($contentIndex + 1)
                                    ]);
                            }

                            // Check if PDF source is provided
                            $hasUpload = $request->hasFile("modules.{$moduleIndex}.content.{$contentIndex}.pdf_file");
                            $hasGoogleDrive = !empty($contentData['google_drive_pdf_url']);

                            if (!$hasUpload && !$hasGoogleDrive) {
                                return redirect()->back()
                                    ->withInput()
                                    ->withErrors([
                                        "modules.{$moduleIndex}.content.{$contentIndex}.pdf_source" =>
                                            "Please upload a PDF file or provide a Google Drive URL for Module " . ($moduleIndex + 1) . ", Content " . ($contentIndex + 1)
                                    ]);
                            }
                        }
                    }
                }
            }
        }

        DB::beginTransaction();

        try {
            // Handle course image upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imageData = $this->fileService->uploadCourseImage($request->file('image'));
                $imagePath = $imageData['path'];
                $this->thumbnailService->generateMultipleThumbnails($imagePath);
            }

            // ✅ FIXED: Create course with always-set deadline
            $course = CourseOnline::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'image_path' => $imagePath,
                'estimated_duration' => $validated['estimated_duration'],
                'difficulty_level' => $validated['difficulty_level'],
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => auth()->id(),
                // ✅ FIXED: Always set deadline fields
                'has_deadline' => true,  // ALWAYS TRUE
                'deadline' => $validated['deadline'],  // ALWAYS SET (required in validation)
                'deadline_type' => $validated['deadline_type'],  // ALWAYS SET (required in validation)
            ]);

            // Create modules and content
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

                        // Handle video content
                        if ($contentData['content_type'] === 'video' && !empty($contentData['video_id'])) {
                            $video = Video::find($contentData['video_id']);
                            if ($video) {
                                $contentFields['video_id'] = $video->id;
                            }
                        }

                        // ✅ SIMPLIFIED: Handle PDF content - admin provides page count
                        if ($contentData['content_type'] === 'pdf') {
                            $fileKey = "modules.{$moduleIndex}.content.{$contentIndex}.pdf_file";

                            // Handle uploaded file
                            if ($request->hasFile($fileKey)) {
                                $pdfData = $this->fileService->uploadCoursePdf(
                                    $request->file($fileKey),
                                    $course->id
                                );

                                $contentFields['file_path'] = $pdfData['path'];
                                $contentFields['pdf_name'] = $contentData['pdf_name'] ?? $pdfData['original_name'] ?? 'PDF Document';
                            }
                            // Handle Google Drive URL
                            elseif (!empty($contentData['google_drive_pdf_url'])) {
                                $processedUrl = $this->processGoogleDrivePdfUrl($contentData['google_drive_pdf_url']);
                                $contentFields['google_drive_pdf_url'] = $processedUrl;
                                $contentFields['pdf_name'] = $contentData['pdf_name'] ?? 'PDF Document';
                            }

                            // ✅ ALWAYS use admin-provided page count
                            $contentFields['pdf_page_count'] = $contentData['pdf_page_count'];


                        }

                        // Create the content record
                        ModuleContent::create($contentFields);
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.course-online.index')
                ->with('success', 'Course created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();



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
            return $url; // Return original URL if we can't process it
        }

        // ✅ Convert to viewable/embeddable URL
        $viewUrl = "https://drive.google.com/file/d/{$fileId}/preview";



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
            'modules.quiz',
            'assignments.user',
        ]);

        $analytics = $courseOnline->getAnalytics();
        // Update analytics to get fresh data
        $analytics->updateAnalytics();

        // Check if there's actual session data
        $hasSessionData = \App\Models\LearningSession::where('course_online_id', $courseOnline->id)
            ->whereNotNull('session_end')
            ->where('total_duration_minutes', '>', 0)
            ->exists();

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
                // ✅ NEW: Include deadline information in show view
                'has_deadline' => $courseOnline->has_deadline,
                'deadline' => $courseOnline->deadline?->toDateTimeString(),
                'deadline_type' => $courseOnline->deadline_type,
                'deadline_status' => $courseOnline->deadline_status,
                'days_until_deadline' => $courseOnline->daysUntilDeadline(),
                'is_deadline_passed' => $courseOnline->isDeadlinePassed(),
                'creator' => $courseOnline->creator ? [
                    'id' => $courseOnline->creator->id,
                    'name' => $courseOnline->creator->name,
                ] : null,
                'modules' => $courseOnline->modules->map(fn($module) => [
                    'id' => $module->id,
                    'name' => $module->name,
                    'description' => $module->description,
                    'order_number' => $module->order_number,
                    'has_quiz' => $module->has_quiz,
                    'quiz_required' => $module->quiz_required,
                    'estimated_duration' => $module->estimated_duration,
                    // Calculate content counts
                    'content_count' => $module->content->count(),
                    'video_count' => $module->content->where('content_type', 'video')->count(),
                    'pdf_count' => $module->content->where('content_type', 'pdf')->count(),
                    'quiz' => $module->quiz ? [
                        'id' => $module->quiz->id,
                        'title' => $module->quiz->title,
                        'status' => $module->quiz->status,
                    ] : null,
                    'content' => $module->content->map(function($content) {
                        $streamingUrl = null;
                        if ($content->video && $content->video->google_drive_url) {
                            $streamingUrl = $this->googleDriveService->processUrl($content->video->google_drive_url);
                        }
                        
                        return [
                            'id' => $content->id,
                            'title' => $content->title,
                            'content_type' => $content->content_type,
                            'video' => $content->video ? [
                                'id' => $content->video->id,
                                'name' => $content->video->name,
                                'duration' => $content->video->duration,
                                'streaming_url' => $streamingUrl,
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
                    'created_at' => $assignment->created_at->toDateTimeString(),
                    'assigned_at' => $assignment->assigned_at->toDateTimeString(),
                    'started_at' => $assignment->started_at?->toDateTimeString(),
                    'completed_at' => $assignment->completed_at?->toDateTimeString(),
                    // ✅ NEW: Include assignment deadline info
                    'deadline' => $assignment->deadline?->toDateTimeString(),
                    'is_overdue' => $assignment->is_overdue,
                    'days_until_deadline' => $assignment->daysUntilDeadline(),
                ]),
                'analytics' => [
                    'total_enrollments' => $analytics->total_enrollments,
                    'completion_rate' => $analytics->completion_rate,
                    'average_session_duration' => $analytics->average_session_duration_minutes,
                    'engagement_score' => $analytics->engagement_score,
                ],
                // Analytics data for display
                'avg_engagement' => round($analytics->engagement_score ?? 0, 1),
                'avg_study_time' => $hasSessionData ? $analytics->average_session_duration_minutes : null,
                'has_session_data' => $hasSessionData,
                'success_prediction' => round($analytics->completion_rate ?? 0, 1),
                'enrollment_count' => $analytics->total_enrollments ?? 0,
                'completion_rate' => round($analytics->completion_rate ?? 0, 1),
                'created_at' => $courseOnline->created_at->toDateTimeString(),
            ]
        ]);
    }

    /**
     * Show form for editing course
     */
    public function edit(CourseOnline $courseOnline)
    {
        // Load course with all related data
        $courseOnline->load([
            'modules.content.video',
            'modules' => function($query) {
                $query->orderBy('order_number');
            },
            'modules.content' => function($query) {
                $query->orderBy('order_number');
            }
        ]);

        $thumbnails = [];
        if ($courseOnline->image_path) {
            $thumbnails = [
                'small' => $this->thumbnailService->getThumbnailUrl($courseOnline->image_path, 'small'),
                'medium' => $this->thumbnailService->getThumbnailUrl($courseOnline->image_path, 'medium'),
                'large' => $this->thumbnailService->getThumbnailUrl($courseOnline->image_path, 'large'),
            ];
        }

        // Get available videos for content selection
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
                    'streaming_url' => $video->google_drive_url ? $this->googleDriveService->processUrl($video->google_drive_url) : null,
                    'thumbnail_url' => $video->thumbnail_url,
                ];
            });

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
                // ✅ NEW: Include deadline fields for editing
                'has_deadline' => $courseOnline->has_deadline,
                'deadline' => $courseOnline->deadline?->format('Y-m-d\TH:i'),
                'deadline_type' => $courseOnline->deadline_type,
                // ✅ NEW: Include modules with PDF page count data
                'modules' => $courseOnline->modules->map(function($module) {
                    return [
                        'id' => $module->id,
                        'name' => $module->name,
                        'description' => $module->description,
                        'order_number' => $module->order_number,
                        'estimated_duration' => $module->estimated_duration,
                        'is_required' => $module->is_required,
                        'is_active' => $module->is_active,
                        'content' => $module->content->map(function($content) {
                            $contentData = [
                                'id' => $content->id,
                                'title' => $content->title,
                                'content_type' => $content->content_type,
                                'order_number' => $content->order_number,
                                'is_required' => $content->is_required,
                                'is_active' => $content->is_active,
                            ];

                            // Video specific data
                            if ($content->content_type === 'video') {
                                $contentData['video_id'] = $content->video_id;
                                $contentData['video'] = $content->video ? [
                                    'id' => $content->video->id,
                                    'name' => $content->video->name,
                                    'duration' => $content->video->duration,
                                    'formatted_duration' => gmdate('H:i:s', $content->video->duration),
                                    'thumbnail_url' => $content->video->thumbnail_url,
                                ] : null;
                            }

                            // ✅ PDF specific data with page count
                            if ($content->content_type === 'pdf') {
                                $contentData['pdf_name'] = $content->pdf_name;
                                $contentData['google_drive_pdf_url'] = $content->google_drive_pdf_url;
                                $contentData['file_path'] = $content->file_path;
                                // ✅ NEW: Include PDF page count
                                $contentData['pdf_page_count'] = $content->pdf_page_count;
                                $contentData['pdf_source_type'] = $content->google_drive_pdf_url ? 'google_drive' : 'upload';

                                // Provide file URL for frontend
                                if ($content->file_path) {
                                    $contentData['file_url'] = asset('storage/' . $content->file_path);
                                } elseif ($content->google_drive_pdf_url) {
                                    $contentData['file_url'] = $content->google_drive_pdf_url;
                                }
                            }

                            return $contentData;
                        })
                    ];
                })
            ],
            'availableVideos' => $availableVideos,
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'estimated_duration' => 'nullable|integer|min:1|max:10000',
            'difficulty_level' => 'required|in:beginner,intermediate,advanced',
            'is_active' => 'boolean',
            'remove_image' => 'boolean',
            // ✅ FIXED: Always required deadline validation
            'deadline' => 'required|date|after:now',  // ALWAYS REQUIRED
            'deadline_type' => 'required|in:flexible,strict',  // ALWAYS REQUIRED
            // ✅ Support for updating modules and content
            'modules' => 'nullable|array',
            'modules.*.id' => 'nullable|exists:course_modules,id',
            'modules.*.name' => 'required|string|max:255',
            'modules.*.description' => 'nullable|string|max:2000',
            'modules.*.estimated_duration' => 'nullable|integer|min:1|max:1000',
            'modules.*.is_required' => 'boolean',
            'modules.*.is_active' => 'boolean',
            'modules.*.content' => 'nullable|array',
            'modules.*.content.*.id' => 'nullable|exists:modulecontent,id',
            'modules.*.content.*.title' => 'required|string|max:255',
            'modules.*.content.*.content_type' => 'required|in:video,pdf',
            'modules.*.content.*.is_required' => 'boolean',
            'modules.*.content.*.is_active' => 'boolean',
            'modules.*.content.*.video_id' => 'nullable|exists:videos,id',
            'modules.*.content.*.google_drive_pdf_url' => 'nullable|string|url',
            'modules.*.content.*.pdf_name' => 'nullable|string|max:255',
            'modules.*.content.*.pdf_page_count' => 'nullable|integer|min:1|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $imagePath = $courseOnline->image_path;

            // Handle image removal
            if ($request->boolean('remove_image')) {
                if ($imagePath) {
                    $this->fileService->deleteFile($imagePath);
                    $this->thumbnailService->deleteThumbnails($imagePath);
                }
                $imagePath = null;
            }

            // Handle new image upload
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

            // ✅ FIXED: Update course basic info with always-set deadline
            $courseOnline->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'image_path' => $imagePath,
                'estimated_duration' => $validated['estimated_duration'],
                'difficulty_level' => $validated['difficulty_level'],
                'is_active' => $validated['is_active'] ?? $courseOnline->is_active,
                // ✅ FIXED: Always set deadline fields
                'has_deadline' => true,  // ALWAYS TRUE
                'deadline' => $validated['deadline'],  // ALWAYS SET (required in validation)
                'deadline_type' => $validated['deadline_type'],  // ALWAYS SET (required in validation)
            ]);

            // ✅ FIXED: Always update assignment deadlines (no conditional check)
            $courseOnline->assignments()->update([
                'deadline' => $validated['deadline']
            ]);


            // ✅ REMOVED: dd() debug statement

            // ✅ Handle modules and content updates if provided
            if (isset($validated['modules']) && is_array($validated['modules'])) {
                foreach ($validated['modules'] as $moduleIndex => $moduleData) {
                    // Update or create module
                    $module = null;
                    if (!empty($moduleData['id'])) {
                        $module = CourseModule::find($moduleData['id']);
                        if ($module && $module->course_online_id === $courseOnline->id) {
                            $module->update([
                                'name' => $moduleData['name'],
                                'description' => $moduleData['description'],
                                'order_number' => $moduleIndex + 1,
                                'estimated_duration' => $moduleData['estimated_duration'],
                                'is_required' => $moduleData['is_required'] ?? true,
                                'is_active' => $moduleData['is_active'] ?? true,
                            ]);
                        }
                    } else {
                        $module = $courseOnline->modules()->create([
                            'name' => $moduleData['name'],
                            'description' => $moduleData['description'],
                            'order_number' => $moduleIndex + 1,
                            'estimated_duration' => $moduleData['estimated_duration'],
                            'is_required' => $moduleData['is_required'] ?? true,
                            'is_active' => $moduleData['is_active'] ?? true,
                        ]);
                    }

                    if ($module && isset($moduleData['content']) && is_array($moduleData['content'])) {
                        foreach ($moduleData['content'] as $contentIndex => $contentData) {
                            $contentFields = [
                                'module_id' => $module->id,
                                'content_type' => $contentData['content_type'],
                                'title' => $contentData['title'],
                                'order_number' => $contentIndex + 1,
                                'is_required' => $contentData['is_required'] ?? true,
                                'is_active' => $contentData['is_active'] ?? true,
                            ];

                            // Handle video content
                            if ($contentData['content_type'] === 'video' && !empty($contentData['video_id'])) {
                                $contentFields['video_id'] = $contentData['video_id'];
                            }

                            // ✅ Handle PDF content updates with page count
                            if ($contentData['content_type'] === 'pdf') {
                                if (!empty($contentData['google_drive_pdf_url'])) {
                                    $processedUrl = $this->processGoogleDrivePdfUrl($contentData['google_drive_pdf_url']);
                                    $contentFields['google_drive_pdf_url'] = $processedUrl;
                                    $contentFields['pdf_name'] = $contentData['pdf_name'] ?? 'PDF Document';
                                }

                                // ✅ Update PDF page count
                                if (isset($contentData['pdf_page_count']) && $contentData['pdf_page_count'] > 0) {
                                    $contentFields['pdf_page_count'] = $contentData['pdf_page_count'];

                                }
                            }

                            // Update or create content
                            if (!empty($contentData['id'])) {
                                $content = ModuleContent::find($contentData['id']);
                                if ($content && $content->module_id === $module->id) {
                                    $content->update($contentFields);
                                }
                            } else {
                                ModuleContent::create($contentFields);
                            }
                        }
                    }
                }
            }

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
                // ✅ NEW: Include deadline statistics
                'deadline_info' => [
                    'has_deadline' => $courseOnline->has_deadline,
                    'deadline' => $courseOnline->deadline?->toDateTimeString(),
                    'days_until_deadline' => $courseOnline->daysUntilDeadline(),
                    'is_deadline_passed' => $courseOnline->isDeadlinePassed(),
                    'overdue_assignments' => $courseOnline->assignments()->where('is_overdue', true)->count(),
                ]
            ]
        ]);
    }
}
