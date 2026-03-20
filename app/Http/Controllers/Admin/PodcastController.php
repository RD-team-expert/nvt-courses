<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PodcastPost;
use App\Models\Video;
use App\Models\Audio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PodcastController extends Controller
{
    public function index()
    {
        $posts = PodcastPost::with(['creator', 'mediable'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($post) => $this->formatPost($post));

        return Inertia::render('Admin/Podcast/Index', [
            'posts' => $posts,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Podcast/Create', [
            'videos' => $this->getAvailableVideos(),
            'audios' => $this->getAvailableAudios(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'excerpt'        => 'nullable|string|max:500',
            'description'    => 'nullable|string',
            'mediable_type'  => 'nullable|in:video,audio',
            'mediable_id'    => 'nullable|integer',
            'thumbnail'      => 'nullable|image|max:2048',
            'status'         => 'required|in:draft,published',
            'tags'           => 'nullable|array',
            'tags.*'         => 'string|max:50',
        ]);

        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')
                ->store('podcast-thumbnails', 'public');
        }

        // Resolve mediable type to full class name
        $mediableType = null;
        $mediableId   = null;
        if (!empty($validated['mediable_type']) && !empty($validated['mediable_id'])) {
            $mediableType = $validated['mediable_type'] === 'video'
                ? \App\Models\Video::class
                : \App\Models\Audio::class;
            $mediableId = $validated['mediable_id'];
        }

        $post = PodcastPost::create([
            'title'          => $validated['title'],
            'slug'           => PodcastPost::generateSlug($validated['title']),
            'excerpt'        => $validated['excerpt'],
            'description'    => $validated['description'],
            'mediable_type'  => $mediableType,
            'mediable_id'    => $mediableId,
            'thumbnail_path' => $thumbnailPath,
            'status'         => $validated['status'],
            'published_at'   => $validated['status'] === 'published' ? now() : null,
            'tags'           => $validated['tags'] ?? [],
            'created_by'     => auth()->id(),
        ]);

        return redirect()->route('admin.podcasts.index')
            ->with('success', 'Blog post created successfully.');
    }

    public function edit(PodcastPost $podcast)
    {
        $podcast->load('mediable');

        return Inertia::render('Admin/Podcast/Edit', [
            'post'   => $this->formatPost($podcast),
            'videos' => $this->getAvailableVideos(),
            'audios' => $this->getAvailableAudios(),
        ]);
    }

    public function update(Request $request, PodcastPost $podcast)
    {
        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'excerpt'       => 'nullable|string|max:500',
            'description'   => 'nullable|string',
            'mediable_type' => 'nullable|in:video,audio',
            'mediable_id'   => 'nullable|integer',
            'thumbnail'     => 'nullable|image|max:2048',
            'status'        => 'required|in:draft,published',
            'tags'          => 'nullable|array',
            'tags.*'        => 'string|max:50',
        ]);

        // Handle thumbnail upload
        $thumbnailPath = $podcast->thumbnail_path;
        if ($request->hasFile('thumbnail')) {
            // Delete old thumbnail
            if ($thumbnailPath) {
                Storage::disk('public')->delete($thumbnailPath);
            }
            $thumbnailPath = $request->file('thumbnail')
                ->store('podcast-thumbnails', 'public');
        }

        // Resolve mediable
        $mediableType = null;
        $mediableId   = null;
        if (!empty($validated['mediable_type']) && !empty($validated['mediable_id'])) {
            $mediableType = $validated['mediable_type'] === 'video'
                ? \App\Models\Video::class
                : \App\Models\Audio::class;
            $mediableId = $validated['mediable_id'];
        }

        // Set published_at only when first publishing
        $publishedAt = $podcast->published_at;
        if ($validated['status'] === 'published' && !$publishedAt) {
            $publishedAt = now();
        } elseif ($validated['status'] === 'draft') {
            $publishedAt = null;
        }

        $podcast->update([
            'title'          => $validated['title'],
            'slug'           => $podcast->title !== $validated['title']
                                    ? PodcastPost::generateSlug($validated['title'])
                                    : $podcast->slug,
            'excerpt'        => $validated['excerpt'],
            'description'    => $validated['description'],
            'mediable_type'  => $mediableType,
            'mediable_id'    => $mediableId,
            'thumbnail_path' => $thumbnailPath,
            'status'         => $validated['status'],
            'published_at'   => $publishedAt,
            'tags'           => $validated['tags'] ?? [],
        ]);

        return redirect()->route('admin.podcasts.index')
            ->with('success', 'Blog post updated successfully.');
    }

    public function destroy(PodcastPost $podcast)
    {
        if ($podcast->thumbnail_path) {
            Storage::disk('public')->delete($podcast->thumbnail_path);
        }

        $podcast->delete();

        return back()->with('success', 'Blog post deleted successfully.');
    }

    public function toggleStatus(PodcastPost $podcast)
    {
        $newStatus = $podcast->status === 'published' ? 'draft' : 'published';

        $podcast->update([
            'status'       => $newStatus,
            'published_at' => $newStatus === 'published'
                ? ($podcast->published_at ?? now())
                : null,
        ]);

        return back()->with('success', 'Post status updated.');
    }

    // ── Private helpers ────────────────────────────────

    private function formatPost(PodcastPost $post): array
    {
        return [
            'id'             => $post->id,
            'title'          => $post->title,
            'slug'           => $post->slug,
            'excerpt'        => $post->excerpt,
            'description'    => $post->description,
            'status'         => $post->status,
            'published_at'   => $post->published_at?->toDateTimeString(),
            'tags'           => $post->tags ?? [],
            'thumbnail_url'  => $post->thumbnail_url,
            'likes_count'    => $post->likes_count,
            'comments_count' => $post->comments_count,
            'mediable_type'  => $post->mediable_type
                ? (str_contains($post->mediable_type, 'Video') ? 'video' : 'audio')
                : null,
            'mediable_id'    => $post->mediable_id,
            'mediable'       => $post->mediable ? [
                'id'   => $post->mediable->id,
                'name' => $post->mediable->name,
            ] : null,
            'creator'        => [
                'id'   => $post->creator->id,
                'name' => $post->creator->name,
            ],
            'created_at'     => $post->created_at->toDateTimeString(),
        ];
    }

    private function getAvailableVideos(): array
    {
        return Video::select('id', 'name', 'duration')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($v) => [
                'id'                 => $v->id,
                'name'               => $v->name,
                'formatted_duration' => $v->formatted_duration,
            ])
            ->toArray();
    }

    private function getAvailableAudios(): array
    {
        return Audio::select('id', 'name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn($a) => [
                'id'   => $a->id,
                'name' => $a->name,
            ])
            ->toArray();
    }
}