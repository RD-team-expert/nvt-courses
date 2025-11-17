<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audio;
use App\Models\AudioCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Carbon\Carbon;

class AudioController extends Controller
{
    /**
     * Display a listing of audio files
     */
    public function index()
    {
        $audios = Audio::with(['category', 'creator', 'progress'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($audio) {
                return [
                    'id' => $audio->id,
                    'name' => $audio->name,
                    'description' => $audio->description,
                    'duration' => $audio->duration,
                    'formatted_duration' => $audio->formatted_duration,
                    'thumbnail_url' => $audio->thumbnail_url,
                    'is_active' => $audio->is_active,
                    'category' => $audio->category ? [
                        'id' => $audio->category->id,
                        'name' => $audio->category->name,
                    ] : null,
                    'creator' => [
                        'id' => $audio->creator->id,
                        'name' => $audio->creator->name,
                    ],
                    'total_listeners' => $audio->progress->count(),
                    'completed_listeners' => $audio->progress->where('is_completed', true)->count(),
                    'avg_completion' => $audio->progress->avg('completion_percentage') ?? 0,
                    'created_at' => $audio->created_at->toDateTimeString(),
                ];
            });

        $categories = AudioCategory::active()->ordered()->get();

        return Inertia::render('Admin/Audio/Index', [
            'audios' => $audios,
            'categories' => $categories
        ]);
    }

    /**
     * Show the form for creating a new audio
     */
    public function create()
    {
        $categories = AudioCategory::active()->ordered()->get();

        return Inertia::render('Admin/Audio/Create', [
            'categories' => $categories
        ]);
    }

    /**
     * Store a newly created audio
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'google_cloud_url' => 'required|url|max:500',
            'duration' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
            'thumbnail_url' => 'nullable|url|max:500',
            'audio_category_id' => 'nullable|exists:audio_categories,id',
            'is_active' => 'boolean'
        ], [
            'duration.regex' => 'Duration must be in HH:MM:SS format (e.g., 01:30:45)'
        ]);

        // Handle thumbnail upload
        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('audio/thumbnails', 'public');
        }

        // Convert HH:MM:SS to seconds for database storage
        $durationInSeconds = null;
        if (!empty($validated['duration'])) {
            $durationInSeconds = $this->convertTimeToSeconds($validated['duration']);


        }

        $audio = Audio::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'google_cloud_url' => $validated['google_cloud_url'],
            'duration' => $durationInSeconds,
            'thumbnail_path' => $thumbnailPath,
            'thumbnail_url' => $validated['thumbnail_url'],
            'audio_category_id' => $validated['audio_category_id'],
            'is_active' => $validated['is_active'] ?? true,
            'created_by' => auth()->id(),
        ]);



        return redirect()->route('admin.audio.index')
            ->with('success', 'Audio created successfully.');
    }

    /**
     * Convert HH:MM:SS to total seconds
     */
    private function convertTimeToSeconds(string $timeString): int
    {
        $parts = explode(':', $timeString);
        $hours = (int) $parts[0];
        $minutes = (int) $parts[1];
        $seconds = (int) $parts[2];

        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }

    /**
     * Convert HH:MM:SS duration format to seconds
     */
    private function convertDurationToSeconds(string $duration): int
    {
        list($hours, $minutes, $seconds) = explode(':', $duration);
        return (int)$hours * 3600 + (int)$minutes * 60 + (int)$seconds;
    }

    /**
     * Convert seconds to HH:MM:SS format
     */
    private function convertSecondsToHHMMSS(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds - ($hours * 3600)) / 60);
        $seconds = $seconds - ($hours * 3600) - ($minutes * 60);

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * Display the specified audio with analytics
     */
    public function show(Audio $audio)
    {
        $audio->load(['category', 'creator', 'progress.user']);

        // Calculate analytics
        $totalListeners = $audio->progress->count();
        $completedListeners = $audio->progress->where('is_completed', true)->count();
        $avgProgress = $audio->progress->avg('completion_percentage') ?? 0;
        $totalListeningTime = $audio->progress->sum('total_listened_time');

        // Recent activity (last 10 listeners)
        $recentActivity = $audio->progress()
            ->with('user')
            ->orderBy('last_accessed_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($progress) {
                return [
                    'id' => $progress->id,
                    'user' => [
                        'id' => $progress->user->id,
                        'name' => $progress->user->name,
                        'email' => $progress->user->email,
                    ],
                    'current_time' => $progress->current_time,
                    'formatted_current_time' => $progress->formatted_current_time,
                    'completion_percentage' => $progress->completion_percentage,
                    'is_completed' => $progress->is_completed,
                    'total_listened_time' => $progress->total_listened_time,
                    'last_accessed_at' => $progress->last_accessed_at?->toDateTimeString(),
                ];
            });

