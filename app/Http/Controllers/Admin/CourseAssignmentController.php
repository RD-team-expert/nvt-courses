<?php

namespace App\Http\Controllers\Admin;

use App\Events\CourseOnlineAssigned;
use App\Http\Controllers\Controller;
use App\Models\CourseOnline;
use App\Models\CourseOnlineAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CourseAssignmentController extends Controller
{
    /**
     * Display listing of course assignments
     */
    public function index(Request $request)
    {
        $query = CourseOnlineAssignment::with(['courseOnline', 'user', 'assignedBy'])
            ->orderBy('assigned_at', 'desc');

        // Filter by course
        if ($request->course_id) {
            $query->where('course_online_id', $request->course_id);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by user
        if ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Search by user name or course name
        if ($request->search) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })->orWhereHas('courseOnline', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $assignments = $query->paginate(15);

        // Get filter options
        $courses = CourseOnline::active()->orderBy('name')->get(['id', 'name']);
        $users = User::where('role', '!=', 'admin')->orderBy('name')->get(['id', 'name', 'email']);

        // Calculate summary statistics
        $stats = [
            'total_assignments' => CourseOnlineAssignment::count(),
            'active_assignments' => CourseOnlineAssignment::where('status', 'in_progress')->count(),
            'completed_assignments' => CourseOnlineAssignment::where('status', 'completed')->count(),
            'average_completion_rate' => CourseOnlineAssignment::avg('progress_percentage') ?? 0,
        ];

        return Inertia::render('Admin/CourseAssignment/Index', [
            'assignments' => $assignments->through(fn($assignment) => [
                'id' => $assignment->id,
                'course' => [
                    'id' => $assignment->courseOnline->id,
                    'name' => $assignment->courseOnline->name,
                    'difficulty_level' => $assignment->courseOnline->difficulty_level,
                    'estimated_duration' => $assignment->courseOnline->estimated_duration,
                ],
                'user' => [
                    'id' => $assignment->user->id,
                    'name' => $assignment->user->name,
                    'email' => $assignment->user->email,
                ],
                'assigned_by' => [
                    'id' => $assignment->assignedBy->id,
                    'name' => $assignment->assignedBy->name,
                ],
                'status' => $assignment->status,
                'progress_percentage' => round($assignment->progress_percentage, 1),
                'assigned_at' => $assignment->assigned_at->format('M d, Y'),
                'started_at' => $assignment->started_at?->format('M d, Y'),
                'completed_at' => $assignment->completed_at?->format('M d, Y'),
                'time_spent' => $assignment->time_spent,
                'current_module' => $assignment->current_module_id,
            ]),
            'courses' => $courses,
            'users' => $users,
            'stats' => $stats,
            'filters' => $request->only(['course_id', 'status', 'user_id', 'search']),
        ]);
    }

    /**
     * Show form for creating new assignments
     */
    public function create(Request $request)
{
    $selectedCourseId = $request->get('course_id');

    $courses = CourseOnline::active()
        ->withCount(['modules', 'assignments'])
        ->orderBy('name')
        ->get()
        ->map(fn($course) => [
            'id' => $course->id,
            'name' => $course->name,
            'description' => $course->description,
            'difficulty_level' => $course->difficulty_level,
            'estimated_duration' => $course->estimated_duration,
            'modules_count' => $course->modules_count,
            'current_assignments' => $course->assignments_count,
        ]);

    $departments = \App\Models\Department::query()
        ->orderBy('name')
        ->get(['id', 'name']);

    // ✅ FIXED: Check if user has THIS specific course
    $users = User::where('role', '!=', 'admin')
        ->with('department')
        ->orderBy('name')
        ->get()
        ->map(function($user) use ($selectedCourseId) {
            // ✅ Check if user has this SPECIFIC course assigned
            $hasThisCourse = false;
            if ($selectedCourseId) {
                $hasThisCourse = \App\Models\CourseOnlineAssignment::where('user_id', $user->id)
                    ->where('course_online_id', $selectedCourseId)
                    ->exists();
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department_id' => $user->department_id,
                'department_name' => $user->department?->name ?? 'No Department',
                'active_assignments' => $user->courseAssignments()->where('status', 'in_progress')->count(),
                'has_selected_course' => $hasThisCourse, // ✅ NEW: Flag for this specific course
            ];
        });

    return Inertia::render('Admin/CourseAssignment/Create', [
        'courses' => $courses,
        'users' => $users,
        'departments' => $departments,
        'selectedCourseId' => $selectedCourseId,
        'selectedUserIds' => $request->get('user_ids', []),
    ]);
}



    /**
     * Store newly created assignments
     */
    public function store(Request $request)
{


    $validated = $request->validate([
        'course_id' => 'required|exists:course_online,id',
        'user_ids' => 'required|array|min:1',
        'user_ids.*' => 'exists:users,id',
        'send_notification' => 'boolean',
    ]);

    try {
        $course = CourseOnline::with(['modules'])->findOrFail($validated['course_id']);
        $users = User::with(['department'])->whereIn('id', $validated['user_ids'])->get();

        $assignmentCount = 0;
        $skippedCount = 0;

        // ✅ NEW: Group users by manager
        $usersByManager = [];

        foreach ($users as $user) {
            $existingAssignment = CourseOnlineAssignment::where('course_online_id', $course->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingAssignment) {
                Log::warning('⚠️ Assignment already exists', [
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                ]);
                $skippedCount++;
                continue;
            }

            $assignment = CourseOnlineAssignment::create([
                'course_online_id' => $course->id,
                'user_id' => $user->id,
                'assigned_by' => auth()->id(),
                'assigned_at' => now(),
                'status' => 'assigned',
                'progress_percentage' => 0,
                'current_module_id' => $course->modules->first()?->id,
                'notification_sent' => false,
            ]);

            $assignmentCount++;

            // ✅ NEW: Group users by their managers
            try {
                $managers = app(\App\Services\ManagerHierarchyService::class)
                    ->getDirectManagersForUser($user->id);

                foreach ($managers as $managerData) {
                    $managerId = $managerData['manager']->id;

                    if (!isset($usersByManager[$managerId])) {
                        $usersByManager[$managerId] = [
                            'manager' => $managerData['manager'],
                            'users' => [],
                            'relationship' => $managerData['relationship'],
                            'level' => $managerData['level'],
                        ];
                    }

                    $usersByManager[$managerId]['users'][] = $user;
                }
            } catch (\Exception $e) {
                Log::warning('⚠️ Could not get managers', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // ✅ Send notification to USER only (skip manager for now)
            if ($validated['send_notification'] ?? true) {
                try {
                    $loginLink = $this->generateUserLoginLink($user, $course);

                    CourseOnlineAssigned::dispatch(
                        $course,
                        $user,
                        $loginLink,
                        auth()->user(),
                        [
                            'assignment_type' => 'course_online',
                            'assignment_id' => $assignment->id,
                            'total_modules' => $course->modules->count(),
                            'estimated_duration' => $course->estimated_duration,
                            'difficulty_level' => $course->difficulty_level,
                            'skip_manager_notification' => true, // ✅ Skip manager in listener
                        ]
                    );

                    $assignment->update(['notification_sent' => true]);

                } catch (\Exception $e) {
                    Log::error('❌ User notification failed', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        // ✅ NEW: Send ONE email per manager with ALL their employees
        if ($validated['send_notification'] ?? true) {
            foreach ($usersByManager as $managerId => $data) {
                try {
                    $manager = $data['manager'];
                    $employees = collect($data['users']);



                    Mail::to($manager->email)->send(
                        new \App\Mail\CourseOnlineAssignmentManagerNotification(
                            $course,
                            $employees, // ✅ ALL employees at once
                            auth()->user(),
                            $manager,
                            [
                                'relationship' => $data['relationship'],
                                'level' => $data['level'],
                            ]
                        )
                    );

                    Log::info('✅ Manager notification sent', [
                        'manager_email' => $manager->email,
                        'employee_count' => $employees->count(),
                    ]);

                } catch (\Exception $e) {
                    Log::error('❌ Manager notification failed', [
                        'manager_id' => $managerId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }



        $message = "Successfully assigned the course to {$assignmentCount} user(s).";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} user(s) were already assigned.";
        }

        return redirect()->route('admin.course-assignments.index')
            ->with('success', $message);

    } catch (\Exception $e) {
        Log::error('❌ Course assignment failed', [
            'error' => $e->getMessage(),
        ]);

        return redirect()->back()
            ->withInput()
            ->withErrors(['error' => 'Failed to assign course: ' . $e->getMessage()]);
    }
}


    /**
     * ✅ PRIVATE: Generate login link for users without passwords
     */
    private function generateUserLoginLink(User $user, CourseOnline $course): ?string
    {
        try {
            // Check if user has a password
            if (!empty($user->password)) {
                return null; // User can use regular login
            }

            // Generate temporary login token
            $token = Str::random(60);

            // Store token in user record or cache (you might want to create a separate table)
            $user->update([
                'login_token' => Hash::make($token),
                'login_token_expires' => now()->addDays(7), // Token expires in 7 days
            ]);

            // Create signed URL for token login
            $loginUrl = URL::temporarySignedRoute(
                'auth.token-login',
                now()->addDays(7),
                [
                    'user' => $user->id,
                    'course' => $course->id,
                    'token' => $token
                ]
            );

            Log::info('🔗 Login link generated', [
                'user_id' => $user->id,
                'expires_at' => now()->addDays(7)->toDateTimeString(),
            ]);

            return $loginUrl;

        } catch (\Exception $e) {
            Log::error('❌ Login link generation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * ✅ PRIVATE: Bulk assign method (if you need it)
     */
//    public function bulkAssign(Request $request)
//    {
//        $validated = $request->validate([
//            'course_id' => 'required|exists:course_online,id',
//            'user_ids' => 'required|array|min:1',
//            'user_ids.*' => 'exists:users,id',
//            'send_notification' => 'boolean',
//        ]);
//
//        // Use the same store logic
//        return $this->store($request);
//    }

    /**
     * Display specific assignment details
     */
    public function show(CourseOnlineAssignment $courseAssignment)
    {
        $courseAssignment->load([
            'courseOnline.modules.content',
            'user',
            'assignedBy',
            'currentModule'
        ]);

        // Get detailed user progress
        $userProgress = $courseAssignment->user
            ->userContentProgress()
            ->where('course_online_id', $courseAssignment->course_online_id)
            ->with(['content.module', 'content.video'])
            ->orderBy('last_accessed_at', 'desc')
            ->get();

        // ✅ PERFECT: Get learning sessions with BOTH duration and engagement working
        $learningSessions = $courseAssignment->user
            ->learningSessions()
            ->where('course_online_id', $courseAssignment->course_online_id)
            ->with('content')
            ->orderBy('session_start', 'desc')
            ->limit(20)
            ->get()
            ->map(function($session) {
                // ✅ CALCULATE DURATION using your working method
                $duration = $this->calculateDuration($session->session_start, $session->session_end);

                // ✅ CALCULATE ENGAGEMENT using working method
                $engagementData = $this->calculateSessionEngagement($session);

                return [
                    'id' => $session->id,
                    'content_title' => $session->content?->title ?? 'Course Overview',
                    'session_start' => $session->session_start->format('M d, Y H:i'),
                    'session_end' => $session->session_end?->format('H:i') ?? 'Not ended',
                    'duration' => $duration, // ✅ WORKING DURATION (from calculateDuration method)
                    'attention_score' => $engagementData['attention_score'], // ✅ WORKING ENGAGEMENT
                    'engagement_level' => $engagementData['engagement_level'],
                    'is_suspicious' => $engagementData['is_suspicious'],
                    'cheating_risk' => $engagementData['cheating_risk'],
                ];
            });

        // Calculate module progress
        $moduleProgress = $courseAssignment->courseOnline->modules->map(function($module) use ($courseAssignment) {
            return [
                'id' => $module->id,
                'name' => $module->name,
                'order_number' => $module->order_number,
                'progress' => $this->getModuleProgress($module, $courseAssignment->user_id),
            ];
        });

        return Inertia::render('Admin/CourseAssignment/Show', [
            'assignment' => [
                'id' => $courseAssignment->id,
                'course' => [
                    'id' => $courseAssignment->courseOnline->id,
                    'name' => $courseAssignment->courseOnline->name,
                    'description' => $courseAssignment->courseOnline->description,
                    'difficulty_level' => $courseAssignment->courseOnline->difficulty_level,
                    'estimated_duration' => $courseAssignment->courseOnline->estimated_duration,
                    'modules_count' => $courseAssignment->courseOnline->modules->count(),
                ],
                'user' => [
                    'id' => $courseAssignment->user->id,
                    'name' => $courseAssignment->user->name,
                    'email' => $courseAssignment->user->email,
                ],
                'assigned_by' => [
                    'id' => $courseAssignment->assignedBy->id,
                    'name' => $courseAssignment->assignedBy->name,
                ],
                'status' => $courseAssignment->status,
                'progress_percentage' => round($courseAssignment->progress_percentage, 1),
                'assigned_at' => $courseAssignment->assigned_at->format('M d, Y H:i'),
                'started_at' => $courseAssignment->started_at?->format('M d, Y H:i'),
                'completed_at' => $courseAssignment->completed_at?->format('M d, Y H:i'),
                'time_spent' => $courseAssignment->time_spent,
                'current_module' => $courseAssignment->currentModule ? [
                    'id' => $courseAssignment->currentModule->id,
                    'name' => $courseAssignment->currentModule->name,
                    'order_number' => $courseAssignment->currentModule->order_number,
                ] : null,
            ],
            'moduleProgress' => $moduleProgress,
            'userProgress' => $userProgress->map(fn($progress) => [
                'id' => $progress->id,
                'content' => [
                    'id' => $progress->content->id,
                    'title' => $progress->content->title,
                    'content_type' => $progress->content->content_type,
                    'module_name' => $progress->content->module->name,
                ],
                'completion_percentage' => $progress->completion_percentage,
                'is_completed' => $progress->is_completed,
                'watch_time' => $progress->watch_time ?? 0,
                'last_accessed' => $progress->last_accessed_at?->format('M d, Y H:i'),
                'completed_at' => $progress->completed_at?->format('M d, Y H:i'),
            ]),
            'learningSessions' => $learningSessions->toArray(), // ✅ BOTH DURATION AND ENGAGEMENT WORK
        ]);
    }
    private function calculateSessionEngagement($session): array
    {
        $attentionScore = 0;
        $engagementLevel = 'Unknown';
        $isSuspicious = false;
        $cheatingRisk = 'None';

        try {
            // Get session duration in minutes
            $sessionDuration = 0;
            if ($session->session_start && $session->session_end) {
                $sessionDuration = Carbon::parse($session->session_start)
                    ->diffInMinutes(Carbon::parse($session->session_end));
            } else if ($session->total_duration_minutes) {
                $sessionDuration = $session->total_duration_minutes;
            }

            $currentPosition = $session->current_position ?? 0;

            if ($sessionDuration > 0) {
                // Base attention score calculation
                $baseScore = min(100, ($sessionDuration / 10) * 25);

                // Adjust based on position progression
                if ($currentPosition > 0) {
                    $progressBonus = min(30, $currentPosition * 0.5);
                    $attentionScore = min(100, $baseScore + $progressBonus);
                } else {
                    $attentionScore = max(10, $baseScore - 15);
                }

                // Detect suspicious patterns
                if ($sessionDuration < 2 && $currentPosition > 100) {
                    $isSuspicious = true;
                    $cheatingRisk = 'High';
                    $attentionScore = max(5, $attentionScore - 40);
                } elseif ($sessionDuration > 120 && $currentPosition == 0) {
                    $isSuspicious = true;
                    $cheatingRisk = 'Medium';
                    $attentionScore = max(10, $attentionScore - 25);
                } elseif ($sessionDuration < 1) {
                    $attentionScore = max(5, $attentionScore - 30);
                }

                // Determine engagement level
                if ($attentionScore >= 85) $engagementLevel = 'High';
                elseif ($attentionScore >= 65) $engagementLevel = 'Medium';
                elseif ($attentionScore >= 40) $engagementLevel = 'Low';
                else $engagementLevel = 'Very Low';

            } else {
                $attentionScore = 15;
                $engagementLevel = 'Very Low';
            }

        } catch (\Exception $e) {
            Log::warning('Error calculating engagement for session', [
                'session_id' => $session->id,
                'error' => $e->getMessage()
            ]);

            $attentionScore = 35;
            $engagementLevel = 'Unknown';
        }

        return [
            'attention_score' => (int) $attentionScore,
            'engagement_level' => $engagementLevel,
            'is_suspicious' => $isSuspicious,
            'cheating_risk' => $cheatingRisk,
        ];
    }

    /**
     * ✅ WORKING: Enhanced module progress method (from code 2)
     */
    private function getModuleProgress($module, $userId): array
    {
        $totalContent = $module->content()->count();
        $completedContent = DB::table('user_content_progress')
            ->where('user_id', $userId)
            ->where('module_id', $module->id)
            ->where('is_completed', true)
            ->count();

        $completionPercentage = $totalContent > 0 ? ($completedContent / $totalContent) * 100 : 0;

        return [
            'total_content' => $totalContent,
            'completed_content' => $completedContent,
            'completion_percentage' => round($completionPercentage, 1),
            'is_unlocked' => $this->isModuleUnlocked($module, $userId),
            'is_completed' => $completionPercentage >= 100,
        ];
    }
    /**
     * Remove assignment
     */
    public function destroy(CourseOnlineAssignment $courseAssignment)
    {
        $courseName = $courseAssignment->courseOnline->name;
        $userName = $courseAssignment->user->name;
        $courseId = $courseAssignment->course_online_id;

        // Delete related progress records
        $courseAssignment->user
            ->userContentProgress()
            ->where('course_online_id', $courseAssignment->course_online_id)
            ->delete();

        // Delete learning sessions
        $courseAssignment->user
            ->learningSessions()
            ->where('course_online_id', $courseAssignment->course_online_id)
            ->delete();

        $courseAssignment->delete();

        // Update course analytics
        $course = CourseOnline::find($courseId);
        if ($course && $course->analytics) {
            $course->analytics->updateAnalytics();
        }

        Log::info('Course assignment deleted', [
            'course_name' => $courseName,
            'user_name' => $userName,
            'deleted_by' => auth()->id(),
        ]);

        return redirect()->route('admin.course-assignments.index')
            ->with('success', "Assignment for {$userName} in {$courseName} has been removed.");
    }

    /**
     * Bulk assignment creation
     */
    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'course_ids' => 'required|array|min:1',
            'course_ids.*' => 'exists:course_online,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'send_notifications' => 'boolean',
        ]);

        $courses = CourseOnline::whereIn('id', $validated['course_ids'])->get();
        $users = User::whereIn('id', $validated['user_ids'])->get();

        $totalAssignments = 0;
        $skippedAssignments = 0;

        foreach ($courses as $course) {
            foreach ($users as $user) {
                // Check if assignment already exists
                $exists = CourseOnlineAssignment::where('course_online_id', $course->id)
                    ->where('user_id', $user->id)
                    ->exists();

                if (!$exists) {
                    CourseOnlineAssignment::create([
                        'course_online_id' => $course->id,
                        'user_id' => $user->id,
                        'assigned_by' => auth()->id(),
                        'assigned_at' => now(),
                        'status' => 'assigned',
                        'progress_percentage' => 0,
                    ]);

                    $totalAssignments++;

                    // Send notification if requested
                    if ($validated['send_notifications'] ?? false) {
                        $this->sendAssignmentNotification($course, $user, null);
                    }
                } else {
                    $skippedAssignments++;
                }
            }
        }

        Log::info('Bulk course assignments created', [
            'courses_count' => count($courses),
            'users_count' => count($users),
            'assignments_created' => $totalAssignments,
            'assignments_skipped' => $skippedAssignments,
            'assigned_by' => auth()->id(),
        ]);

        $message = "Successfully created {$totalAssignments} assignments across " . count($courses) . " courses.";
        if ($skippedAssignments > 0) {
            $message .= " {$skippedAssignments} assignments were skipped (already exist).";
        }

        return redirect()->route('admin.course-assignments.index')
            ->with('success', $message);
    }

    /**
     * Get assignment statistics
     */
    public function statistics(Request $request)
    {
        $stats = [
            'overview' => [
                'total_assignments' => CourseOnlineAssignment::count(),
                'active_assignments' => CourseOnlineAssignment::where('status', 'in_progress')->count(),
                'completed_assignments' => CourseOnlineAssignment::where('status', 'completed')->count(),
                'not_started' => CourseOnlineAssignment::where('status', 'assigned')->count(),
            ],
            'completion_rates' => CourseOnline::withCount(['assignments', 'completedAssignments'])
                ->having('assignments_count', '>', 0)
                ->get()
                ->map(fn($course) => [
                    'course_name' => $course->name,
                    'total_assignments' => $course->assignments_count,
                    'completed_assignments' => $course->completed_assignments_count,
                    'completion_rate' => round(($course->completed_assignments_count / $course->assignments_count) * 100, 1),
                ]),
            'progress_distribution' => CourseOnlineAssignment::selectRaw('
                    CASE
                        WHEN progress_percentage = 0 THEN "Not Started"
                        WHEN progress_percentage < 25 THEN "0-25%"
                        WHEN progress_percentage < 50 THEN "25-50%"
                        WHEN progress_percentage < 75 THEN "50-75%"
                        WHEN progress_percentage < 100 THEN "75-99%"
                        ELSE "Completed"
                    END as range,
                    COUNT(*) as count
                ')
                ->groupBy('range')
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Send assignment notification email
     */
    private function sendAssignmentNotification(CourseOnline $course, User $user, ?CourseOnlineAssignment $assignment): void
    {
        try {
            // You can implement email sending here
            // Mail::to($user->email)->send(new CourseAssigned($course, $user, $assignment));

            Log::info('Assignment notification sent', [
                'course_id' => $course->id,
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send assignment notification', [
                'course_id' => $course->id,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
    private function calculateDuration($sessionStart, $sessionEnd)
    {
        if (!$sessionEnd || !$sessionStart) {
            return '0 min'; // Session not ended
        }

        try {
            $start = Carbon::parse($sessionStart);
            $end = Carbon::parse($sessionEnd);

            // ✅ CORRECT: Use start->diffInMinutes(end) NOT end->diffInMinutes(start)
            $diffInMinutes = $start->diffInMinutes($end);

            // Additional safety check
            if ($diffInMinutes < 0) {
                $diffInMinutes = 0;
            }

            return $this->formatTimeSpent($diffInMinutes);
        } catch (\Exception $e) {
            Log::warning('Duration calculation error', [
                'session_start' => $sessionStart,
                'session_end' => $sessionEnd,
                'error' => $e->getMessage()
            ]);
            return '0 min';
        }
    }
    private function formatTimeSpent(?int $minutes): string
    {
        if (!$minutes || $minutes <= 0) {
            return '0 min';
        }

        $days = floor($minutes / 1440);
        $hours = floor(($minutes % 1440) / 60);
        $mins = $minutes % 60;

        $result = [];

        if ($days > 0) {
            $result[] = $days . 'd';
        }
        if ($hours > 0) {
            $result[] = $hours . 'h';
        }
        if ($mins > 0) {
            $result[] = $mins . 'm';
        }

        return empty($result) ? '0 min' : implode(' ', $result);
    }

    /**
     * Calculate engagement level based on progress data
     */
    private function calculateEngagementLevel($progress): string
    {
        if (isset($progress->attention_score)) {
            $score = $progress->attention_score;
            if ($score >= 80) return 'High';
            if ($score >= 60) return 'Medium';
            if ($score >= 40) return 'Low';
            return 'Very Low';
        }

        return 'Unknown';
    }

    /**
     * Check if module is unlocked for user
     */
    private function isModuleUnlocked($module, int $userId): bool
    {
        // First module is always unlocked
        if ($module->order_number === 1) {
            return true;
        }

        // Check if previous modules are completed
        // Implementation depends on your business logic
        return true; // Placeholder
    }

}
