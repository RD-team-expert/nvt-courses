<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseOnline;
use App\Models\CourseModule;
use App\Models\ModuleContent;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ModuleContentController extends Controller
{
    /**
     * Display a listing of content for a module
     */
    public function index(CourseOnline $courseOnline, CourseModule $courseModule)
    {
        $content = $courseModule->content()
            ->with(['video', 'userProgress'])
            ->orderBy('order_number')
            ->get();

        return Inertia::render('Admin/ModuleContent/Index', [
            'course' => [
                'id' => $courseOnline->id,
                'name' => $courseOnline->name,
            ],
            'module' => [
                'id' => $courseModule->id,
                'name' => $courseModule->name,
                'description' => $courseModule->description,
            ],
            'content' => $content->map(fn($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'content_type' => $item->content_type,
                'order_number' => $item->order_number,
                'duration' => $item->duration,
                'formatted_duration' => $item->formatted_duration,
                'is_required' => $item->is_required,
                'is_active' => $item->is_active,
                'video' => $item->video ? [
                    'id' => $item->video->id,
                    'name' => $item->video->name,
                    'google_drive_url' => $item->video->google_drive_url,
                    'thumbnail_url' => $item->video->thumbnail_url,
                ] : null,
                'pdf_name' => $item->pdf_name,
                'content_url' => $item->content_url,
                'thumbnail_url' => $item->thumbnail_url,
            ]),
        ]);
    }

    /**
     * Show the form for creating new content
     */
    public function create(CourseOnline $courseOnline, CourseModule $courseModule)
    {
        // Get next order number
        $nextOrderNumber = $courseModule->content()->max('order_number') + 1;

        // Get available videos (not already assigned to this module)
        $assignedVideoIds = ModuleContent::where('course_module_id', $courseModule->id)
            ->where('content_type', 'video')
            ->pluck('video_id')
            ->toArray();

        $availableVideos = Video::whereNotIn('id', $assignedVideoIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($video) => [
                'id' => $video->id,
                'name' => $video->name,
                'description' => $video->description,
                'duration' => $video->duration,
                'formatted_duration' => $video->formatted_duration,
                'google_drive_url' => $video->google_drive_url,
                'thumbnail_url' => $video->thumbnail_url,
            ]);

        return Inertia::render('Admin/ModuleContent/Create', [
            'course' => [
                'id' => $courseOnline->id,
                'name' => $courseOnline->name,
            ],
            'module' => [
                'id' => $courseModule->id,
                'name' => $courseModule->name,
            ],
            'nextOrderNumber' => $nextOrderNumber,
            'availableVideos' => $availableVideos,
        ]);
    }

    /**
     * Store newly created content
     */
    public function store(Request $request, CourseOnline $courseOnline, CourseModule $courseModule)
    {
        $validated = $request->validate([
            'content_type' => 'required|in:video,pdf',
            'title' => 'required|string|max:255',
            'order_number' => 'required|integer|min:1',
            'is_required' => 'boolean',
            'is_active' => 'boolean',

            // Video fields
            'video_id' => 'required_if:content_type,video|exists:videos,id',

            // PDF fields
            'pdf_file' => 'required_if:content_type,pdf|file|mimes:pdf|max:10240', // 10MB max
            'pdf_name' => 'required_if:content_type,pdf|string|max:255',
        ]);

        // Handle order number conflicts
        $existingContent = $courseModule->content()
            ->where('order_number', $validated['order_number'])
            ->first();

        if ($existingContent) {
            // Shift other content up
            $courseModule->content()
                ->where('order_number', '>=', $validated['order_number'])
                ->increment('order_number');
        }

        $contentData = [
            'course_module_id' => $courseModule->id,
            'content_type' => $validated['content_type'],
            'title' => $validated['title'],
            'order_number' => $validated['order_number'],
            'is_required' => $validated['is_required'] ?? true,
            'is_active' => $validated['is_active'] ?? true,
        ];

        if ($validated['content_type'] === 'video') {
            $video = Video::find($validated['video_id']);
            $contentData['video_id'] = $video->id;
            $contentData['duration'] = $video->duration;
        } else {
            // Handle PDF upload
            $pdfPath = $request->file('pdf_file')->store('course-content/pdfs', 'public');
            $contentData['pdf_name'] = $validated['pdf_name'];
            $contentData['pdf_path'] = $pdfPath;
        }

        $content = ModuleContent::create($contentData);

        Log::info('Module content created', [
            'content_id' => $content->id,
            'module_id' => $courseModule->id,
            'course_id' => $courseOnline->id,
            'content_type' => $content->content_type,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.module-content.index', [$courseOnline, $courseModule])
            ->with('success', 'Content added successfully.');
    }

    /**
     * Display specific content
     */
    public function show(CourseOnline $courseOnline, CourseModule $courseModule, ModuleContent $moduleContent)
    {
        $moduleContent->load(['video', 'userProgress.user', 'learningSessions.user']);

        return Inertia::render('Admin/ModuleContent/Show', [
            'course' => [
                'id' => $courseOnline->id,
                'name' => $courseOnline->name,
            ],
            'module' => [
                'id' => $courseModule->id,
                'name' => $courseModule->name,
            ],
            'content' => [
                'id' => $moduleContent->id,
                'title' => $moduleContent->title,
                'content_type' => $moduleContent->content_type,
                'order_number' => $moduleContent->order_number,
                'duration' => $moduleContent->duration,
                'formatted_duration' => $moduleContent->formatted_duration,
                'is_required' => $moduleContent->is_required,
                'is_active' => $moduleContent->is_active,
                'video' => $moduleContent->video ? [
                    'id' => $moduleContent->video->id,
                    'name' => $moduleContent->video->name,
                    'google_drive_url' => $moduleContent->video->google_drive_url,
                    'thumbnail_url' => $moduleContent->video->thumbnail_url,
                ] : null,
                'pdf_name' => $moduleContent->pdf_name,
                'content_url' => $moduleContent->content_url,
                'engagement_score' => $moduleContent->getAverageEngagementScore(),
                'suspicious_activity_count' => $moduleContent->getSuspiciousActivityCount(),
            ],
            'userProgress' => $moduleContent->userProgress->map(fn($progress) => [
                'user' => [
                    'id' => $progress->user->id,
                    'name' => $progress->user->name,
                ],
                'completion_percentage' => $progress->completion_percentage,
                'is_completed' => $progress->is_completed,
                'engagement_level' => $progress->getEngagementLevel(),
                'time_spent' => $progress->formatted_time_spent,
                'last_accessed' => $progress->last_accessed_at?->toDateTimeString(),
            ]),
        ]);
    }

    /**
     * Show the form for editing content
     */
    public function edit(CourseOnline $courseOnline, CourseModule $courseModule, ModuleContent $moduleContent)
    {
        $moduleContent->load('video');

        // Get available videos if editing video content
        $availableVideos = [];
        if ($moduleContent->content_type === 'video') {
            $assignedVideoIds = ModuleContent::where('course_module_id', $courseModule->id)
                ->where('content_type', 'video')
                ->where('id', '!=', $moduleContent->id) // Exclude current content
                ->pluck('video_id')
                ->toArray();

            $availableVideos = Video::whereNotIn('id', $assignedVideoIds)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn($video) => [
                    'id' => $video->id,
                    'name' => $video->name,
                    'description' => $video->description,
                    'duration' => $video->duration,
                    'formatted_duration' => $video->formatted_duration,
                ]);
        }

        return Inertia::render('Admin/ModuleContent/Edit', [
            'course' => [
                'id' => $courseOnline->id,
                'name' => $courseOnline->name,
            ],
            'module' => [
                'id' => $courseModule->id,
                'name' => $courseModule->name,
            ],
            'content' => [
                'id' => $moduleContent->id,
                'title' => $moduleContent->title,
                'content_type' => $moduleContent->content_type,
                'order_number' => $moduleContent->order_number,
                'is_required' => $moduleContent->is_required,
                'is_active' => $moduleContent->is_active,
                'video_id' => $moduleContent->video_id,
                'pdf_name' => $moduleContent->pdf_name,
            ],
            'availableVideos' => $availableVideos,
        ]);
    }

    /**
     * Update content
     */
    public function update(Request $request, CourseOnline $courseOnline, CourseModule $courseModule, ModuleContent $moduleContent)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'order_number' => 'required|integer|min:1',
            'is_required' => 'boolean',
            'is_active' => 'boolean',

            // Video fields (only if video content)
            'video_id' => $moduleContent->content_type === 'video' ? 'required|exists:videos,id' : '',

            // PDF fields (only if pdf content and new file uploaded)
            'pdf_file' => 'nullable|file|mimes:pdf|max:10240',
            'pdf_name' => $moduleContent->content_type === 'pdf' ? 'required|string|max:255' : '',
        ]);

        // Handle order number changes
        if ($moduleContent->order_number !== $validated['order_number']) {
            $this->reorderContent($courseModule, $moduleContent, $validated['order_number']);
        }

        $updateData = [
            'title' => $validated['title'],
            'order_number' => $validated['order_number'],
            'is_required' => $validated['is_required'] ?? $moduleContent->is_required,
            'is_active' => $validated['is_active'] ?? $moduleContent->is_active,
        ];

        if ($moduleContent->content_type === 'video' && isset($validated['video_id'])) {
            $video = Video::find($validated['video_id']);
            $updateData['video_id'] = $video->id;
            $updateData['duration'] = $video->duration;
        }

        if ($moduleContent->content_type === 'pdf') {
            $updateData['pdf_name'] = $validated['pdf_name'];

            // Handle new PDF upload
            if ($request->hasFile('pdf_file')) {
                // Delete old PDF
                if ($moduleContent->pdf_path) {
                    Storage::disk('public')->delete($moduleContent->pdf_path);
                }

                // Store new PDF
                $updateData['pdf_path'] = $request->file('pdf_file')->store('course-content/pdfs', 'public');
            }
        }

        $moduleContent->update($updateData);

        Log::info('Module content updated', [
            'content_id' => $moduleContent->id,
            'module_id' => $courseModule->id,
            'course_id' => $courseOnline->id,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('admin.module-content.index', [$courseOnline, $courseModule])
            ->with('success', 'Content updated successfully.');
    }

    /**
     * Remove content
     */
    public function destroy(CourseOnline $courseOnline, CourseModule $courseModule, ModuleContent $moduleContent)
    {
        // Check if content has user progress
        $progressCount = $moduleContent->userProgress()->count();
        if ($progressCount > 0) {
            return redirect()->route('admin.module-content.index', [$courseOnline, $courseModule])
                ->with('error', "Cannot delete content. It has {$progressCount} user progress records.");
        }

        $title = $moduleContent->title;
        $orderNumber = $moduleContent->order_number;

        // Delete PDF file if exists
        if ($moduleContent->content_type === 'pdf' && $moduleContent->pdf_path) {
            Storage::disk('public')->delete($moduleContent->pdf_path);
        }

        $moduleContent->delete();

        // Reorder remaining content
        $courseModule->content()
            ->where('order_number', '>', $orderNumber)
            ->decrement('order_number');

        Log::info('Module content deleted', [
            'content_title' => $title,
            'module_id' => $courseModule->id,
            'course_id' => $courseOnline->id,
            'deleted_by' => auth()->id(),
        ]);

        return redirect()->route('admin.module-content.index', [$courseOnline, $courseModule])
            ->with('success', 'Content deleted successfully.');
    }

    /**
     * Helper method to reorder content
     */
    private function reorderContent(CourseModule $module, ModuleContent $content, int $newOrder): void
    {
        $oldOrder = $content->order_number;

        if ($newOrder > $oldOrder) {
            // Moving down - decrement items between old and new position
            $module->content()
                ->where('id', '!=', $content->id)
                ->whereBetween('order_number', [$oldOrder + 1, $newOrder])
                ->decrement('order_number');
        } else {
            // Moving up - increment items between new and old position
            $module->content()
                ->where('id', '!=', $content->id)
                ->whereBetween('order_number', [$newOrder, $oldOrder - 1])
                ->increment('order_number');
        }
    }
}
