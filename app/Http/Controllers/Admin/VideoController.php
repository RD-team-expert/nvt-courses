<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Services\GoogleDriveService;
use App\Services\ThumbnailService;
use App\Services\FileUploadService;
use App\Services\VideoStorageService; // ✅ NEW
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\VideoCategory;

class VideoController extends Controller
{
    // ✅ UPDATED: Inject VideoStorageService
    public function __construct(
        protected GoogleDriveService $googleDriveService,
        protected ThumbnailService $thumbnailService,
        protected FileUploadService $fileService,
        protected VideoStorageService $videoStorageService // ✅ NEW
    ) {}

    /**
     * Display a listing of videos
     * ✅ UPDATED: Include storage_type in response
     */
    public function index()
    {
        $videos = Video::with(['creator', 'category'])
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

                    // ✅ NEW: Storage information
                    'storage_type' => $video->storage_type,
                    'storage_type_label' => $video->getStorageTypeLabel(),
                    'file_size' => $video->file_size,
                    'formatted_file_size' => $video->formatted_file_size,

                    'category' => $video->category ? [
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

        // ✅ NEW: Storage statistics
        $storageStats = $this->videoStorageService->getStorageStats();

        return Inertia::render('Admin/Video/Index', [
            'videos' => $videos,
            'categories' => $categories,
            'storageStats' => $storageStats, // ✅ NEW
        ]);
    }

    /**
     * Show the form for creating a new video
     * ✅ UPDATED: Pass storage options
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
            // ✅ NEW: Storage configuration
            'storageOptions' => [
                ['value' => 'google_drive', 'label' => 'Google Drive'],
                ['value' => 'local', 'label' => 'Local Storage'],
            ],
            'maxFileSize' => 512000, // 500 MB in KB
            'allowedMimes' => ['mp4', 'webm', 'avi', 'mov', 'mkv'],
        ]);
    }

    /**
     * Store a newly created video
     * ✅ UPDATED: Handle both Google Drive and local storage
     */
    public function store(Request $request)
    {


        // ✅ UPDATED: Validate based on storage_type
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'duration' => 'nullable|integer|min:1|max:86400',
            'content_category_id' => 'nullable|exists:content_categories,id',
            'is_active' => 'boolean',

            // ✅ NEW: Storage type selection
            'storage_type' => 'required|in:google_drive,local',

            // ✅ NEW: Conditional validation
            'google_drive_url' => 'required_if:storage_type,google_drive|nullable|url|max:500',
            'video_file' => [
                'required_if:storage_type,local',
                'nullable',
                'file',
                'mimetypes:video/*,application/octet-stream', // Accept any video type
                'max:524288', // 512MB (adjust if needed)
            ],
        ], [
            'video_file.mimetypes' => 'The file must be a valid video file.',
            'video_file.max' => 'The video file must not be larger than 512MB.',
        ]);

        DB::beginTransaction();

