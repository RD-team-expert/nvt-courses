<?php
// app/Http/Controllers/Admin/AudioCategoryController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudioCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AudioCategoryController extends Controller
{
    /**
     * Display a listing of audio categories
     */
    public function index()
    {
        $categories = AudioCategory::withCount(['audios', 'activeAudios'])
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
                    'audios_count' => $category->audios_count,
                    'active_audios_count' => $category->active_audios_count,
                    'created_at' => $category->created_at->toDateTimeString(),
                ];
            });

        return Inertia::render('Admin/AudioCategory/Index', [
            'categories' => $categories
        ]);
    }

    /**
     * Show the form for creating a new category
     */
    public function create()
    {
        return Inertia::render('Admin/AudioCategory/Create');
    }

    /**
     * Store a newly created category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:audio_categories,name',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ]);

        $category = AudioCategory::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        Log::info('Audio category created', [
            'category_id' => $category->id,
            'category_name' => $category->name,
            'created_by' => auth()->id()
        ]);

        return redirect()->route('admin.audio-categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Show the form for editing a category
     */
    public function edit(AudioCategory $audioCategory)
    {
        return Inertia::render('Admin/AudioCategory/Edit', [
            'category' => [
                'id' => $audioCategory->id,
                'name' => $audioCategory->name,
                'description' => $audioCategory->description,
                'slug' => $audioCategory->slug,
                'is_active' => $audioCategory->is_active,
                'sort_order' => $audioCategory->sort_order,
            ]
        ]);
    }

    /**
     * Update the specified category
     */
    public function update(Request $request, AudioCategory $audioCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:audio_categories,name,' . $audioCategory->id,
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ]);

        $audioCategory->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'is_active' => $validated['is_active'] ?? true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        Log::info('Audio category updated', [
            'category_id' => $audioCategory->id,
            'category_name' => $audioCategory->name,
            'updated_by' => auth()->id()
        ]);

        return redirect()->route('admin.audio-categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified category
     */
    public function destroy(AudioCategory $audioCategory)
    {
        // Check if category has audios
        $audioCount = $audioCategory->audios()->count();

        if ($audioCount > 0) {
            return redirect()->route('admin.audio-categories.index')
                ->with('error', "Cannot delete category. It has {$audioCount} audio(s) associated with it.");
        }

        $categoryName = $audioCategory->name;
        $audioCategory->delete();

        Log::info('Audio category deleted', [
            'category_name' => $categoryName,
            'deleted_by' => auth()->id()
        ]);

        return redirect()->route('admin.audio-categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    /**
     * Toggle category active status
     */
    public function toggleActive(AudioCategory $audioCategory)
    {
        $audioCategory->update([
            'is_active' => !$audioCategory->is_active
        ]);

        Log::info('Audio category status toggled', [
            'category_id' => $audioCategory->id,
            'category_name' => $audioCategory->name,
            'new_status' => $audioCategory->is_active ? 'active' : 'inactive',
            'updated_by' => auth()->id()
        ]);

        return redirect()->back()
            ->with('success', 'Category status updated successfully.');
    }
}
