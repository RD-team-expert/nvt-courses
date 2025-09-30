<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\AudioCategory;
use App\Models\AudioProgress;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AudioController extends Controller
{
    protected $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Display a listing of audio files for users
     */
    public function index(Request $request)
    {
        $query = Audio::with(['category', 'progress' => function ($query) {
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
            $query->where('audio_category_id', $request->category_id);
        }

        // Status filter (not_started, in_progress, completed)
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

        $audios = $query->get()->map(function ($audio) {
            $userProgress = $audio->progress->first();

            // ✅ EXPLICIT THUMBNAIL HANDLING WITH FALLBACK
            $thumbnailUrl = null;
            if ($audio->thumbnail_path && Storage::disk('public')->exists($audio->thumbnail_path)) {
                $thumbnailUrl = Storage::disk('public')->url($audio->thumbnail_path);
            } elseif (!empty($audio->attributes['thumbnail_url'])) {
                // Fallback to external URL if no uploaded file
                $thumbnailUrl = $audio->attributes['thumbnail_url'];
            }

            return [
                'id' => $audio->id,
                'name' => $audio->name,
                'description' => $audio->description,
                'duration' => $audio->duration,
                'formatted_duration' => $audio->formatted_duration,
                'thumbnail_url' => $thumbnailUrl, // ✅ EXPLICIT HANDLING
                'category' => $audio->category ? [
                    'id' => $audio->category->id,
                    'name' => $audio->category->name,
                ] : null,
                'user_progress' => $userProgress ? [
                    'current_time' => $userProgress->current_time,
                    'formatted_current_time' => $userProgress->formatted_current_time,
                    'completion_percentage' => $userProgress->completion_percentage,
                    'is_completed' => $userProgress->is_completed,
                    'last_accessed_at' => $userProgress->last_accessed_at?->toDateTimeString(),
                ] : null,
                'status' => $userProgress
                    ? ($userProgress->is_completed ? 'completed' : 'in_progress')
                    : 'not_started'
            ];
        });

        $categories = AudioCategory::active()->ordered()->get();
        return Inertia::render('Audio/Index', [
            'audios' => $audios,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id', 'status', 'sort'])
        ]);
    }

    /**
     * Display the specified audio with player
     */
// In your AudioController show() method:
    public function show(Audio $audio)
    {
        // Check if audio is active
        if (!$audio->is_active) {
            abort(404);
        }

        // Process Google Drive URL to get streaming URL
        $streamingUrl = $this->googleDriveService->processUrl($audio->google_cloud_url);

        if (!$streamingUrl) {
            // Fallback to original URL if processing fails
            $streamingUrl = $audio->google_cloud_url;
            Log::warning('Using fallback URL for audio', [
                'audio_id' => $audio->id,
                'original_url' => $audio->google_cloud_url
            ]);
        }

        // Get or create user progress
        $userProgress = AudioProgress::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'audio_id' => $audio->id,
            ],
            [
                'current_time' => 0,
                'total_listened_time' => 0,
                'is_completed' => false,
                'completion_percentage' => 0,
                'last_accessed_at' => now(),
            ]
        );

        // Update last accessed time
        $userProgress->update(['last_accessed_at' => now()]);

        // ✅ EXPLICIT THUMBNAIL HANDLING (same as index)
        $thumbnailUrl = null;
        if ($audio->thumbnail_path && Storage::disk('public')->exists($audio->thumbnail_path)) {
            $thumbnailUrl = Storage::disk('public')->url($audio->thumbnail_path);
            // Fix URL if needed
            $thumbnailUrl = str_replace('http://localhost', config('app.url'), $thumbnailUrl);
        } elseif (!empty($audio->attributes['thumbnail_url'])) {
            $thumbnailUrl = $audio->attributes['thumbnail_url'];
        }

        Log::info('Audio accessed by user', [
            'audio_id' => $audio->id,
            'user_id' => auth()->id(),
            'current_progress' => $userProgress->completion_percentage
        ]);

        return Inertia::render('Audio/Show', [
            'audio' => [
                'id' => $audio->id,
                'name' => $audio->name,
                'description' => $audio->description,
                'google_cloud_url' => $streamingUrl,
                'duration' => $audio->duration,
                'formatted_duration' => $audio->formatted_duration,
                'thumbnail_url' => $thumbnailUrl, // ✅ EXPLICIT HANDLING
                'category' => $audio->category ? [
                    'id' => $audio->category->id,
                    'name' => $audio->category->name,
                ] : null,
            ],
            'user_progress' => [
                'id' => $userProgress->id,
                'current_time' => $userProgress->current_time,
                'formatted_current_time' => $userProgress->formatted_current_time,
                'completion_percentage' => $userProgress->completion_percentage,
                'is_completed' => $userProgress->is_completed,
                'total_listened_time' => $userProgress->total_listened_time,
                'last_accessed_at' => $userProgress->last_accessed_at?->toDateTimeString(),
            ]
        ]);
    }
    /**
     * Update user progress for the audio
     */
    public function updateProgress(Request $request, Audio $audio)
    {
        $validated = $request;

        $userProgress = AudioProgress::where('user_id', auth()->id())
            ->where('audio_id', $audio->id)
            ->first();

        if (!$userProgress) {
            return response()->json(['error' => 'Progress record not found'], 404);
        }

        // Update progress with duration-based completion calculation
        $userProgress->updateProgress(
            $validated['current_time'],
            $audio->duration
        );

        // Update total listened time if provided
        if (isset($validated['total_listened_time'])) {
            $userProgress->update([
                'total_listened_time' => $validated['total_listened_time']
            ]);
        }

        Log::debug('Audio progress updated', [
            'audio_id' => $audio->id,
            'user_id' => auth()->id(),
            'current_time' => $validated['current_time'],
            'completion_percentage' => $userProgress->completion_percentage,
            'is_completed' => $userProgress->is_completed
        ]);

        return response()->json([
            'success' => true,
            'progress' => [
                'current_time' => $userProgress->current_time,
                'completion_percentage' => $userProgress->completion_percentage,
                'is_completed' => $userProgress->is_completed,
            ]
        ]);
    }

    /**
     * Mark audio as completed manually
     */
    public function markCompleted(Audio $audio)
    {
        $userProgress = AudioProgress::where('user_id', auth()->id())
            ->where('audio_id', $audio->id)
            ->first();

        if (!$userProgress) {
            return response()->json(['error' => 'Progress record not found'], 404);
        }

        $userProgress->update([
            'is_completed' => true,
            'completion_percentage' => 100,
            'current_time' => $audio->duration ?? $userProgress->current_time,
            'last_accessed_at' => now(),
        ]);

        Log::info('Audio manually marked as completed', [
            'audio_id' => $audio->id,
            'user_id' => auth()->id()
        ]);

        return response()->json(['success' => true]);
    }
}
