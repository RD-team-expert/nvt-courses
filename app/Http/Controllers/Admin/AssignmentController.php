<?php

namespace App\Http\Controllers\Admin;

use App\Events\CourseAssigned;
use App\Http\Controllers\Controller;
use App\Mail\CourseCreatedNotification;
use App\Mail\CourseAssignmentManagerNotification; // 🎯 NEW IMPORT
use App\Models\CourseAssignment;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\User;
use App\Services\ManagerHierarchyService;
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
    /**
     * Store a newly created assignment - FIXED VERSION
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

        // 🎯 FIXED: Combine all assigned users and send notifications ONCE
        $allAssignedUsers = $enrolledUsers->merge($assignedUsers)->unique('id');

        if ($allAssignedUsers->isNotEmpty()) {
            Log::info('📬 Sending notifications to all assigned users', [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'total_users' => $allAssignedUsers->count(),
                'enrolled_users_count' => $enrolledUsers->count(),
                'assigned_users_count' => $assignedUsers->count(),
                'all_user_emails' => $allAssignedUsers->pluck('email')->toArray(),
                'enrolled_emails' => $enrolledUsers->pluck('email')->toArray(),
                'assigned_emails' => $assignedUsers->pluck('email')->toArray()
            ]);

            // Send user notifications (only once per user)
            $this->notifyUsersOnCourseAssignment($course, $allAssignedUsers, auth()->user());

            // Send manager notifications for private courses (only once per user)
            if ($course->privacy === 'private') {
                Log::info('🎯 Course is private, sending manager notifications', [
                    'course_privacy' => $course->privacy,
                    'users_for_manager_notification' => $allAssignedUsers->pluck('email')->toArray()
                ]);

                $this->notifyManagersOnCourseAssignment($course, $allAssignedUsers, auth()->user());
            } else {
                Log::info('ℹ️ Course is public, skipping manager notifications', [
                    'course_privacy' => $course->privacy
                ]);
            }
        } else {
            Log::info('ℹ️ No users to notify', [
                'enrolled_users_count' => $enrolledUsers->count(),
                'assigned_users_count' => $assignedUsers->count()
            ]);
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

        Log::info('🏁 Assignment process completed', [
            'course_id' => $course->id,
            'course_name' => $course->name,
            'assignments_created' => $assignmentCount,
            'enrollments_created' => $enrollmentCount,
            'users_skipped' => $skippedCount,
            'notifications_sent_to' => $allAssignedUsers->count(),
            'manager_notifications' => $course->privacy === 'private' ? 'sent' : 'skipped'
        ]);

        return redirect()->route('admin.assignments.index');
    }

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

            // 🎯 NEW: Notify managers for private courses
            if ($course->privacy === 'private') {
                $this->notifyManagersOnCourseAssignment($course, $assignedUsers, auth()->user());
            }
        }

        return redirect()->route('admin.assignments.index')
            ->with('success', "Successfully created {$assignmentCount} assignments.");
    }

    /**
     * 🎯 EXISTING: Notify users about course assignments
     */
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

    /**
     * 🎯 NEW: Notify managers about course assignments to their team members
     */
    /**
     * 🎯 FIXED: Notify managers with proper User models
     */
    private function notifyManagersOnCourseAssignment(Course $course, $assignedUsers, $assignedBy)
    {
        try {
            Log::info('🎯 Starting manager notifications for course assignment', [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'assigned_users_count' => $assignedUsers->count(),
                'assigned_by_id' => $assignedBy->id,
                'assigned_by_name' => $assignedBy->name,
                'assigned_users' => $assignedUsers->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'department' => $user->department?->name ?? 'No Department'
                    ];
                })->toArray()
            ]);

            $managerService = new ManagerHierarchyService();
            $managersData = [];

            foreach ($assignedUsers as $user) {
                Log::info('🔍 Processing user for manager notification', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_email' => $user->email,
                    'user_department' => $user->department?->name ?? 'No Department'
                ]);

                if (!$user->department) {
                    Log::warning('⚠️ User has no department, skipping manager notification', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'user_email' => $user->email
                    ]);
                    continue;
                }

                // FIXED: Use the new flexible method instead of hardcoded one
                $managers = $managerService->getDirectManagersForUser($user->id);

                Log::info('👔 Direct managers found for user', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'user_department' => $user->department->name,
                    'managers_count' => count($managers),
                    'managers_found' => array_map(function($managerData) {
                        return [
                            'id' => $managerData['manager']->id,
                            'name' => $managerData['manager']->name,
                            'email' => $managerData['manager']->email,
                            'level' => $managerData['level'],
                            'relationship' => $managerData['relationship']
                        ];
                    }, $managers)
                ]);

                // If no direct managers found, try getting department managers as fallback
                if (empty($managers)) {
                    Log::info('🔄 No direct managers found, trying department managers as fallback', [
                        'user_id' => $user->id,
                        'department' => $user->department->name
                    ]);

                    $managers = $managerService->getManagersForUser($user->id, ['L2', 'L3']);

                    Log::info('🏢 Department managers found as fallback', [
                        'user_id' => $user->id,
                        'managers_count' => count($managers)
                    ]);
                }

                foreach ($managers as $managerData) {
                    $manager = $managerData['manager'];
                    $managerId = $manager->id;

                    Log::info('📝 Processing manager for user', [
                        'manager_id' => $managerId,
                        'manager_name' => $manager->name,
                        'manager_email' => $manager->email,
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'relationship_type' => $managerData['relationship'],
                        'level' => $managerData['level']
                    ]);

                    if (!isset($managersData[$managerId])) {
                        Log::info('✅ Manager added to notification list', [
                            'manager_id' => $manager->id,
                            'manager_name' => $manager->name,
                            'manager_email' => $manager->email,
                            'manager_department' => $manager->department?->name ?? 'No Department'
                        ]);

                        $managersData[$managerId] = [
                            'manager' => $manager,
                            'users' => collect()
                        ];
                    }

                    $managersData[$managerId]['users']->push($user);

                    Log::info('👥 User added to manager team', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'user_email' => $user->email,
                        'manager_id' => $managerId,
                        'manager_name' => $manager->name,
                        'manager_email' => $manager->email,
                        'total_team_members' => $managersData[$managerId]['users']->count()
                    ]);
                }
            }

            Log::info('📊 Manager notifications data prepared', [
                'total_managers' => count($managersData),
                'managers_overview' => collect($managersData)->map(function($data, $managerId) {
                    return [
                        'manager_id' => $managerId,
                        'manager_name' => $data['manager']->name,
                        'manager_email' => $data['manager']->email,
                        'team_members_count' => $data['users']->count(),
                        'team_members' => $data['users']->map(function($user) {
                            return [
                                'id' => $user->id,
                                'name' => $user->name,
                                'email' => $user->email
                            ];
                        })->toArray()
                    ];
                })->toArray()
            ]);

            // FIXED: Check if any managers were found
            if (empty($managersData)) {
                Log::warning('⚠️ No managers found for any assigned users', [
                    'course_id' => $course->id,
                    'assigned_users_count' => $assignedUsers->count(),
                    'users_without_managers' => $assignedUsers->map(function($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'department' => $user->department?->name ?? 'No Department'
                        ];
                    })->toArray()
                ]);
                return;
            }

            // Send notifications to each manager
            $successCount = 0;
            $failureCount = 0;

            foreach ($managersData as $managerId => $managerData) {
                $manager = $managerData['manager'];
                $teamMembers = $managerData['users'];

                Log::info('📧 Preparing to send email to manager', [
                    'manager_id' => $manager->id,
                    'manager_name' => $manager->name,
                    'manager_email' => $manager->email,
                    'team_members_count' => $teamMembers->count(),
                    'course_name' => $course->name,
                    'course_privacy' => $course->privacy ?? 'unknown'
                ]);

                if ($teamMembers->count() === 0) {
                    Log::warning('⚠️ Manager has no team members assigned, skipping email', [
                        'manager_id' => $manager->id,
                        'manager_name' => $manager->name,
                        'manager_email' => $manager->email
                    ]);
                    continue;
                }

                try {
                    Log::info('📤 Sending manager notification email', [
                        'to_email' => $manager->email,
                        'to_name' => $manager->name,
                        'from_admin' => $assignedBy->name,
                        'course_name' => $course->name,
                        'team_members' => $teamMembers->pluck('email')->toArray(),
                        'mail_class' => 'CourseAssignmentManagerNotification'
                    ]);

                    // FIXED: Make sure the Mailable class exists and is properly imported
                    Mail::to($manager->email)
                        ->send(new CourseAssignmentManagerNotification(
                            $course,
                            $teamMembers,
                            $assignedBy,
                            $manager,
                            [
                                'assignment_type' => $course->privacy ?? 'course_assignment',
                                'total_assignments' => $assignedUsers->count()
                            ]
                        ));

                    $successCount++;

                    Log::info('✅ Manager notification email sent successfully', [
                        'manager_id' => $manager->id,
                        'manager_name' => $manager->name,
                        'manager_email' => $manager->email,
                        'team_members_count' => $teamMembers->count(),
                        'team_member_ids' => $teamMembers->pluck('id')->toArray(),
                        'team_member_emails' => $teamMembers->pluck('email')->toArray(),
                        'course_id' => $course->id,
                        'course_name' => $course->name
                    ]);

                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error('❌ Failed to send manager notification email', [
                        'manager_id' => $manager->id ?? 'unknown',
                        'manager_name' => $manager->name ?? 'unknown',
                        'manager_email' => $manager->email ?? 'unknown',
                        'error_message' => $e->getMessage(),
                        'error_code' => $e->getCode(),
                        'error_file' => $e->getFile(),
                        'error_line' => $e->getLine(),
                        'full_trace' => $e->getTraceAsString()
                    ]);
                }

                // Rate limiting
                Log::debug('⏱️ Rate limiting - waiting 0.5 seconds before next email');
                usleep(500000); // 0.5 second delay
            }

            Log::info('🏁 Manager notifications process completed', [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'successful_notifications' => $successCount,
                'failed_notifications' => $failureCount,
                'total_managers_processed' => count($managersData),
                'assigned_users_count' => $assignedUsers->count(),
                'summary' => [
                    'success_rate' => count($managersData) > 0 ? round(($successCount / count($managersData)) * 100, 2) . '%' : '0%',
                    'emails_sent' => $successCount,
                    'emails_failed' => $failureCount
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Manager notification process failed completely', [
                'course_id' => $course->id,
                'course_name' => $course->name,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'full_trace' => $e->getTraceAsString(),
                'assigned_users_count' => $assignedUsers->count()
            ]);
        }
    }

    }
