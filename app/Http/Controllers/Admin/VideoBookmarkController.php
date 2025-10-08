<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\VideoBookmark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class VideoBookmarkController extends Controller
{
    /**
     * Store a new bookmark
     */
    public function store(Request $request, Video $video)
    {
        $validated = $request->validate([
            'timestamp' => 'required|numeric|min:0',
            'note' => 'nullable|string|max:500',
        ]);

        $bookmark = VideoBookmark::create([
            'user_id' => auth()->id(),
            'video_id' => $video->id,
            'timestamp' => $validated['timestamp'],
            'note' => $validated['note'],
        ]);

        Log::info('Video bookmark created', [
            'bookmark_id' => $bookmark->id,
            'video_id' => $video->id,
            'user_id' => auth()->id(),
            'timestamp' => $validated['timestamp'],
        ]);

        return response()->json([
            'success' => true,
            'bookmark' => [
                'id' => $bookmark->id,
                'timestamp' => $bookmark->timestamp,
                'formatted_timestamp' => $bookmark->formatted_timestamp,
                'note' => $bookmark->note,
            ],
        ]);
    }

    /**
     * Get bookmarks for a video
     */
    public function index(Video $video)
    {
        $bookmarks = VideoBookmark::where('video_id', $video->id)
            ->where('user_id', auth()->id())
            ->ordered()
            ->get()
            ->map(function ($bookmark) {
                return [
                    'id' => $bookmark->id,
                    'timestamp' => $bookmark->timestamp,
                    'formatted_timestamp' => $bookmark->formatted_timestamp,
                    'note' => $bookmark->note,
                    'created_at' => $bookmark->created_at->toDateTimeString(),
                ];
            });

        return response()->json(['bookmarks' => $bookmarks]);
    }

    /**
     * Update a bookmark
     */
    public function update(Request $request, VideoBookmark $bookmark)
    {
        // Ensure user owns this bookmark
        if ($bookmark->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $bookmark->update(['note' => $validated['note']]);

        return response()->json([
            'success' => true,
            'bookmark' => [
                'id' => $bookmark->id,
                'note' => $bookmark->note,
            ],
        ]);
    }

    /**
     * Delete a bookmark
     */
    public function destroy(VideoBookmark $bookmark)
    {
        // Ensure user owns this bookmark
        if ($bookmark->user_id !== auth()->id()) {
            abort(403);
        }

        $bookmark->delete();

        Log::info('Video bookmark deleted', [
            'bookmark_id' => $bookmark->id,
            'user_id' => auth()->id(),
        ]);

        return response()->json(['success' => true]);
    }
}
