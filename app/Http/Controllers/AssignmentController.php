<?php

namespace App\Http\Controllers;

use App\Models\CourseAssignment;
use App\Models\Course;
use App\Models\User;
use App\Mail\CourseCreationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Carbon\Carbon;

class AssignmentController extends Controller
{
    /**
     * Display a listing of assignments
     */
    public function index()
    {
        $assignments = CourseAssignment::with(['course', 'user', 'assignedBy', 'courseAvailability'])
            ->orderBy('assigned_at', 'desc')
            ->paginate(15);

        return Inertia::render('Admin/Assignments/Index', [
            'assignments' => $assignments
        ]);
    }

    /**
     * Show the form for creating a new assignment
     */
    public function create(Request $request)
    {
        $courses = Course::with('availabilities')->orderBy('name')->get();
        $users = User::where('is_admin', false)->orderBy('name')->get();

        return Inertia::render('Admin/Assignments/Create', [
            'courses' => $courses,
            'users' => $users,
            'selectedCourseId' => $request->get('course_id')
        ]);
    }

    /**
     * Store a newly created assignment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'course_availability_id' => 'nullable|exists:course_availabilities,id',
        ]);

        $course = Course::findOrFail($validated['course_id']);

        // Validate course availability belongs to course
        if ($validated['course_availability_id']) {
            $availability = $course->availabilities()
                ->where('id', $validated['course_availability_id'])
                ->first();

            if (!$availability) {
                return back()->withErrors([
                    'course_availability_id' => 'Selected availability does not belong to this course.'
                ]);
            }
        }

        $assignmentCount = 0;
        $skippedCount = 0;
        $assignedUsers = collect();

        foreach ($validated['user_ids'] as $userId) {
            // Check if user already has assignment for this course
            $existingAssignment = CourseAssignment::where('course_id', $course->id)
                ->where('user_id', $userId)
                ->exists();

            if (!$existingAssignment) {
                CourseAssignment::create([
                    'course_id' => $course->id,
                    'user_id' => $userId,
                    'assigned_by' => auth()->id(),
                    'course_availability_id' => $validated['course_availability_id'] ?? null,
                    'assigned_at' => now(),
                    'status' => 'pending'
                ]);

                // Collect assigned users for notification
                $assignedUsers->push(User::find($userId));
                $assignmentCount++;
            } else {
                $skippedCount++;
            }
        }

        // Send notifications to assigned users
        if ($assignedUsers->isNotEmpty()) {
            $this->notifyUsersOnCourseAssignment($course, $assignedUsers, auth()->user());
        }

        $message = "Successfully assigned course to {$assignmentCount} users.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} users were skipped (already assigned).";
        }

        return redirect()->route('admin.assignments.index')
            ->with('success', $message);
    }

    /**
     * Display the specified assignment
     */
    public function show(CourseAssignment $assignment)
    {
        $assignment->load(['course.availabilities', 'user', 'assignedBy', 'courseAvailability']);

        return Inertia::render('Admin/Assignments/Show', [
            'assignment' => $assignment
        ]);
    }

    /**
     * Show the edit form
     */
    public function edit(CourseAssignment $assignment)
    {
        $assignment->load(['course', 'user', 'courseAvailability']);
        $courses = Course::with('availabilities')->orderBy('name')->get();

        return Inertia::render('Admin/Assignments/Edit', [
            'assignment' => $assignment,
            'courses' => $courses
        ]);
    }

    /**
     * Update the specified assignment
     */
    public function update(Request $request, CourseAssignment $assignment)
    {
        $validated = $request->validate([
            'course_availability_id' => 'nullable|exists:course_availabilities,id',
            'deadline' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,accepted,declined,completed'
        ]);

        // Validate course availability belongs to assignment's course
        if ($validated['course_availability_id']) {
            $availability = $assignment->course->availabilities()
                ->where('id', $validated['course_availability_id'])
                ->first();

            if (!$availability) {
                return back()->withErrors([
                    'course_availability_id' => 'Selected availability does not belong to this course.'
                ]);
            }
        }

        $updateData = [
            'course_availability_id' => $validated['course_availability_id'],
            'deadline' => $validated['deadline'] ? Carbon::parse($validated['deadline']) : null,
            'notes' => $validated['notes'],
            'priority' => $validated['priority'],
            'status' => $validated['status']
        ];

        // Set responded_at if status changed from pending
        if ($assignment->status === 'pending' && $validated['status'] !== 'pending') {
            $updateData['responded_at'] = now();
        }

        // Set completed_at if status is completed
        if ($validated['status'] === 'completed' && $assignment->status !== 'completed') {
            $updateData['completed_at'] = now();
        }

        $assignment->update($updateData);

        return redirect()->route('admin.assignments.index')
            ->with('success', 'Assignment updated successfully.');
    }

    /**
     * Remove the specified assignment
     */
    public function destroy(CourseAssignment $assignment)
    {
        $assignment->delete();

        return redirect()->route('admin.assignments.index')
            ->with('success', 'Assignment deleted successfully.');
    }

    /**
     * Display assignment dashboard with stats
     */
    public function dashboard()
    {
        $stats = [
            'total' => CourseAssignment::count(),
            'pending' => CourseAssignment::where('status', 'pending')->count(),
            'completed' => CourseAssignment::where('status', 'completed')->count(),
            'accepted' => CourseAssignment::where('status', 'accepted')->count(),
            'declined' => CourseAssignment::where('status', 'declined')->count(),
        ];

        $recentAssignments = CourseAssignment::with(['course', 'user', 'assignedBy'])
            ->orderBy('assigned_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Assignments/Dashboard', [
            'stats' => $stats,
            'recentAssignments' => $recentAssignments,
        ]);
    }

    /**
     * Bulk assignment creation
     */
    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => 'exists:courses,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'deadline' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:1000',
            'priority' => 'required|in:low,medium,high'
        ]);

        $assignmentCount = 0;
        $assignedUsers = User::whereIn('id', $validated['user_ids'])->get();

        foreach ($validated['course_ids'] as $courseId) {
            $course = Course::find($courseId);

            foreach ($validated['user_ids'] as $userId) {
                // Check if assignment already exists
                $exists = CourseAssignment::where('course_id', $courseId)
                    ->where('user_id', $userId)
                    ->exists();

                if (!$exists) {
                    CourseAssignment::create([
                        'course_id' => $courseId,
                        'user_id' => $userId,
                        'assigned_by' => auth()->id(),
                        'deadline' => $validated['deadline'] ? Carbon::parse($validated['deadline']) : null,
                        'notes' => $validated['notes'] ?? null,
                        'priority' => $validated['priority'],
                        'assigned_at' => now(),
                        'status' => 'pending'
                    ]);
                    $assignmentCount++;
                }
            }

            // Send notifications for each course assignment
            $this->notifyUsersOnCourseAssignment($course, $assignedUsers, auth()->user());
        }

        return redirect()->route('admin.assignments.index')
            ->with('success', "Successfully created {$assignmentCount} assignments.");
    }

    /**
     * Notify users when assigned to a course
     */
    private function notifyUsersOnCourseAssignment(Course $course, $assignedUsers, $assignedBy)
    {
        try {
            // Load course availabilities for the email template
            $course->load('availabilities');

            // Loop through each user to send individual emails
            foreach ($assignedUsers as $user) {
                Mail::to($user->email)->send(new CourseCreationNotification($course, $user));
            }


        } catch (\Exception $e) {

        }
    }
}
