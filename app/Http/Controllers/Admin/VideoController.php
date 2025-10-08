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
        $videos = Video::with(['creator'])  // ✅ Removed 'category' relationship
        ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($video) {
                return [
                    'id' => $video->id,
                    'name' => $video->name,
                    'description' => $video->description,
                    'duration' => $video->duration,
                    'formatted_duration' => $video->formatted_duration,
                    'thumbnail_url' => null,  // ✅ Set to null if column doesn't exist
                    'streaming_url' => $video->streaming_url,
                    'google_drive_url' => $video->google_drive_url,
                    'is_active' => $video->is_active,
                    // ✅ REMOVED CATEGORY
                    'creator' => [
                        'id' => $video->creator->id,
                        'name' => $video->creator->name,
                    ],
                    'total_viewers' => 0,  // ✅ Simplified - no progress table
                    'completed_viewers' => 0,
                    'avg_completion' => 0,
                    'created_at' => $video->created_at->toDateTimeString(),
                ];
            });

        // ✅ REMOVED CATEGORIES
        return Inertia::render('Admin/Video/Index', [
            'videos' => $videos,
            'categories' => [],  // Empty array
        ]);
    }

    /**
     * Show the form for creating a new video
     */
    public function create()
    {
        return Inertia::render('Admin/Video/Create', [
            'categories' => [],  // ✅ Empty categories
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
            'is_active' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            // ✅ USE GoogleDriveService TO PROCESS URL
            $streamingUrl = $this->googleDriveService->processUrl($validated['google_drive_url']);

            if (!$streamingUrl) {
                throw new \Exception('Could not process Google Drive URL. Please check the URL and try again.');
            }

            $videoData = [
                'name' => $validated['name'],
                'description' => $validated['description'],
                'google_drive_url' => $validated['google_drive_url'],
                'streaming_url' => $streamingUrl,
                'duration' => $validated['duration'],
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => auth()->id(),
            ];

            $video = Video::create($videoData);

            DB::commit();

            Log::info('Video created successfully', [
                'video_id' => $video->id,
                'video_name' => $video->name,
                'streaming_url' => $streamingUrl,
                'created_by' => auth()->id(),
            ]);

            return redirect()->route('admin.videos.index')
                ->with('success', 'Video created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create video', [
                'error' => $e->getMessage(),
                'google_drive_url' => $validated['google_drive_url']
            ]);

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
        return Inertia::render('Admin/Video/Edit', [
            'video' => [
                'id' => $video->id,
                'name' => $video->name,
                'description' => $video->description,
                'google_drive_url' => $video->google_drive_url,
                'duration' => $video->duration,
                'is_active' => $video->is_active,
            ],
            'categories' => [],  // ✅ Empty categories
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
            'is_active' => 'boolean',
            'refresh_streaming_url' => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            $updateData = [
                'name' => $validated['name'],
                'description' => $validated['description'],
                'google_drive_url' => $validated['google_drive_url'],
                'duration' => $validated['duration'],
                'is_active' => $validated['is_active'] ?? true,
            ];

            // ✅ REFRESH STREAMING URL IF REQUESTED OR URL CHANGED
            if ($request->boolean('refresh_streaming_url') || $video->google_drive_url !== $validated['google_drive_url']) {
                $newStreamingUrl = $this->googleDriveService->processUrl($validated['google_drive_url']);
                if ($newStreamingUrl) {
                    $updateData['streaming_url'] = $newStreamingUrl;

                    Log::info('Video streaming URL refreshed', [
                        'video_id' => $video->id,
                        'new_streaming_url' => $newStreamingUrl
                    ]);
                }
            }

            $video->update($updateData);

            DB::commit();

            Log::info('Video updated successfully', [
                'video_id' => $video->id,
                'video_name' => $video->name,
                'updated_by' => auth()->id(),
            ]);

            return redirect()->route('admin.videos.index')
                ->with('success', 'Video updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update video', [
                'video_id' => $video->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update video. Please try again.');
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
