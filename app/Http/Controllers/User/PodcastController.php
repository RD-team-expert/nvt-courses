<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PodcastPost;
use App\Models\PostComment;
use App\Models\PostLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PodcastController extends Controller
{
    public function index()
    {
       $posts = PodcastPost::with(['creator', 'mediable'])
        ->withCount(['likes', 'comments']) // ← adds likes_count & comments_count in ONE query
        ->published()
        ->orderBy('published_at', 'desc')
        ->get()
        ->map(fn($post) => $this->formatPostCard($post));

        // Collect all unique tags from published posts
        $allTags = PodcastPost::published()
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        return Inertia::render('User/Podcast/Index', [
            'posts'   => $posts,
            'allTags' => $allTags,
        ]);
    }

    public function show(string $slug)
    {
         $post = PodcastPost::with(['creator', 'mediable', 'comments.user'])
        ->withCount(['likes', 'comments']) // ← same here
        ->published()
        ->where('slug', $slug)
        ->firstOrFail();

        $userId = auth()->id();

        // Related posts — same tags, exclude current
        $related = PodcastPost::with(['creator', 'mediable'])
            ->published()
            ->where('id', '!=', $post->id)
            ->orderBy('published_at', 'desc')
            ->take(3)
            ->get()
            ->map(fn($p) => $this->formatPostCard($p));

        return Inertia::render('User/Podcast/Show', [
            'post'        => $this->formatPostDetail($post, $userId),
            'relatedPosts' => $related,
        ]);
    }

    public function toggleLike(Request $request, PodcastPost $podcast)
{
    $userId = auth()->id();

    $deleted = PostLike::where('podcast_post_id', $podcast->id)
        ->where('user_id', $userId)
        ->delete();

    if ($deleted) {
        $liked = false;
    } else {
        PostLike::create([
            'podcast_post_id' => $podcast->id,
            'user_id'         => $userId,
        ]);
        $liked = true;
    }

    // Count directly from the relationship — no column needed
    $likesCount = $podcast->likes()->count();

    return response()->json([
        'liked'       => $liked,
        'likes_count' => $likesCount,
    ]);
}
    public function storeComment(Request $request, PodcastPost $podcast)
    {
        $request->validate([
            'body' => 'required|string|max:1000',
        ]);

        $comment = PostComment::create([
            'podcast_post_id' => $podcast->id,
            'user_id'         => auth()->id(),
            'body'            => $request->input('body'),
        ]);

        $comment->load('user');

        return response()->json([
            'comment' => [
                'id'         => $comment->id,
                'body'       => $comment->body,
                'created_at' => $comment->created_at->diffForHumans(),
                'user'       => [
                    'id'   => $comment->user->id,
                    'name' => $comment->user->name,
                ],
            ],
        ]);
    }

    public function destroyComment(Request $request, PostComment $comment)
    {
        $userId  = auth()->id();
        $isAdmin = auth()->user()->is_admin ?? false;

        // Only comment owner or admin can delete
        if ($comment->user_id !== $userId && !$isAdmin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['deleted' => true]);
    }

    // ── Private helpers ────────────────────────────────

    private function formatPostCard(PodcastPost $post): array
    {
        return [
            'id'            => $post->id,
            'title'         => $post->title,
            'slug'          => $post->slug,
            'excerpt'       => $post->excerpt,
            'thumbnail_url' => $post->thumbnail_url,
            'published_at'  => $post->published_at?->toDateTimeString(),
            'tags'          => $post->tags ?? [],
            'likes_count'   => $post->likes_count,
            'comments_count'=> $post->comments_count,
            'mediable_type' => $post->mediable_type
                ? (str_contains($post->mediable_type, 'Video') ? 'video' : 'audio')
                : null,
            'creator'       => [
                'id'   => $post->creator->id,
                'name' => $post->creator->name,
            ],
        ];
    }

    private function formatPostDetail(PodcastPost $post, int $userId): array
    {
        // Build media URL depending on type
        $mediaUrl  = null;
        $mediaType = null;

        if ($post->mediable) {
            if (str_contains($post->mediable_type, 'Video')) {
                $mediaType = 'video';
                $video     = $post->mediable;
                if ($video->storage_type === 'local' && $video->file_path) {
                    $mediaUrl = Storage::disk('public')->url($video->file_path);
                } elseif ($video->streaming_url) {
                    $mediaUrl = $video->streaming_url;
                }
            } elseif (str_contains($post->mediable_type, 'Audio')) {
                $mediaType = 'audio';
                $audio     = $post->mediable;
                $mediaUrl  = route('audio.stream', $audio->id);
            }
        }

        return [
            'id'            => $post->id,
            'title'         => $post->title,
            'slug'          => $post->slug,
            'excerpt'       => $post->excerpt,
            'description'   => $post->description,
            'thumbnail_url' => $post->thumbnail_url,
            'published_at'  => $post->published_at?->toDateTimeString(),
            'tags'          => $post->tags ?? [],
            'likes_count'   => $post->likes_count,
            'comments_count'=> $post->comments_count,
            'liked_by_user' => $post->likedBy($userId),
            'media_url'     => $mediaUrl,
            'media_type'    => $mediaType,
            'mediable'      => $post->mediable ? [
                'id'   => $post->mediable->id,
                'name' => $post->mediable->name,
            ] : null,
            'creator'       => [
                'id'   => $post->creator->id,
                'name' => $post->creator->name,
            ],
            'comments'      => $post->comments->map(fn($c) => [
                'id'         => $c->id,
                'body'       => $c->body,
                'created_at' => $c->created_at->diffForHumans(),
                'user'       => [
                    'id'   => $c->user->id,
                    'name' => $c->user->name,
                ],
            ])->toArray(),
        ];
    }
}