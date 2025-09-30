<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseCompletion;
use App\Models\CourseRegistration;
use App\Models\CourseAvailability;
use App\Models\CourseAssignment;
use App\Models\User;
use App\Services\CourseService;
use App\Events\CourseEnrolled;
use App\Events\CourseCompleted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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
        $userId = auth()->id();

        // Get course IDs that are assigned to the current user
        $assignedCourseIds = [];
        if ($userId) {
            $assignedCourseIds = CourseAssignment::where('user_id', $userId)
                ->pluck('course_id')
                ->toArray();
        }

        $courses = Course::where(function($query) use ($assignedCourseIds) {
            // Show public courses OR courses assigned to the user (even if private)
            $query->where('privacy', 'public')
                ->orWhereIn('id', $assignedCourseIds);
        })
            ->with([
                'availabilities',
                'registrations' => function($query) use ($userId) {
                    $query->where('user_id', $userId);
                },
                'assignments' => function($query) use ($userId) {
                    $query->where('user_id', $userId)->with('assignedBy');
                }
            ])
            ->where('status', '<>', 'completed')
            ->orderBy('start_date', 'asc')
            ->paginate(10) // Paginate with 10 items per page
            ->through(function ($course) use ($userId) {
                $userRegistration = $course->registrations->first();
                $assignment = $course->assignments->first();

                $userAssignment = null;
                if ($assignment) {
                    $userAssignment = [
                        'id' => $assignment->id,
                        'status' => $assignment->status,
                        'assigned_by' => $assignment->assignedBy->name,
                    ];
                }

                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'description' => $course->description,
                    'image_path' => $course->image_path,
                    'start_date' => $course->start_date,
                    'end_date' => $course->end_date,
                    'status' => $course->status,
                    'privacy' => $course->privacy,
                    'level' => $course->level,
                    'duration' => $course->duration,
                    'user_status' => $userRegistration ? $userRegistration->status : null,
                    'has_available_spots' => $course->has_available_spots ?? true,
                    'total_capacity' => $course->availabilities->sum('capacity'),
                    'total_enrolled' => $course->total_enrollment ?? 0,
                    'user_assignment' => $userAssignment
                ];
            });

        return Inertia::render('Courses/Index', [
            'courses' => $courses,
        ]);
    }




    /**
     * Enhanced enroll method with course availability selection
     */
    /**
     * Enhanced enroll method with course availability selection and manager notifications
     */
    public function enroll(Request $request, Course $course)
    {
        try {
            $user = auth()->user();

            Log::info('🎯 Starting enrollment process', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'course_id' => $course->id,
                'course_name' => $course->name,
                'course_privacy' => $course->privacy,
                'user_department' => $user->department?->name ?? 'No Department'
            ]);

            // Validate course availability selection
            $validated = $request->validate([
                'course_availability_id' => 'required|exists:course_availabilities,id'
            ]);

            // ✅ Use transaction to prevent race conditions
            return DB::transaction(function () use ($course, $user, $validated) {

                // ✅ Lock the availability record to prevent overbooking
                $availability = $course->availabilities()
                    ->where('id', $validated['course_availability_id'])
                    ->lockForUpdate() // Prevents concurrent access
                    ->first();

                if (!$availability) {
                    Log::warning('❌ Invalid availability selection', [
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'requested_availability_id' => $validated['course_availability_id']
                    ]);
                    return back()->withErrors(['message' => 'Invalid availability selection.']);
                }

                // ✅ Check if availability is still available
                if (!$availability->is_available) {
                    Log::warning('❌ Availability no longer available', [
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'availability_id' => $availability->id
                    ]);
                    return back()->withErrors(['message' => 'This date range is no longer available.']);
                }

                // ✅ Check if there are available sessions (capacity)
                if ($availability->sessions <= 0) {
                    Log::warning('❌ No sessions available', [
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'availability_id' => $availability->id,
                        'sessions_remaining' => $availability->sessions
                    ]);
                    return back()->withErrors(['message' => 'No sessions available for this schedule. Fully booked!']);
                }

                // Log the enrollment attempt
                Log::info('📝 Proceeding with enrollment', [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'availability_id' => $availability->id,
                    'sessions_before' => $availability->sessions,
                    'capacity_before' => $availability->capacity
                ]);

                // Check if already enrolled
                $existingEnrollment = CourseRegistration::where('course_id', $course->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if ($existingEnrollment) {
                    Log::info('ℹ️ User already enrolled', [
                        'user_id' => $user->id,
                        'course_id' => $course->id
                    ]);
                    return redirect()->route('courses.show', $course->id)
                        ->with('info', 'You are already enrolled in this course.');
                }

                // ✅ Create enrollment record
                $enrollment = CourseRegistration::create([
                    'course_id' => $course->id,
                    'user_id' => $user->id,
                    'course_availability_id' => $availability->id,
                    'status' => 'in_progress',
                    'registered_at' => now()
                ]);

                // ✅ DECREASE CAPACITY BY 1 (This reduces available spots)
                $availability->decrement('capacity');

                Log::info('✅ User enrolled successfully - database updated', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'course_id' => $course->id,
                    'course_name' => $course->name,
                    'availability_id' => $availability->id,
                    'enrollment_id' => $enrollment->id,
                    'sessions_after' => $availability->sessions,
                    'capacity_after' => $availability->capacity - 1
                ]);

                // 🎯 NEW: Send manager notification for public course enrollment
                if ($course->privacy === 'public') {
                    Log::info('📧 Course is public, sending manager notifications', [
                        'course_privacy' => $course->privacy,
                        'user_department' => $user->department?->name ?? 'No Department'
                    ]);

                    $this->notifyManagersOnPublicEnrollment($course, $user);
                } else {
                    Log::info('ℹ️ Course is private, skipping public enrollment manager notifications', [
                        'course_privacy' => $course->privacy
                    ]);
                }

                Log::info('🏁 Enrollment process completed successfully', [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'enrollment_id' => $enrollment->id,
                    'manager_notification' => $course->privacy === 'public' ? 'sent' : 'skipped',
                    'redirect_to' => 'courses.show'
                ]);

                return redirect()->route('courses.show', $course->id)
                    ->with('success', 'Successfully enrolled in the course for ' . $availability->formatted_date_range . '!');
            });

        } catch (\Exception $e) {
            Log::error('💥 Enrollment process failed', [
                'user_id' => $user->id ?? 'unknown',
                'course_id' => $course->id ?? 'unknown',
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('courses.show', $course->id)
                ->with('error', 'An error occurred while enrolling. Please try again.');
        }
    }

    /**
     * 🎯 NEW: Notify managers about public course enrollments
     */


    /**
     * A user can mark their course as completed
     */
    public function markCompleted(Request $request, Course $course)
    {
        $user = auth()->user();

        // Check if user has rated the course first
        $completion = CourseCompletion::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$completion || $completion->rating === null) {
            return redirect()->route('courses.show', $course->id)
                ->with('error', 'You must rate this course before marking it as completed.');
        }

        // ✅ Update the pivot table directly using updateExistingPivot
        $course->users()->updateExistingPivot($user->id, [
            'status' => 'completed',
            'completed_at' => now(),
            'updated_at' => now()
        ]);

        // Optional: Fire the event
         event(new \App\Events\CourseCompleted($course, $user));

        return redirect()->route('courses.show', $course->id)
            ->with('success', 'Course marked as completed!');
    }



    /**
     * Enhanced show method with course availability and assignment data
     */
    public function show(Course $course)
    {
        Log::info('=== COURSE SHOW START ===', ['course_id' => $course->id, 'user_id' => auth()->id()]);

        $course->load(['availabilities' => function($query) {
            $query->orderBy('start_date');
        }]);

        $user = auth()->user();
        $isEnrolled = false;
        $userStatus = null;
        $completion = null;
        $selectedAvailability = null;
        $userAssignment = null;

        if ($user) {
            $isEnrolled = $this->courseService->isUserEnrolled($course, $user);
            $userStatus = $this->courseService->getUserCourseStatus($course, $user);

            // ✅ ALWAYS fetch completion data
            $completion = CourseCompletion::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            Log::info('Completion data fetched', [
                'completion_exists' => !!$completion,
                'completion_data' => $completion ? $completion->toArray() : null
            ]);

            // Get enrollment details
            $enrollment = CourseRegistration::where('course_id', $course->id)
                ->where('user_id', $user->id)
                ->with('courseAvailability')
                ->first();

            if ($enrollment) {
                $selectedAvailability = $enrollment->courseAvailability;
            }
        }

        // ✅ Format availabilities with BOTH capacity and sessions
        $availabilities = $course->availabilities->map(function ($availability) {
            return [
                'id' => $availability->id,
                'start_date' => $availability->start_date,
                'end_date' => $availability->end_date,
                'formatted_date_range' => $availability->formatted_date_range ?? 'TBD',
                'capacity' => $availability->capacity ?? 50,          // ✅ Total capacity
                'sessions' => $availability->sessions ?? 0,           // ✅ Available sessions
                'enrolled_count' => ($availability->capacity ?? 50) - ($availability->sessions ?? 0), // Calculate enrolled
                'available_spots' => $availability->sessions ?? 0,   // Same as sessions
                'is_available' => ($availability->sessions ?? 0) > 0, // Available if sessions > 0
                'is_full' => ($availability->sessions ?? 0) <= 0,    // Full if no sessions left
                'is_expired' => false,
                'notes' => $availability->notes
            ];
        });

        Log::info('Returning data', [
            'isEnrolled' => $isEnrolled,
            'userStatus' => $userStatus,
            'completion_exists' => !!$completion,
            'completion_rating' => $completion ? $completion->rating : null
        ]);

        return Inertia::render('Courses/Show', [
            'course' => $course,
            'isEnrolled' => $isEnrolled,
            'userStatus' => $userStatus,
            'completion' => $completion,
            'selectedAvailability' => $selectedAvailability,
            'availabilities' => $availabilities, // ✅ Now includes both capacity and sessions
            'userAssignment' => $userAssignment,
        ]);
    }




    /**
     * Check if user can view a course
     */
    /**
     * Check if user can view a course
     */
    private function canViewCourse($course)
    {
        // Public courses are visible to everyone
        if ($course->privacy === 'public') {
            return true;
        }

        // Private courses are only visible to enrolled users, assigned users, or admins
        if ($course->privacy === 'private') {
            if (!auth()->check()) {
                return false;
            }

            $user = auth()->user();

            // Check if user is enrolled in the course
            $isEnrolled = CourseRegistration::where('course_id', $course->id)
                ->where('user_id', $user->id)
                ->exists();

            // ✅ ADD THIS: Check if course is assigned to the user
            $isAssigned = CourseAssignment::where('course_id', $course->id)
                ->where('user_id', $user->id)
                ->exists();

            // Check if user is admin
            $isAdmin = $user->is_admin ?? false;

            return $isEnrolled || $isAssigned || $isAdmin; // ✅ Added $isAssigned
        }

        return false;
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
            'feedback' => 'required|string|max:1000'
        ]);

        $user = auth()->user();
        $course = Course::findOrFail($id);

        // ✅ Allow rating for enrolled users (not just completed)
        $enrollment = $course->users()
            ->where('user_id', $user->id)
            ->wherePivot('status', ['pending', 'in_progress', 'active']) // Multiple statuses
            ->first();

        // OR use direct database query
        $enrollment = CourseRegistration::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'in_progress', 'active'])
            ->first();

        if (!$enrollment) {
            return back()->withErrors(['error' => 'You must be enrolled in this course to rate it.']);
        }

        // Create/update rating
        CourseCompletion::updateOrCreate(
            ['user_id' => $user->id, 'course_id' => $id],
            [
                'rating' => $validated['rating'],
                'feedback' => $validated['feedback'],
                'completed_at' => now()
            ]
        );

        return redirect()->route('courses.show', $id)
            ->with('success', 'Thank you for your rating!');
    }
    private function notifyManagersOnPublicEnrollment(Course $course, User $enrolledUser)
    {
        try {
            Log::info('🎯 Starting manager notifications for public course enrollment', [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'enrolled_user_id' => $enrolledUser->id,
                'enrolled_user_name' => $enrolledUser->name,
                'enrolled_user_department' => $enrolledUser->department?->name ?? 'No Department'
            ]);

            if (!$enrolledUser->department) {
                Log::warning('⚠️ User has no department, skipping manager notification', [
                    'user_id' => $enrolledUser->id,
                    'user_name' => $enrolledUser->name,
                    'user_email' => $enrolledUser->email
                ]);
                return;
            }

            $managerService = new \App\Services\ManagerHierarchyService();
            $departmentName = $enrolledUser->department->name;
            $managers = $managerService->getManagersForDepartment($departmentName, ['L2']);

            Log::info('👔 Managers found for public enrollment', [
                'department_name' => $departmentName,
                'managers_count' => count($managers['L2']),
                'managers_found' => array_map(function($manager) {
                    return $manager ? [
                        'id' => $manager['id'],
                        'name' => $manager['name'],
                        'email' => $manager['email']
                    ] : null;
                }, $managers['L2'])
            ]);

            $successCount = 0;
            $failureCount = 0;

            foreach ($managers['L2'] as $manager) {
                if (!$manager) continue;

                try {
                    $managerUser = User::find($manager['id']);

                    if (!$managerUser) {
                        Log::error('❌ Manager user not found in database', [
                            'manager_id' => $manager['id'],
                            'expected_name' => $manager['name']
                        ]);
                        continue;
                    }

                    Log::info('📤 Sending public enrollment notification to manager', [
                        'to_email' => $managerUser->email,
                        'to_name' => $managerUser->name,
                        'enrolled_user' => $enrolledUser->name,
                        'course_name' => $course->name
                    ]);

                    Mail::to($managerUser->email)
                        ->send(new \App\Mail\PublicCourseEnrollmentNotification(
                            $course,
                            $enrolledUser,
                            $managerUser,
                            [
                                'enrollment_type' => 'self_enrollment',
                                'course_type' => 'public'
                            ]
                        ));

                    $successCount++;

                    Log::info('✅ Public enrollment notification sent successfully', [
                        'manager_id' => $managerUser->id,
                        'manager_email' => $managerUser->email,
                        'enrolled_user_id' => $enrolledUser->id,
                        'enrolled_user_name' => $enrolledUser->name,
                        'course_id' => $course->id,
                        'course_name' => $course->name
                    ]);

                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error('❌ Failed to send public enrollment notification', [
                        'manager_id' => $manager['id'] ?? 'unknown',
                        'manager_email' => $manager['email'] ?? 'unknown',
                        'error_message' => $e->getMessage()
                    ]);
                }

                // Rate limiting
                usleep(500000); // 0.5 second delay
            }

            Log::info('🏁 Public enrollment notifications completed', [
                'course_id' => $course->id,
                'enrolled_user_id' => $enrolledUser->id,
                'successful_notifications' => $successCount,
                'failed_notifications' => $failureCount
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Public enrollment notification process failed', [
                'course_id' => $course->id,
                'enrolled_user_id' => $enrolledUser->id,
                'error_message' => $e->getMessage()
            ]);
        }
    }

}
