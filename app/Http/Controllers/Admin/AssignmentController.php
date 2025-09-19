<?php

namespace App\Http\Controllers\Admin;

use App\Events\CourseAssigned;
use App\Http\Controllers\Controller;
use App\Mail\CourseCreatedNotification;
use App\Models\CourseAssignment;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
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

        // Filter out admin users (assuming you have is_admin field)
        $users = User::where('is_admin', false)->orderBy('name')->get();

        return Inertia::render('Admin/Assignments/Create', [
            'courses' => $courses,
            'users' => $users,
            'selectedCourseId' => $request->get('course_id') // For pre-selecting from course list
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

        $course = Course::with('availabilities')->findOrFail($validated['course_id']);

        // ✅ Get all availabilities for the course (as collection)
        $availableAvailabilities = $course->availabilities;
        $availabilityCount = $availableAvailabilities->count();

        // ✅ Determine which availability to use based on count
        $availability = null;
        $availabilityId = null;

        if ($availabilityCount > 1) {
            // Multiple availabilities - don't auto-enroll, let user choose
            $availability = null;
            $availabilityId = null;

        } elseif ($availabilityCount === 1) {
            // Single availability - use it automatically
            $availability = $availableAvailabilities->first();
            $availabilityId = $availability->id;

        } else {
            // No availabilities - proceed without availability
            $availability = null;
            $availabilityId = null;
        }

        $assignmentCount = 0;
        $enrollmentCount = 0;
        $skippedCount = 0;
        $assignedUsers = collect();
        $enrolledUsers = collect();
        $errorMessages = collect();

        foreach ($validated['user_ids'] as $userId) {
            try {
                // Check if user already has assignment OR enrollment for this course
                $existingAssignment = CourseAssignment::where('course_id', $course->id)
                    ->where('user_id', $userId)
                    ->exists();

                $existingEnrollment = CourseRegistration::where('course_id', $course->id)
                    ->where('user_id', $userId)
                    ->exists();

                if ($existingAssignment || $existingEnrollment) {
                    $skippedCount++;
                    continue;
                }

                // ✅ Always create CourseAssignment record
                $assignment = CourseAssignment::create([
                    'course_id' => $course->id,
                    'user_id' => $userId,
                    'assigned_by' => auth()->id(),
                    'course_availability_id' => $availabilityId,
                    'assigned_at' => now(),
                    'status' => 'pending'
                ]);
                $assignmentCount++;

                $user = User::find($userId);

                // ✅ Decision: Auto-enroll ONLY if 0 or 1 availability
                if ($availabilityCount <= 1) {
                    // Auto-enroll: No choice needed (0 or 1 availability)
                    $registration = CourseRegistration::create([
                        'course_id' => $course->id,
                        'user_id' => $userId,
                        'course_availability_id' => $availabilityId,
                        'status' => 'in_progress',
                        'registered_at' => now()
                    ]);
                    $enrollmentCount++;
                    $enrolledUsers->push($user);

                    Log::info('Admin auto-assignment and enrollment', [
                        'admin_id' => auth()->id(),
                        'user_id' => $userId,
                        'course_id' => $course->id,
                        'assignment_id' => $assignment->id,
                        'registration_id' => $registration->id,
                        'availability_id' => $availabilityId,
                        'auto_enrolled' => true
                    ]);
                } else {
                    // Don't auto-enroll: Multiple availabilities, user must choose
                    $assignedUsers->push($user);

                    Log::info('Admin assignment without auto-enrollment', [
                        'admin_id' => auth()->id(),
                        'user_id' => $userId,
                        'course_id' => $course->id,
                        'assignment_id' => $assignment->id,
                        'availability_id' => $availabilityId,
                        'auto_enrolled' => false,
                        'reason' => 'Multiple availabilities available'
                    ]);
                }

            } catch (\Exception $e) {
                $user = User::find($userId);
                $errorMessages->push("Failed to assign {$user->name}: " . $e->getMessage());
                $skippedCount++;

                Log::error('Assignment error', [
                    'user_id' => $userId,
                    'course_id' => $course->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // ✅ Send different notifications based on enrollment type
        if ($enrolledUsers->isNotEmpty()) {
            $this->notifyUsersOnCourseAssignment($course, $enrolledUsers, auth()->user());
        }

        if ($assignedUsers->isNotEmpty()) {
            $this->notifyUsersOnCourseAssignment($course, $assignedUsers, auth()->user());
        }

        // ✅ Enhanced messaging system with different messages
        $messages = collect();

        if ($assignmentCount > 0) {
            if ($availabilityCount > 1) {
                // Multiple availabilities - users need to choose
                $messages->push([
                    'type' => 'success',
                    'message' => "Successfully assigned {$assignmentCount} user(s) to the course. Users will visit the course page and choose their preferred schedule before starting."
                ]);
            } else {
                // 0 or 1 availability - users auto-enrolled
                $messages->push([
                    'type' => 'success',
                    'message' => "Successfully assigned and enrolled {$assignmentCount} user(s) in the course. They can start learning immediately."
                ]);
            }
        }

        if ($skippedCount > 0) {
            $messages->push([
                'type' => 'info',
                'message' => "{$skippedCount} user(s) were skipped (already assigned/enrolled or error occurred)."
            ]);
        }

        if ($errorMessages->isNotEmpty()) {
            $messages->push([
                'type' => 'warning',
                'message' => 'Some assignments encountered issues: ' . $errorMessages->implode('; ')
            ]);
        }

        // Flash all messages to session
        foreach ($messages as $msg) {
            session()->flash($msg['type'], $msg['message']);
        }

        // Handle case where no assignments were made
        if ($assignmentCount === 0) {
            if ($skippedCount > 0) {
                return redirect()->route('admin.assignments.index')
                    ->with('warning', 'No new assignments were made. All selected users were already assigned/enrolled in this course.');
            } else {
                return redirect()->route('admin.assignments.index')
                    ->with('error', 'Failed to assign any users to the course. Please try again.');
            }
        }

        return redirect()->route('admin.assignments.index');
    }


    /**
     * ✅ Notify users about automatic enrollment
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
            'pending' => CourseAssignment::pending()->count(),
            'overdue' => CourseAssignment::overdue()->count(),
            'completed' => CourseAssignment::where('status', 'completed')->count(),
            'accepted' => CourseAssignment::where('status', 'accepted')->count(),
            'declined' => CourseAssignment::where('status', 'declined')->count(),
        ];

        $recentAssignments = CourseAssignment::with(['course', 'user', 'assignedBy'])
            ->orderBy('assigned_at', 'desc')
            ->limit(10)
            ->get();

        $overdueAssignments = CourseAssignment::overdue()
            ->with(['course', 'user'])
            ->limit(5)
            ->get();

        return Inertia::render('Admin/Assignments/Dashboard', [
            'stats' => $stats,
            'recentAssignments' => $recentAssignments,
            'overdueAssignments' => $overdueAssignments
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

            // ✅ Send notifications for each course assignment
            $this->notifyUsersOnCourseAssignment($course, $assignedUsers, auth()->user());
        }

        return redirect()->route('admin.assignments.index')
            ->with('success', "Successfully created {$assignmentCount} assignments.");
    }
    private function notifyUsersOnCourseAssignment(Course $course, $assignedUsers, $assignedBy)
    {
        try {
            $course->load('availabilities');
            foreach ($assignedUsers as $user) {
                // ✅ Pass the course ID when generating the login link
                $loginLink = $user->generateLoginLink($course->id);

                Log::debug("Generated login link for {$user->email}: {$loginLink}");

                // Dispatch event with the user and their login link
                CourseAssigned::dispatch($course, $user, $loginLink);
                sleep(3); // Rate limiting
            }

            Log::info('Private course assignment notifications sent successfully', [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'assigned_by' => $assignedBy->id,
                'assigned_by_name' => $assignedBy->name,
                'user_count' => $assignedUsers->count(),
                'user_emails' => $assignedUsers->pluck('email')->toArray(),
                'user_ids' => $assignedUsers->pluck('id')->toArray()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send course assignment notifications', [
                'course_id' => $course->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }


}