        try {
            $videoData = [
                'name' => $validated['name'],
                'description' => $validated['description'],
                'duration' => $validated['duration'],
                'content_category_id' => $validated['content_category_id'],
                'is_active' => $validated['is_active'] ?? true,
                'created_by' => auth()->id(),
                'storage_type' => $validated['storage_type'], // ✅ NEW
            ];

            // ============================================
            // ✅ ROUTE 1: Google Drive (existing logic)
            // ============================================
            if ($validated['storage_type'] === 'google_drive') {
                if (!$validated['google_drive_url']) {
                    throw new \Exception('Google Drive URL is required');
                }

                $streamingUrl = $this->googleDriveService->processUrl($validated['google_drive_url']);

                if (!$streamingUrl) {
                    throw new \Exception('Could not process Google Drive URL.');
                }

                $videoData['google_drive_url'] = $validated['google_drive_url'];
                $videoData['streaming_url'] = $streamingUrl;
            }

            // ============================================
            // ✅ ROUTE 2: Local Storage (new logic)
            // ============================================
            elseif ($validated['storage_type'] === 'local') {
                if (!$request->hasFile('video_file')) {
                    throw new \Exception('Video file is required for local storage');
                }

                $file = $request->file('video_file');

                // Upload video and get metadata
                $uploadData = $this->videoStorageService->uploadVideo(
                    $file,
                    $validated['content_category_id']
                );

                $videoData['file_path'] = $uploadData['file_path'];
                $videoData['file_size'] = $uploadData['file_size'];
                $videoData['mime_type'] = $uploadData['mime_type'];
                $videoData['duration_seconds'] = $uploadData['duration_seconds'];

                // Use extracted duration if not provided
                if (!$videoData['duration'] && $uploadData['duration_seconds']) {
                    $videoData['duration'] = $uploadData['duration_seconds'];
                }

                Log::info('✅ Local video uploaded', [
                    'file_path' => $uploadData['file_path'],
                    'file_size' => $uploadData['file_size'],
                    'duration' => $uploadData['duration_seconds'],
                ]);
            }

            // Create video record
            $video = Video::create($videoData);

            DB::commit();

            Log::info('✅ Video created successfully', [
                'video_id' => $video->id,
                'storage_type' => $video->storage_type,
                'category_id' => $video->content_category_id,
            ]);

            return redirect()->route('admin.videos.index')
                ->with('success', 'Video created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Failed to create video', [
                'error' => $e->getMessage(),
                'storage_type' => $validated['storage_type'] ?? 'unknown',
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified video
     * ✅ UPDATED: Handle both storage types
     */
    public function show(Video $video)
    {
        $video->load(['creator']);

        // ✅ UPDATED: Only refresh Google Drive streaming URL
        $currentStreamingUrl = null;
        if ($video->isGoogleDrive() && $video->google_drive_url) {
            $currentStreamingUrl = $this->googleDriveService->processUrl($video->google_drive_url);
            if ($currentStreamingUrl && $currentStreamingUrl !== $video->streaming_url) {
                $video->update(['streaming_url' => $currentStreamingUrl]);
            }
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

                // ✅ NEW: Storage information
                'storage_type' => $video->storage_type,
                'storage_type_label' => $video->getStorageTypeLabel(),
                'file_size' => $video->file_size,
                'formatted_file_size' => $video->formatted_file_size,

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
     * ✅ UPDATED: Include storage information
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
                'thumbnail_url' => $video->thumbnail_url,
                'content_category_id' => $video->content_category_id,
                'is_active' => $video->is_active,

                // ✅ NEW: Storage information
                'storage_type' => $video->storage_type,
                'storage_type_label' => $video->getStorageTypeLabel(),
                'file_path' => $video->file_path,
                'file_size' => $video->file_size,
                'formatted_file_size' => $video->formatted_file_size,

                'total_viewers' => 0,
                'avg_completion' => 0,
            ],
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified video
     * ✅ UPDATED: Handle storage type changes carefully
     */
    public function update(Request $request, Video $video)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'duration' => 'nullable|integer|min:1|max:86400',
            'content_category_id' => 'nullable|exists:content_categories,id',
            'is_active' => 'boolean',

            // ✅ NEW: Only allow updating Google Drive URL for Google Drive videos
            'google_drive_url' => $video->isGoogleDrive() ? 'required|url|max:500' : 'nullable',
        ]);

        DB::beginTransaction();

        try {
            $updateData = [
                'name' => $validated['name'],
                'description' => $validated['description'],
                'duration' => $validated['duration'],
                'content_category_id' => $validated['content_category_id'],
                'is_active' => $validated['is_active'] ?? true,
            ];

            // ✅ UPDATED: Only update Google Drive URL if storage type is Google Drive
            if ($video->isGoogleDrive() && isset($validated['google_drive_url'])) {
                if ($video->google_drive_url !== $validated['google_drive_url']) {
                    $newStreamingUrl = $this->googleDriveService->processUrl($validated['google_drive_url']);
                    if ($newStreamingUrl) {
                        $updateData['google_drive_url'] = $validated['google_drive_url'];
                        $updateData['streaming_url'] = $newStreamingUrl;
                    }
                }
            }

            $video->update($updateData);

            DB::commit();

            Log::info('✅ Video updated successfully', [
                'video_id' => $video->id,
                'storage_type' => $video->storage_type,
            ]);

            return redirect()->route('admin.videos.index')
                ->with('success', 'Video updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Failed to update video', [
                'error' => $e->getMessage(),
                'video_id' => $video->id,
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update video.');
        }
    }

    /**
     * Remove the specified video
     * ✅ UPDATED: Delete physical file for local storage
     */
    public function destroy(Video $video)
    {
        try {
            $videoName = $video->name;
            $storageType = $video->storage_type;

            // ✅ NEW: Delete physical file if local storage
            if ($video->isLocal()) {
                $deleted = $video->deleteStoredFile();
                if (!$deleted) {
                    Log::warning('⚠️ Could not delete video file', [
                        'video_id' => $video->id,
                        'file_path' => $video->file_path,
                    ]);
                }
            }

            // Delete database record
            $video->delete();

            Log::info('✅ Video deleted', [
                'video_name' => $videoName,
                'storage_type' => $storageType,
                'deleted_by' => auth()->id(),
            ]);

            return redirect()->route('admin.videos.index')
                ->with('success', 'Video deleted successfully.');

        } catch (\Exception $e) {
            Log::error('❌ Failed to delete video', [
                'video_id' => $video->id,
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete video. Please try again.');
        }
    }

    /**
     * Toggle active status
     * ✅ UNCHANGED: Works for both storage types
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
     * ✅ UPDATED: Only works for Google Drive videos
     */
    public function getStreamingUrl(Video $video)
    {
        try {
            if (!$video->isGoogleDrive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This function only works for Google Drive videos'
                ], 400);
            }

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