        return Inertia::render('Admin/Audio/Show', [
            'audio' => [
                'id' => $audio->id,
                'name' => $audio->name,
                'description' => $audio->description,
                'google_cloud_url' => $audio->google_cloud_url,
                'duration' => $audio->duration,
                'formatted_duration' => $audio->formatted_duration,
                'thumbnail_url' => $audio->thumbnail_url,
                'is_active' => $audio->is_active,
                'category' => $audio->category ? [
                    'id' => $audio->category->id,
                    'name' => $audio->category->name,
                ] : null,
                'creator' => [
                    'id' => $audio->creator->id,
                    'name' => $audio->creator->name,
                ],
                'created_at' => $audio->created_at->toDateTimeString(),
            ],
            'analytics' => [
                'total_listeners' => $totalListeners,
                'completed_listeners' => $completedListeners,
                'completion_rate' => $totalListeners > 0 ? round(($completedListeners / $totalListeners) * 100, 2) : 0,
                'avg_progress' => round($avgProgress, 2),
                'total_listening_hours' => round($totalListeningTime / 3600, 2),
            ],
            'recent_activity' => $recentActivity
        ]);
    }

    /**
     * Show the form for editing the specified audio
     */
    public function edit(Audio $audio)
    {
        $categories = AudioCategory::active()->ordered()->get();

        return Inertia::render('Admin/Audio/Edit', [
            'audio' => $audio,
            'categories' => $categories
        ]);
    }

    /**
     * Update the specified audio
     */
    public function update(Request $request, Audio $audio)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'google_cloud_url' => 'required|url|max:500',
            'duration' => 'nullable|integer|min:1|max:86400',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
            'thumbnail_url' => 'nullable|url|max:500',
            'audio_category_id' => 'nullable|exists:audio_categories,id',
            'is_active' => 'boolean',
            'remove_thumbnail' => 'boolean' // Option to remove current thumbnail
        ]);

        // Handle thumbnail upload/removal
        $thumbnailPath = $audio->thumbnail_path; // Keep existing by default

        if ($request->boolean('remove_thumbnail')) {
            // Remove existing thumbnail
            if ($thumbnailPath && Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }
            $thumbnailPath = null;
        }

        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail if exists
            if ($thumbnailPath && Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }

            // Store new thumbnail
            $thumbnailPath = $request->file('thumbnail')->store('audio/thumbnails', 'public');
        }

        $audio->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'google_cloud_url' => $validated['google_cloud_url'],
            'duration' => $validated['duration'],
            'thumbnail_path' => $thumbnailPath,
            'thumbnail_url' => $validated['thumbnail_url'],
            'audio_category_id' => $validated['audio_category_id'],
            'is_active' => $validated['is_active'] ?? true,
        ]);



        return redirect()->route('admin.audio.index')
            ->with('success', 'Audio updated successfully.');
    }

    /**
     * Remove the specified audio
     */
    public function destroy(Audio $audio)
    {
        // Log before deletion

        // Delete related progress records (cascade will handle this, but for logging)
        $progressCount = $audio->progress()->count();

        $audio->delete();



        return redirect()->route('admin.audio.index')
            ->with('success', 'Audio deleted successfully.');
    }

    /**
     * Toggle active status
     */
    public function toggleActive(Audio $audio)
    {
        $audio->update(['is_active' => !$audio->is_active]);

        $status = $audio->is_active ? 'activated' : 'deactivated';



        return back()->with('success', "Audio {$status} successfully.");
    }
}
