<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;


class CourseController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    // In the index method of your CourseController
    public function index()
    {
        $courses = Course::with(['users'])
            ->withCount('users')
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        return Inertia::render('Admin/Courses/Index', [
            'courses' => $courses,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Courses/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:pending,in_progress,completed',
            'level' => 'nullable|in:beginner,intermediate,advanced',
            'duration' => 'nullable|numeric|min:0.1',
        ]);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('course-images', 'public');
        }

        // Ensure dates are properly formatted to prevent timezone issues
        $startDate = !empty($validated['start_date']) ? Carbon::parse($validated['start_date'])->startOfDay()->toDateString() : null;
        $endDate = !empty($validated['end_date']) ? Carbon::parse($validated['end_date'])->startOfDay()->toDateString() : null;

        $course = Course::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'image_path' => $imagePath,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $validated['status'],
            'level' => $validated['level'],
            'duration' => $validated['duration'],
        ]);
        
        event(new \App\Events\CourseCreated($course));

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course created successfully!');
    }

    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:pending,in_progress,completed',
            'level' => 'nullable|in:beginner,intermediate,advanced',
            'duration' => 'nullable|numeric|min:0.1',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($course->image_path) {
                Storage::disk('public')->delete($course->image_path);
            }

            $imagePath = $request->file('image')->store('course-images', 'public');
            $course->image_path = $imagePath;
        }

        // Ensure dates are properly formatted to prevent timezone issues
        $startDate = !empty($validated['start_date']) ? Carbon::parse($validated['start_date'])->startOfDay()->toDateString() : null;
        $endDate = !empty($validated['end_date']) ? Carbon::parse($validated['end_date'])->startOfDay()->toDateString() : null;

        $course->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $validated['status'],
            'level' => $validated['level'],
            'duration' => $validated['duration'],
        ]);

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course updated successfully!');
    }

    public function destroy($id)
    {
        $course = Course::findOrFail($id);

        // Delete course image if exists
        if ($course->image_path) {
            Storage::disk('public')->delete($course->image_path);
        }

        $course->delete();

        return redirect()->route('admin.courses.index')
            ->with('success', 'Course deleted successfully!');
    }

    /**
     * Display the specified course.
     *
     * @param  \App\Models\Course  $course
     * @return \Inertia\Response
     */
    public function show(Course $course)
    {
        // Load the course with its related users
        $course->load('users');

        return Inertia::render('Admin/Courses/Show', [
            'course' => $course
        ]);
    }

    public function edit($id)
    {
        $course = Course::findOrFail($id);
    
        return Inertia::render('Admin/Courses/Edit', [
            'course' => $course,
        ]);
    }
}
