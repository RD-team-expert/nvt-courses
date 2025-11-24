<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\VideoBookmark;
use App\Models\VideoCategory;
use App\Models\VideoProgress;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class VideoController extends Controller
{
    protected $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Display a listing of videos for users
     */
    public function index(Request $request)
    {
        // Base query with user progress
        $query = Video::with(['category', 'progress' => function ($query) {
            $query->where('user_id', auth()->id());
        }])->active();

        // Search functionality
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('video_category_id', $request->category_id);
        }

        // Status filter: not_started, in_progress, completed
        if ($request->filled('status')) {
            $userId = auth()->id();
            if ($request->status === 'not_started') {
                $query->whereDoesntHave('progress', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            } elseif ($request->status === 'in_progress') {
                $query->whereHas('progress', function ($q) use ($userId) {
                    $q->where('user_id', $userId)->where('is_completed', false);
                });
            } elseif ($request->status === 'completed') {
                $query->whereHas('progress', function ($q) use ($userId) {
                    $q->where('user_id', $userId)->where('is_completed', true);
                });
            }
        }

        // Sort options
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'duration':
                $query->orderBy('duration', 'asc');
                break;
            case 'latest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $videos = $query->get()->map(function ($video) {
            $userProgress = $video->progress->first();

            // Handle thumbnail URL
            $thumbnailUrl = null;
            if ($video->thumbnail_path && Storage::disk('public')->exists($video->thumbnail_path)) {
                $thumbnailUrl = Storage::disk('public')->url($video->thumbnail_path);
            }

            return [
                'id' => $video->id,
                'name' => $video->name,
                'description' => $video->description,
                'duration' => $video->duration,
                'formatted_duration' => $video->formatted_duration,
                'thumbnail_url' => $thumbnailUrl,
                'category' => $video->category ? [
                    'id' => $video->category->id,
                    'name' => $video->category->name,
                ] : null,
                'user_progress' => $userProgress ? [
                    'current_time' => $userProgress->current_time,
                    'formatted_current_time' => $userProgress->formatted_current_time,
                    'completion_percentage' => $userProgress->completion_percentage,
                    'is_completed' => $userProgress->is_completed,
                    'last_accessed_at' => $userProgress->last_accessed_at?->toDateTimeString(),
                ] : null,
                'status' => $userProgress ? ($userProgress->is_completed ? 'completed' : 'in_progress') : 'not_started',
            ];
        });

        $categories = VideoCategory::active()->ordered()->get();

        return Inertia::render('Video/Index', [
            'videos' => $videos,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id', 'status', 'sort']),
        ]);
    }

    /**
     * Display the specified video with player
     */

    public function show(Video $video)
    {
        // Check if video is active
        if (!$video->is_active) {
            abort(404);
        }

        // Process Google Drive URL to get streaming URL
        $streamingUrl = $this->googleDriveService->processUrl($video->google_drive_url);

        if (!$streamingUrl) {
            // Fallback to original URL if processing fails
            $streamingUrl = $video->google_drive_url;

        }

        // Get or create user progress
        $userProgress = VideoProgress::firstOrCreate(
            ['user_id' => auth()->id(), 'video_id' => $video->id],
            [
                'current_time' => 0,
                'total_watched_time' => 0,
                'is_completed' => false,
                'completion_percentage' => 0,
                'playback_speed' => 1.0,
                'last_accessed_at' => now(),
            ]
        );

        // Update last accessed time
        $userProgress->update(['last_accessed_at' => now()]);

        // Handle thumbnail URL
        $thumbnailUrl = null;
        if ($video->thumbnail_path && Storage::disk('public')->exists($video->thumbnail_path)) {
            $thumbnailUrl = Storage::disk('public')->url($video->thumbnail_path);
        }

        // ✅ ENSURE BOOKMARKS ARE ALWAYS AN ARRAY
        $bookmarks = VideoBookmark::where('video_id', $video->id)
            ->where('user_id', auth()->id())
            ->orderBy('timestamp')
            ->get()
            ->map(function ($bookmark) {
                return [
                    'id' => $bookmark->id,
                    'timestamp' => $bookmark->timestamp,
                    'formatted_timestamp' => $bookmark->formatted_timestamp,
                    'note' => $bookmark->note,
                    'created_at' => $bookmark->created_at->toDateTimeString(),
                ];
            })
            ->toArray(); // ✅ Convert to array to ensure it's never null

      

        return Inertia::render('Video/Show', [
            'video' => [
                'id' => $video->id,
                'name' => $video->name,
                'description' => $video->description,
                'google_drive_url' => $streamingUrl,
                'duration' => $video->duration,
                'formatted_duration' => $video->formatted_duration,
                'thumbnail_url' => $thumbnailUrl,
                'category' => $video->category ? [
                    'id' => $video->category->id,
                    'name' => $video->category->name,
                ] : null,
            ],
            'user_progress' => [
                'id' => $userProgress->id,
                'current_time' => $userProgress->current_time,
                'formatted_current_time' => $userProgress->formatted_current_time,
                'completion_percentage' => $userProgress->completion_percentage,
                'is_completed' => $userProgress->is_completed,
                'playback_speed' => $userProgress->playback_speed,
                'total_watched_time' => $userProgress->total_watched_time,
                'last_accessed_at' => $userProgress->last_accessed_at?->toDateTimeString(),
            ],
            'bookmarks' => $bookmarks, // ✅ Ensure this is always an array
        ]);
    }

    /**
     * Update user progress for the video
     */
    public function updateProgress(Request $request, Video $video)
    {
        $validated = $request;

        $userProgress = VideoProgress::where('user_id', auth()->id())
            ->where('video_id', $video->id)
            ->first();

        if (!$userProgress) {
            return response()->json(['error' => 'Progress record not found'], 404);
        }

        // Update progress with duration-based completion calculation
        $userProgress->updateProgress($validated['current_time'], $video->duration);

        // Update total watched time if provided
        if (isset($validated['total_watched_time'])) {
            $userProgress->update(['total_watched_time' => $validated['total_watched_time']]);
        }

        // Update playback speed if provided
        if (isset($validated['playback_speed'])) {
            $userProgress->update(['playback_speed' => $validated['playback_speed']]);
        }

       

        return response()->json([
            'success' => true,
            'progress' => [
                'current_time' => $userProgress->current_time,
                'completion_percentage' => $userProgress->completion_percentage,
                'is_completed' => $userProgress->is_completed,
            ],
        ]);
    }

    /**
     * Mark video as completed manually
     */
    public function markCompleted(Video $video)
    {
        $userProgress = VideoProgress::where('user_id', auth()->id())
            ->where('video_id', $video->id)
            ->first();

        if (!$userProgress) {
            return response()->json(['error' => 'Progress record not found'], 404);
        }

        $userProgress->update([
            'is_completed' => true,
            'completion_percentage' => 100,
            'current_time' => $video->duration ?? $userProgress->current_time,
            'last_accessed_at' => now(),
        ]);

        
        return response()->json(['success' => true]);
    }

  
}
