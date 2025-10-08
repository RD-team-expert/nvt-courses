<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VideoCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class VideoCategoryController extends Controller
{
    /**
     * Display a listing of video categories
     */
    public function index()
    {
        $categories = VideoCategory::withCount(['videos', 'activeVideos'])
            ->ordered()
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'slug' => $category->slug,
                    'is_active' => $category->is_active,
                    'sort_order' => $category->sort_order,
                    'videos_count' => $category->videos_count,
                    'active_videos_count' => $category->active_videos_count,
                    'created_at' => $category->created_at->toDateTimeString(),
                ];
            });

        return Inertia::render('Admin/VideoCategory/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new category
     */
    public function create()
    {
        return Inertia::render('Admin/VideoCategory/Create');
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:video_categories,name',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ]);

        $category = VideoCategory::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        Log::info('Video category created', [
            'category_id' => $category->id,
            'category_name' => $category->name,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.video-categories.index')
            ->with('success', 'Video category created successfully.');
    }

    /**
     * Show the form for editing a category
     */
    public function edit(VideoCategory $videoCategory)
    {
        return Inertia::render('Admin/VideoCategory/Edit', [
            'category' => [
                'id' => $videoCategory->id,
                'name' => $videoCategory->name,
                'description' => $videoCategory->description,
                'slug' => $videoCategory->slug,
                'is_active' => $videoCategory->is_active,
                'sort_order' => $videoCategory->sort_order,
            ],
        ]);
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, VideoCategory $videoCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:video_categories,name,' . $videoCategory->id,
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ]);

        $videoCategory->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        Log::info('Video category updated', [
            'category_id' => $videoCategory->id,
            'category_name' => $videoCategory->name,
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.video-categories.index')
            ->with('success', 'Video category updated successfully.');
    }

    /**
     * Remove the specified category
     */
    public function destroy(VideoCategory $videoCategory)
    {
        // Check if category has videos
        $videoCount = $videoCategory->videos()->count();

        if ($videoCount > 0) {
            return redirect()
                ->route('admin.video-categories.index')
                ->with('error', "Cannot delete category. It has {$videoCount} videos associated with it.");
        }

        $categoryName = $videoCategory->name;
        $videoCategory->delete();

        Log::info('Video category deleted', [
            'category_name' => $categoryName,
            'deleted_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.video-categories.index')
            ->with('success', 'Video category deleted successfully.');
    }

    /**
     * Toggle category active status
     */
    public function toggleActive(VideoCategory $videoCategory)
    {
        $videoCategory->update([
            'is_active' => !$videoCategory->is_active,
        ]);

        Log::info('Video category status toggled', [
            'category_id' => $videoCategory->id,
            'category_name' => $videoCategory->name,
            'new_status' => $videoCategory->is_active ? 'active' : 'inactive',
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->back()
            ->with('success', 'Video category status updated successfully.');
    }
}
