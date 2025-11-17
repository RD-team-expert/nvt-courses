<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseOnline;
use App\Models\CourseModule;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class CourseModuleController extends Controller
{
    /**
     * Display listing of modules for a course
     */
    public function index(CourseOnline $courseOnline)
    {
        $modules = $courseOnline->modules()
            ->withCount(['content'])
            ->orderBy('order_number')
            ->get();

        return Inertia::render('Admin/CourseModule/Index', [
            'course' => [
                'id' => $courseOnline->id,
                'name' => $courseOnline->name,
            ],
            'modules' => $modules->map(fn($module) => [
                'id' => $module->id,
                'name' => $module->name,
                'description' => $module->description,
                'order_number' => $module->order_number,
                'estimated_duration' => $module->estimated_duration,
                'is_required' => $module->is_required,
                'is_active' => $module->is_active,
                'content_count' => $module->content_count,
                'video_count' => $module->video_count,
                'pdf_count' => $module->pdf_count,
            ])
        ]);
    }

    /**
     * Show form for creating new module
     */
    public function create(CourseOnline $courseOnline)
    {
        $nextOrderNumber = $courseOnline->modules()->max('order_number') + 1;

        return Inertia::render('Admin/CourseModule/Create', [
            'course' => [
                'id' => $courseOnline->id,
                'name' => $courseOnline->name,
            ],
            'nextOrderNumber' => $nextOrderNumber,
        ]);
    }

    /**
     * Store newly created module
     */
    public function store(Request $request, CourseOnline $courseOnline)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'order_number' => 'required|integer|min:1',
            'estimated_duration' => 'nullable|integer|min:1|max:1000',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // Check if order number already exists
        $existingModule = $courseOnline->modules()
            ->where('order_number', $validated['order_number'])
            ->first();

        if ($existingModule) {
            // Shift other modules up
            $courseOnline->modules()
                ->where('order_number', '>=', $validated['order_number'])
                ->increment('order_number');
        }

        $module = $courseOnline->modules()->create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'order_number' => $validated['order_number'],
            'estimated_duration' => $validated['estimated_duration'],
            'is_required' => $validated['is_required'] ?? true,
            'is_active' => $validated['is_active'] ?? true,
        ]);



        return redirect()->route('admin.course-modules.index', $courseOnline)
            ->with('success', 'Module created successfully.');
    }

    /**
     * Display specified module
     */
    public function show(CourseOnline $courseOnline, CourseModule $courseModule)
    {
        $courseModule->load(['content.video', 'content.tasks']);

        return Inertia::render('Admin/CourseModule/Show', [
            'course' => [
                'id' => $courseOnline->id,
                'name' => $courseOnline->name,
            ],
            'module' => [
                'id' => $courseModule->id,
                'name' => $courseModule->name,
                'description' => $courseModule->description,
                'order_number' => $courseModule->order_number,
                'estimated_duration' => $courseModule->estimated_duration,
                'is_required' => $courseModule->is_required,
                'is_active' => $courseModule->is_active,
                'content' => $courseModule->content->map(fn($content) => [
                    'id' => $content->id,
                    'title' => $content->title,
                    'content_type' => $content->content_type,
                    'order_number' => $content->order_number,
                    'duration' => $content->duration,
                    'formatted_duration' => $content->formatted_duration,
                    'is_required' => $content->is_required,
                    'is_active' => $content->is_active,
                    'video' => $content->video ? [
                        'id' => $content->video->id,
                        'name' => $content->video->name,
                        'google_drive_url' => $content->video->google_drive_url,
                        'thumbnail_url' => $content->video->thumbnail_url,
                    ] : null,
                    'tasks_count' => $content->tasks->count(),
                ]),
                'content_count' => $courseModule->content_count,
                'video_count' => $courseModule->video_count,
                'pdf_count' => $courseModule->pdf_count,
            ]
        ]);
    }

    /**
     * Show form for editing module
     */
    public function edit(CourseOnline $courseOnline, CourseModule $courseModule)
    {
        return Inertia::render('Admin/CourseModule/Edit', [
            'course' => [
                'id' => $courseOnline->id,
                'name' => $courseOnline->name,
            ],
            'module' => [
                'id' => $courseModule->id,
                'name' => $courseModule->name,
                'description' => $courseModule->description,
                'order_number' => $courseModule->order_number,
                'estimated_duration' => $courseModule->estimated_duration,
                'is_required' => $courseModule->is_required,
                'is_active' => $courseModule->is_active,
            ]
        ]);
    }

    /**
     * Update specified module
     */
    public function update(Request $request, CourseOnline $courseOnline, CourseModule $courseModule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'order_number' => 'required|integer|min:1',
            'estimated_duration' => 'nullable|integer|min:1|max:1000',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ]);

        // Handle order number change
        if ($courseModule->order_number != $validated['order_number']) {
            // Reorder modules
            $this->reorderModules($courseOnline, $courseModule, $validated['order_number']);
        }

        $courseModule->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'order_number' => $validated['order_number'],
            'estimated_duration' => $validated['estimated_duration'],
            'is_required' => $validated['is_required'] ?? $courseModule->is_required,
            'is_active' => $validated['is_active'] ?? $courseModule->is_active,
        ]);



        return redirect()->route('admin.course-modules.index', $courseOnline)
            ->with('success', 'Module updated successfully.');
    }

    /**
     * Remove specified module
     */
    public function destroy(CourseOnline $courseOnline, CourseModule $courseModule)
    {
        // Check if module has content
        $contentCount = $courseModule->content()->count();
        if ($contentCount > 0) {
            return redirect()->route('admin.course-modules.index', $courseOnline)
                ->with('error', "Cannot delete module. It has {$contentCount} content items.");
        }

        $moduleName = $courseModule->name;
        $orderNumber = $courseModule->order_number;

        $courseModule->delete();

        // Reorder remaining modules
        $courseOnline->modules()
            ->where('order_number', '>', $orderNumber)
            ->decrement('order_number');



        return redirect()->route('admin.course-modules.index', $courseOnline)
            ->with('success', 'Module deleted successfully.');
    }

    /**
     * Update module order
     */
    public function updateOrder(Request $request, CourseOnline $courseOnline)
    {
        $validated = $request->validate([
            'modules' => 'required|array',
            'modules.*.id' => 'required|exists:course_modules,id',
            'modules.*.order_number' => 'required|integer|min:1',
        ]);

        foreach ($validated['modules'] as $moduleData) {
            CourseModule::where('id', $moduleData['id'])
                ->where('course_online_id', $courseOnline->id)
                ->update(['order_number' => $moduleData['order_number']]);
        }


        return redirect()->back()
            ->with('success', 'Module order updated successfully.');
    }

    /**
     * Helper method to reorder modules
     */
    private function reorderModules(CourseOnline $courseOnline, CourseModule $module, int $newOrder)
    {
        $oldOrder = $module->order_number;

        if ($newOrder > $oldOrder) {
            // Moving down - decrement modules between old and new position
            $courseOnline->modules()
                ->where('id', '!=', $module->id)
                ->whereBetween('order_number', [$oldOrder + 1, $newOrder])
                ->decrement('order_number');
        } else {
            // Moving up - increment modules between new and old position
            $courseOnline->modules()
                ->where('id', '!=', $module->id)
                ->whereBetween('order_number', [$newOrder, $oldOrder - 1])
                ->increment('order_number');
        }
    }
}
