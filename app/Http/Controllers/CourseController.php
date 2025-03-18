<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseCompletion; // Add this import
use App\Services\CourseService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;


class CourseController extends Controller
{
    protected $courseService;

    public function __construct(CourseService $courseService)
    {
        $this->courseService = $courseService;
    }

    public function index()
    {
        $user = auth()->user();
        $courses = Course::where('status', '<>', 'completed')
            ->orderBy('start_date', 'asc')
            ->get();

        // Add user enrollment status to each course
        if ($user) {
            foreach ($courses as $course) {
                $enrollment = $course->users()->where('user_id', $user->id)->first();
                $course->user_status = $enrollment ? $enrollment->pivot->user_status : null;
            }
        }

        return Inertia::render('Courses/Index', [
            'courses' => $courses,
        ]);
    }

    /**
     * User clicks "Count Me In" / "Enroll" on a course
     */
    public function enroll(Request $request, Course $course)
    {
        try {
            $user = auth()->user();

            // Log the enrollment attempt
            \Log::info('Enrollment attempt', [
                'user_id' => $user->id,
                'course_id' => $course->id
            ]);

            if ($this->courseService->enrollUser($course, $user)) {
                //kami
                event(new \App\Events\CourseEnrolled($course, $user));

                return redirect()->route('courses.show', $course->id)
                    ->with('success', 'Enrolled successfully!');
            }

            return redirect()->route('courses.show', $course->id)
                ->with('info', 'You are already enrolled in this course.');
        } catch (\Exception $e) {
            \Log::error('Enrollment error', [
                'message' => $e->getMessage(),
                'course_id' => $course->id
            ]);

            return redirect()->route('courses.show', $course->id)
                ->with('error', 'An error occurred while enrolling. Please try again.');
        }
    }

    /**
     * A user can mark their course as completed
     */
    public function markCompleted(Request $request, Course $course)
    {
        $user = auth()->user();

        // First mark the course as completed in the pivot table
        if ($this->courseService->markCourseCompleted($course, $user)) {
            // Then create the completion record
            $completion = $this->courseService->createCourseCompletionRecord($course, $user);

            if ($completion) {
                // Redirect to the completion page
                //kami
                event(new \App\Events\CourseCompleted($course, $user));
                return redirect()->route('courses.completion', $course->id);
            }

            return redirect()->route('courses.show', $course->id)
                ->with('success', 'Course marked as completed!');
        }

        return redirect()->route('courses.show', $course->id)
            ->with('error', 'Failed to mark course as completed. Please try again.');
    }

    /**
     * Show a single course with enrollment status
     */
    public function show(Course $course)
    {
        $user = auth()->user();

        $isEnrolled = false;
        $userStatus = null;

        if ($user) {
            $isEnrolled = $this->courseService->isUserEnrolled($course, $user);
            $userStatus = $this->courseService->getUserCourseStatus($course, $user);
        }

        return Inertia::render('Courses/Show', [
            'course' => $course,
            'isEnrolled' => $isEnrolled,
            'userStatus' => $userStatus,
        ]);
    }

    /**
     * Show the course completion page with rating form
     */
    public function showCompletionPage($id)
    {
        $course = Course::findOrFail($id);
        $user = auth()->user();

        // Check if user is enrolled and has completed the course
        $enrollment = $course->users()
            ->where('user_id', $user->id)
            ->first();

        if (!$enrollment || $enrollment->pivot->user_status !== 'completed') {
            return redirect()->route('courses.show', $id)
                ->with('error', 'You must complete this course before accessing the completion page.');
        }

        // Get completion record if exists
        $completion = CourseCompletion::where('user_id', $user->id)
            ->where('course_id', $id)
            ->first();

        return Inertia::render('Courses/Completion', [
            'course' => $course,
            'completion' => $completion
        ]);
    }

    /**
     * Submit course rating and feedback
     */
    public function submitRating(Request $request, $id)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string|max:1000'
        ]);

        $user = auth()->user();
        $course = Course::findOrFail($id);

        // Check if user has completed the course
        $enrollment = $course->users()
            ->where('user_id', $user->id)
            ->where('user_status', 'completed')
            ->first();

        if (!$enrollment) {
            return back()->with('error', 'You must complete this course before rating it.');
        }

        // Update or create completion record
        $completion = CourseCompletion::updateOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $id
            ],
            [
                'completed_at' => now(),
                'rating' => $validated['rating'],
                'feedback' => $validated['feedback']
            ]
        );

        \Log::info('Course rating submitted', [
            'user_id' => $user->id,
            'course_id' => $id,
            'rating' => $validated['rating']
        ]);

        return redirect()->route('courses.show', $id)
            ->with('success', 'Thank you for your feedback!');
    }
}
