<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Services\GoogleDriveService;
use App\Services\ThumbnailService;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\VideoCategory; // ✅ ADD THIS LINE


class VideoController extends Controller
{
    // ✅ INJECT ALL THREE SERVICES
    public function __construct(
        protected GoogleDriveService $googleDriveService,
        protected ThumbnailService $thumbnailService,
        protected FileUploadService $fileService
    ) {}

    /**
     * Display a listing of videos
     */
    public function index()
    {
        $videos = Video::with(['creator', 'category']) // ✅ Load category
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($video) {
                return [
                    'id' => $video->id,
                    'name' => $video->name,
                    'description' => $video->description,
                    'duration' => $video->duration,
                    'formatted_duration' => $video->formatted_duration,
                    'streaming_url' => $video->streaming_url,
                    'google_drive_url' => $video->google_drive_url,
                    'is_active' => $video->is_active,
                    'category' => $video->category ? [ // ✅ Include category
                        'id' => $video->category->id,
                        'name' => $video->category->name,
                    ] : null,
                    'creator' => [
                        'id' => $video->creator->id,
                        'name' => $video->creator->name,
                    ],
                    'created_at' => $video->created_at->toDateTimeString(),
                ];
            });

        $categories = VideoCategory::active()
            ->ordered()
            ->get()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
            ]);
        return Inertia::render('Admin/Video/Index', [
            'videos' => $videos,
            'categories' => $categories,
        ]);
    }


    /**
     * Show the form for creating a new video
     */
    public function create()
    {
        $categories = VideoCategory::active()
            ->ordered()
            ->get()
            ->map(fn($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
            ]);

        return Inertia::render('Admin/Video/Create', [
            'categories' => $categories,
        ]);
    }


    /**
     * Store a newly created video
     */
   public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'google_drive_url' => 'required|url|max:500',
            'duration' => 'nullable|integer|min:1|max:86400',
            'content_category_id' => 'nullable|exists:content_categories,id', // ✅ Validate category
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            $streamingUrl = $this->googleDriveService->processUrl($validated['google_drive_url']);

            if (!$streamingUrl) {
                throw new \Exception('Could not process Google Drive URL.');
            }

            $videoData = [
                'name' => $validated['name'],
                'description' => $validated['description'],
                'google_drive_url' => $validated['google_drive_url'],
                'streaming_url' => $streamingUrl,
                'duration' => $validated['duration'],
                'content_category_id' => $validated['content_category_id'], // ✅ Save category
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => auth()->id(),
            ];

            $video = Video::create($videoData);

            DB::commit();

            Log::info('Video created successfully', [
                'video_id' => $video->id,
                'category_id' => $video->content_category_id,
            ]);

            return redirect()->route('admin.videos.index')
                ->with('success', 'Video created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create video', ['error' => $e->getMessage()]);

            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }
    /**
     * Display the specified video
     */
    public function show(Video $video)
    {
        $video->load(['creator']);

        // ✅ ENSURE FRESH STREAMING URL
        $currentStreamingUrl = $this->googleDriveService->processUrl($video->google_drive_url);
        if ($currentStreamingUrl && $currentStreamingUrl !== $video->streaming_url) {
            $video->update(['streaming_url' => $currentStreamingUrl]);
        }

        return Inertia::render('Admin/Video/Show', [
            'video' => [
                'id' => $video->id,
                'name' => $video->name,
                'description' => $video->description,
                'google_drive_url' => $video->google_drive_url,
                'streaming_url' => $currentStreamingUrl,
                'duration' => $video->duration,
                'formatted_duration' => $video->formatted_duration,
                'thumbnail_url' => null,
                'is_active' => $video->is_active,
                'creator' => [
                    'id' => $video->creator->id,
                    'name' => $video->creator->name,
                ],
                'created_at' => $video->created_at->toDateTimeString(),
            ],
            'analytics' => [
                'total_viewers' => 0,
                'completed_viewers' => 0,
                'completion_rate' => 0,
                'avg_progress' => 0,
                'total_watch_hours' => 0,
            ],
            'recent_activity' => [],
        ]);
    }

    /**
     * Show the form for editing the specified video
     */
   public function edit(Video $video)
{
    $categories = VideoCategory::active()
        ->ordered()
        ->get()
        ->map(fn($cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
        ]);

    return Inertia::render('Admin/Video/Edit', [
        'video' => [
            'id' => $video->id,
            'name' => $video->name,
            'description' => $video->description,
            'google_drive_url' => $video->google_drive_url,
            'duration' => $video->duration,
            'thumbnail_url' => $video->thumbnail_url, // ✅ Add this
            'content_category_id' => $video->content_category_id,
            'is_active' => $video->is_active,
            'total_viewers' => 0, // ✅ Add this (or calculate from progress)
            'avg_completion' => 0, // ✅ Add this (or calculate from progress)
        ],
        'categories' => $categories,
    ]);
}
    /**
     * Update the specified video
     */
    public function update(Request $request, Video $video)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'google_drive_url' => 'required|url|max:500',
            'duration' => 'nullable|integer|min:1|max:86400',
            'content_category_id' => 'nullable|exists:content_categories,id', // ✅ Validate category
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            $updateData = [
                'name' => $validated['name'],
                'description' => $validated['description'],
                'google_drive_url' => $validated['google_drive_url'],
                'duration' => $validated['duration'],
                'content_category_id' => $validated['content_category_id'], // ✅ Update category
                'is_active' => $validated['is_active'] ?? true,
            ];

            // Refresh streaming URL if needed
            if ($video->google_drive_url !== $validated['google_drive_url']) {
                $newStreamingUrl = $this->googleDriveService->processUrl($validated['google_drive_url']);
                if ($newStreamingUrl) {
                    $updateData['streaming_url'] = $newStreamingUrl;
                }
            }

            $video->update($updateData);

            DB::commit();

            return redirect()->route('admin.videos.index')
                ->with('success', 'Video updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update video', ['error' => $e->getMessage()]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update video.');
        }
    }
    /**
     * Remove the specified video
     */
    public function destroy(Video $video)
    {
        try {
            $videoName = $video->name;
            $video->delete();

            Log::info('Video deleted', [
                'video_name' => $videoName,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()->route('admin.videos.index')
                ->with('success', 'Video deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete video', [
                'video_id' => $video->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete video. Please try again.');
        }
    }

    /**
     * Toggle active status
     */
    public function toggleActive(Video $video)
    {
        $video->update(['is_active' => !$video->is_active]);
        $status = $video->is_active ? 'activated' : 'deactivated';

        Log::info("Video {$status}", [
            'video_id' => $video->id,
            'video_name' => $video->name,
            'is_active' => $video->is_active,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', "Video {$status} successfully.");
    }

    /**
     * Get fresh streaming URL for video
     */
    public function getStreamingUrl(Video $video)
    {
        try {
            $streamingUrl = $this->googleDriveService->processUrl($video->google_drive_url);

            if ($streamingUrl) {
                $video->update(['streaming_url' => $streamingUrl]);

                return response()->json([
                    'success' => true,
                    'streaming_url' => $streamingUrl,
                    'updated_at' => now()->toISOString()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Could not generate streaming URL'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Failed to get streaming URL', [
                'video_id' => $video->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }
}
