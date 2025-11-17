<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CourseOnline;
use App\Models\CourseOnlineAssignment;
use App\Models\CourseModule;
use App\Models\ModuleContent;
use App\Models\UserContentProgress;
use App\Models\LearningSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CourseOnlineController extends Controller
{
    /**
     * Display user's learning dashboard
     */
    public function index()
    {
        $user = Auth::user();



        // Get user's course assignments with related data
        $assignments = CourseOnlineAssignment::with([
            'courseOnline.modules',
            'currentModule',
            'assignedBy'
        ])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($assignment) use ($user) {



                // ✅ FIXED: Calculate real-time assignment progress
                $realTimeProgress = $this->calculateCourseProgress($assignment->courseOnline->id, $user->id);



                // ✅ UPDATE: Update assignment with real progress if different
                if ($assignment->progress_percentage != $realTimeProgress) {
                    $assignment->update(['progress_percentage' => $realTimeProgress]);
                    $assignment->refresh(); // Refresh the model with new data

                }

                // ✅ DEBUG: Calculate time spent for this assignment
                $timeSpentCalculation = $this->calculateAssignmentTimeSpent($assignment->courseOnline->id, $user->id);



                // Get next content for in-progress courses
                $nextContent = null;
                if ($assignment->status === 'in_progress' && $assignment->currentModule) {
                    $nextContent = $this->getNextUnlockedContent($assignment->courseOnline, $user->id);

                }

                // ✅ NEW: Calculate deadline information
                $deadlineInfo = $this->calculateDeadlineInfo($assignment);

                $assignmentData = [
                    'id' => $assignment->id,
                    'course' => [
                        'id' => $assignment->courseOnline->id,
                        'name' => $assignment->courseOnline->name,
                        'description' => $assignment->courseOnline->description,
                        'image_path' => $assignment->courseOnline->image_path
                            ? asset('storage/' . $assignment->courseOnline->image_path)
                            : null,
                        'difficulty_level' => $assignment->courseOnline->difficulty_level,
                        'estimated_duration' => $assignment->courseOnline->estimated_duration,
                        'modules_count' => $assignment->courseOnline->modules()->count(),
                        // ✅ NEW: Add course deadline info
                        'has_deadline' => $assignment->courseOnline->has_deadline,
                        'deadline' => $assignment->courseOnline->deadline?->toDateTimeString(),
                        'deadline_type' => $assignment->courseOnline->deadline_type,
                    ],
                    'status' => $assignment->status,
                    'progress_percentage' => $assignment->progress_percentage,
                    'assigned_at' => $assignment->created_at->toDateTimeString(),
                    'started_at' => $assignment->started_at?->toDateTimeString(),
                    'completed_at' => $assignment->completed_at?->toDateTimeString(),
                    // ✅ NEW: Add assignment deadline info
                    'deadline' => $assignment->deadline?->toDateTimeString(),
                    'is_overdue' => $assignment->is_overdue ?? false,
                    'deadline_info' => $deadlineInfo,
                    'current_module' => $assignment->currentModule ? [
                        'id' => $assignment->currentModule->id,
                        'name' => $assignment->currentModule->name,
                        'order_number' => $assignment->currentModule->order_number,
                    ] : null,
                    // ✅ DEBUG: Use calculated time instead of stored value
                    'time_spent' => $this->formatTimeSpent($timeSpentCalculation),
                    'next_content' => $nextContent ? [
                        'id' => $nextContent->id,
                        'title' => $nextContent->title,
                        'content_type' => $nextContent->content_type,
                        'module_name' => $nextContent->module->name,
                    ] : null,
                ];



                return $assignmentData;
            });

        // ✅ DEBUG: Calculate total hours with detailed logging
        $totalMinutes = $this->calculateTotalMinutes($user->id);



        // Calculate user statistics
        $stats = [
            'total_assignments' => $assignments->count(),
            'completed_courses' => $assignments->where('status', 'completed')->count(),
            'in_progress_courses' => $assignments->where('status', 'in_progress')->count(),
            'total_minutes_spent' => $totalMinutes,  // ✅ CORRECT KEY NAME
            'average_completion_rate' => $assignments->avg('progress_percentage') ?? 0,
            'certificates_earned' => $assignments->where('status', 'completed')->count(),
            // ✅ NEW: Add deadline-related stats
            'overdue_courses' => $assignments->where('deadline_info.status', 'overdue')->count(),
            'due_soon_courses' => $assignments->where('deadline_info.status', 'due_soon')->count(),
        ];

        // ✅ DEBUG: Final dashboard stats



        return Inertia::render('User/CourseOnline/Index', [
            'assignments' => $assignments,
            'stats' => $stats,
        ]);
    }

    /**
     * Display specific course for user
     */
    public function show(CourseOnline $courseOnline)
    {
        $user = Auth::user();



        // Check if user has access to this course
        $assignment = CourseOnlineAssignment::where('course_online_id', $courseOnline->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$assignment) {


            return redirect()->route('courses-online.index')
                ->with('error', 'You do not have access to this course.');
        }

        // Update assignment progress with current calculation
        $currentProgress = $this->calculateCourseProgress($courseOnline->id, $user->id);
        if ($assignment->progress_percentage != $currentProgress) {

            $assignment->update(['progress_percentage' => $currentProgress]);
            $assignment->refresh();
        }

        // Eager load relationships to avoid N+1 queries
        $courseOnline->load(['modules.content.video']);

        // Get user progress for each module
        $modulesWithProgress = $courseOnline->modules()
            ->orderBy('order_number')
            ->get()
            ->map(function ($module) use ($user) {
                $progress = $this->getModuleProgress($module, $user->id);



                // ✅ FIXED: Added missing array return structure
                return [
                    'id' => $module->id,
                    'name' => $module->name,
                    'description' => $module->description,
                    'order_number' => $module->order_number,
                    'is_required' => $module->is_required,
                    'estimated_duration' => $module->estimated_duration,
                    'is_unlocked' => $this->isModuleUnlocked($module, $user->id),
                    'progress' => $progress,
                    'content' => $module->content->map(function ($content) use ($user) {
                        $contentProgress = $this->getContentProgress($content, $user->id);

                        return [
                            'id' => $content->id,
                            'title' => $content->title,
                            'content_type' => $content->content_type,
                            'order_number' => $content->order_number,
                            'is_required' => $content->is_required,
                            'duration' => $content->duration,
                            'is_unlocked' => $this->isContentUnlocked($content, $user->id),
                            'progress' => $contentProgress,
                            'video' => $content->video ? [
                                'id' => $content->video->id,
                                'name' => $content->video->name,
                                'thumbnail_url' => null,
                                'duration' => $content->video->duration,
                                'formatted_duration' => gmdate('H:i:s', $content->video->duration),
                            ] : null,
                            'pdf_name' => $content->pdf_name,
                        ];
                    }),
                ];
            });

        // Mark assignment as started if not already
        if ($assignment->status === 'assigned') {


            $assignment->update([
                'status' => 'in_progress',
                'started_at' => now(),
                'current_module_id' => $courseOnline->modules()->orderBy('order_number')->first()?->id,
            ]);
        }

        // Calculate time spent for assignment display
        $assignmentTimeSpent = $this->calculateAssignmentTimeSpent($courseOnline->id, $user->id);



        // Calculate deadline info for course show
        $deadlineInfo = $this->calculateDeadlineInfo($assignment);


        return Inertia::render('User/CourseOnline/Show', [
            'course' => [
                'id' => $courseOnline->id,
                'name' => $courseOnline->name,
                'description' => $courseOnline->description,
                'image_path' => $courseOnline->image_path
                    ? asset('storage/' . $courseOnline->image_path)
                    : null,
                'difficulty_level' => $courseOnline->difficulty_level,
                'estimated_duration' => $courseOnline->estimated_duration,
                'is_active' => $courseOnline->is_active,
                'has_deadline' => $courseOnline->has_deadline,
                'deadline' => $courseOnline->deadline?->toDateTimeString(),
                'deadline_type' => $courseOnline->deadline_type,
            ],
            'assignment' => [
                'id' => $assignment->id,
                'status' => $assignment->status,
                'progress_percentage' => $assignment->progress_percentage,
                'assigned_at' => $assignment->created_at->toDateTimeString(),
                'started_at' => $assignment->started_at?->toDateTimeString(),
                'current_module_id' => $assignment->current_module_id,
                'time_spent' => $this->formatTimeSpent($assignmentTimeSpent),
                'time_spent_minutes' => $assignmentTimeSpent,
                'deadline' => $assignment->deadline?->toDateTimeString(),
                'is_overdue' => $assignment->is_overdue ?? false,
                'deadline_info' => $deadlineInfo,
            ],
            'modules' => $modulesWithProgress,
        ]);
    }

    // ===== 🔍 HELPER METHODS WITH ENHANCED DEBUGGING =====

    // ✅ NEW: Calculate deadline information for assignments
    // ✅ FIXED: Calculate deadline information for assignments
    private function calculateDeadlineInfo($assignment): array
    {
        if (!$assignment->deadline) {
            return [
                'status' => 'no_deadline',
                'message' => 'No deadline set',
                'days_remaining' => null,
                'urgency_level' => 'none',
                'formatted_deadline' => null,
            ];
        }

        $now = now();
        $deadline = $assignment->deadline;

        // ✅ FIXED: Use floor() to get whole days only
        $daysDiff = floor($now->diffInDays($deadline, false));

        // Status determination
        $status = 'upcoming';
        $urgencyLevel = 'low';
        $message = '';

        if ($daysDiff < 0) {
            $status = 'overdue';
            $urgencyLevel = 'critical';
            $message = 'Overdue by ' . abs($daysDiff) . ' day' . (abs($daysDiff) !== 1 ? 's' : '');
        } elseif ($daysDiff == 0) {
            $status = 'due_today';
            $urgencyLevel = 'critical';
            $message = 'Due today';
        } elseif ($daysDiff == 1) {
            $status = 'due_tomorrow';
            $urgencyLevel = 'high';
            $message = 'Due tomorrow';
        } elseif ($daysDiff <= 3) {
            $status = 'due_soon';
            $urgencyLevel = 'high';
            $message = 'Due in ' . $daysDiff . ' days';
        } elseif ($daysDiff <= 7) {
            $status = 'due_this_week';
            $urgencyLevel = 'medium';
            $message = 'Due in ' . $daysDiff . ' days';
        } else {
            $status = 'upcoming';
            $urgencyLevel = 'low';
            $message = 'Due in ' . $daysDiff . ' days';
        }

        return [
            'status' => $status,
            'message' => $message,
            'days_remaining' => $daysDiff >= 0 ? $daysDiff : null,
            'urgency_level' => $urgencyLevel,
            'formatted_deadline' => $deadline->format('M d, Y H:i'),
            'is_overdue' => $daysDiff < 0,
            'time_remaining' => $this->formatTimeRemaining($daysDiff),
        ];
    }

// ✅ FIXED: Format time remaining text with whole days
    private function formatTimeRemaining($daysDiff): string
    {
        // ✅ FIXED: Convert to integer to avoid decimals
        $days = intval($daysDiff);

        if ($days < 0) {
            return 'Overdue by ' . abs($days) . ' day' . (abs($days) !== 1 ? 's' : '');
        } elseif ($days == 0) {
            return 'Due today';
        } elseif ($days == 1) {
            return 'Due tomorrow';
        } else {
            return $days . ' day' . ($days !== 1 ? 's' : '') . ' remaining';
        }
    }


    /**
     * ✅ NEW: Calculate time spent for specific assignment/course
     */
    private function calculateAssignmentTimeSpent(int $courseId, int $userId): int
    {


        // Get all learning sessions for this user and course
        $sessions = LearningSession::where('user_id', $userId)
            ->where('course_online_id', $courseId)
            ->select(['id', 'total_duration_minutes', 'session_start', 'session_end'])
            ->get();


        // ✅ FIXED: Only sum positive durations
        $totalMinutes = $sessions->where('total_duration_minutes', '>', 0)->sum('total_duration_minutes');
        $negativeCount = $sessions->where('total_duration_minutes', '<=', 0)->count();



        return max(0, intval($totalMinutes));
    }

    /**
     * ✅ ENHANCED: Calculate total hours spent by user with detailed logging
     */
    private function calculateTotalHours(int $userId): float
    {


        // Get all learning sessions for this user
        $allSessions = LearningSession::where('user_id', $userId)
            ->select(['id', 'total_duration_minutes', 'course_online_id', 'session_start'])
            ->get();



        // ✅ DEBUG: Show sample session data
        $sampleSessions = $allSessions->take(5);


        // ✅ FIXED: Only include positive duration values
        $positiveSessions = $allSessions->where('total_duration_minutes', '>', 0);
        $totalMinutes = $positiveSessions->sum('total_duration_minutes');
        $totalHours = round(max(0, $totalMinutes) / 60, 1);


        return $totalHours;
    }

    /**
     * ✅ ENHANCED: Get module progress with detailed debugging
     */
    private function getModuleProgress(CourseModule $module, int $userId): array
    {

        $totalContent = $module->content()->count();

        if ($totalContent === 0) {


            return [
                'total_content' => 0,
                'completed_content' => 0,
                'completion_percentage' => 0,
                'is_completed' => false,
            ];
        }

        // ✅ FIXED: Get all progress records for this module
        $progressRecords = UserContentProgress::where('user_id', $userId)
            ->where('module_id', $module->id)
            ->get();



        // ✅ FIXED: Calculate total progress from all content
        $totalProgressSum = 0;
        $completedContent = 0;

        foreach ($module->content as $content) {
            $progressRecord = $progressRecords->where('content_id', $content->id)->first();

            if ($progressRecord) {
                $totalProgressSum += floatval($progressRecord->completion_percentage);
                if ($progressRecord->is_completed) {
                    $completedContent++;
                }
            }
            // If no progress record, it contributes 0 to the sum
        }

        // ✅ FIXED: Calculate average progress percentage
        $averageProgress = round($totalProgressSum / $totalContent, 2);

        $result = [
            'total_content' => $totalContent,
            'completed_content' => $completedContent,
            'completion_percentage' => $averageProgress,
            'is_completed' => $totalContent > 0 && $completedContent >= $totalContent,
        ];



        return $result;
    }

    /**
     * Get content progress for user
     */
    private function getContentProgress(ModuleContent $content, int $userId): array
    {
        $progress = UserContentProgress::where('user_id', $userId)
            ->where('content_id', $content->id)
            ->first();

        $result = [
            'is_completed' => $progress?->is_completed ?? false,
            'completion_percentage' => $progress?->completion_percentage ?? 0,
            'time_spent' => $progress?->watch_time ?? 0,
            'last_accessed' => $progress?->last_accessed_at?->toDateTimeString(),
        ];

        return $result;
    }

    /**
     * ✅ ENHANCED: Calculate course progress with detailed debugging
     */
    private function calculateCourseProgress(int $courseId, int $userId): float
    {


        // Get all required content in the course
        $totalContent = ModuleContent::whereHas('module', function ($query) use ($courseId) {
            $query->where('course_online_id', $courseId)->where('is_required', true);
        })->where('is_required', true)->count();

        if ($totalContent === 0) {

            return 0.0;
        }

        // ✅ FIXED: Get sum of all progress percentages instead of just counting completed
        $progressRecords = UserContentProgress::where('user_id', $userId)
            ->where('course_online_id', $courseId)
            ->whereHas('content', function ($query) {
                $query->where('is_required', true);
            })
            ->get();

        $totalProgressSum = $progressRecords->sum('completion_percentage');



        // ✅ FIXED: Calculate average progress
        $averageProgress = round($totalProgressSum / $totalContent, 2);



        return $averageProgress;
    }

    // ===== REST OF YOUR METHODS (UNCHANGED) =====

    private function isContentCompleted(ModuleContent $content, int $userId): bool
    {
        return UserContentProgress::where('user_id', $userId)
            ->where('content_id', $content->id)
            ->where('is_completed', true)
            ->exists();
    }

    private function isModuleCompleted(CourseModule $module, int $userId): bool
    {
        $requiredContent = $module->content()->where('is_required', true)->count();
        $completedContent = UserContentProgress::where('user_id', $userId)
            ->where('module_id', $module->id)
            ->where('is_completed', true)
            ->count();

        return $requiredContent > 0 && $completedContent >= $requiredContent;
    }

    private function isModuleUnlocked(CourseModule $module, int $userId): bool
    {
        if ($module->order_number === 1) {
            return true;
        }

        $previousModules = $module->courseOnline->modules()
            ->where('order_number', '<', $module->order_number)
            ->where('is_required', true)
            ->get();

        foreach ($previousModules as $prevModule) {
            if (!$this->isModuleCompleted($prevModule, $userId)) {
                return false;
            }
        }

        return true;
    }

    private function isContentUnlocked(ModuleContent $content, int $userId): bool
    {
        if (!$this->isModuleUnlocked($content->module, $userId)) {
            return false;
        }

        if ($content->order_number === 1) {
            return true;
        }

        $previousContent = $content->module->content()
            ->where('order_number', '<', $content->order_number)
            ->where('is_required', true)
            ->get();

        foreach ($previousContent as $prevContent) {
            if (!$this->isContentCompleted($prevContent, $userId)) {
                return false;
            }
        }

        return true;
    }

    private function getNextUnlockedContent(CourseOnline $courseOnline, int $userId)
    {
        $modules = $courseOnline->modules()->orderBy('order_number')->get();

        foreach ($modules as $module) {
            if (!$this->isModuleUnlocked($module, $userId)) {
                continue;
            }

            $content = $module->content()->orderBy('order_number')->get();
            foreach ($content as $item) {
                if ($this->isContentUnlocked($item, $userId) && !$this->isContentCompleted($item, $userId)) {
                    return $item;
                }
            }
        }

        return null;
    }

    private function getCompletedModulesCount(int $courseId, int $userId): int
    {
        $course = CourseOnline::find($courseId);
        $completedCount = 0;

        foreach ($course->modules()->where('is_required', true)->get() as $module) {
            if ($this->isModuleCompleted($module, $userId)) {
                $completedCount++;
            }
        }

        return $completedCount;
    }

    private function calculateLearningStreak(int $userId): int
    {
        return 0; // Placeholder
    }

    /**
     * ✅ ENHANCED: Format time with logging
     */
    private function formatTimeSpent(?int $minutes): string
    {
        if (!$minutes || $minutes <= 0) {
            return '0 minutes';
        }

        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0) {
            return $hours . 'h ' . $mins . 'm';
        }

        return $mins . 'm';
    }

    public function completeCourse(Request $request, CourseOnline $courseOnline)
    {
        $user = Auth::user();

       ;

        // Validate assignment exists
        $assignment = CourseOnlineAssignment::where('course_online_id', $courseOnline->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$assignment) {
            return redirect()->route('courses-online.index')
                ->with('error', 'You do not have access to this course.');
        }

        // Check if already completed
        if ($assignment->status === 'completed') {
            return redirect()->route('courses-online.index')
                ->with('info', 'Course already completed!');
        }

        // Validate completion eligibility
        $currentProgress = $this->calculateCourseProgress($courseOnline->id, $user->id);

        if ($currentProgress < 85) {
            return redirect()->back()
                ->with('error', 'You must complete at least 85% of the course content before finishing.');
        }

        try {
            // Complete the course
            $completedAt = now();
            $finalTimeSpent = $this->calculateAssignmentTimeSpent($courseOnline->id, $user->id);

            $assignment->update([
                'status' => 'completed',
                'progress_percentage' => 100,
                'completed_at' => $completedAt,
                'total_time_spent' => $finalTimeSpent,
            ]);

            // ✅ INERTIA REDIRECT: Navigate to success/dashboard page
            return redirect()->route('courses-online.index')
                ->with('success', 'Congratulations! You have successfully completed the course!')
                ->with('completed_course', [
                    'name' => $courseOnline->name,
                    'completed_at' => $completedAt->format('M d, Y H:i'),
                    'time_spent' => $this->formatTimeSpent($finalTimeSpent),
                ]);

        } catch (\Exception $e) {


            return redirect()->back()
                ->with('error', 'Failed to complete course. Please try again.');
        }
    }

    private function getRecommendedCourses(int $userId, int $completedCourseId): array
    {
        try {
            // Get courses at similar or next difficulty level
            $completedCourse = CourseOnline::find($completedCourseId);

            $recommendedCourses = CourseOnline::where('is_active', true)
                ->where('id', '!=', $completedCourseId)
                ->where('difficulty_level', '>=', $completedCourse->difficulty_level ?? 'beginner')
                ->whereDoesntHave('assignments', function($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->limit(3)
                ->get(['id', 'name', 'description', 'difficulty_level', 'estimated_duration'])
                ->toArray();



            return $recommendedCourses;

        } catch (\Exception $e) {


            return [];
        }
    }

    /**
     * ✅ OPTIONAL: Generate course certificate (placeholder)
     */
    private function generateCourseCertificate(CourseOnlineAssignment $assignment): ?array
    {
        // This is a placeholder - implement your certificate generation logic
        // You might want to create a Certificate model and generate PDF


        return [
            'id' => 'cert_' . $assignment->id,
            'course_name' => $assignment->courseOnline->name,
            'user_name' => $assignment->user->name,
            'completed_at' => $assignment->completed_at,
            'download_url' => null, // URL to download certificate PDF
        ];
    }

    /**
     * ✅ OPTIONAL: Send course completion notification (placeholder)
     */
    private function sendCourseCompletionNotification(CourseOnlineAssignment $assignment): void
    {
        // This is a placeholder - implement your notification logic
        // You might want to send email, push notification, etc.


        // Example implementation:
        // Mail::to($assignment->user->email)->send(new CourseCompletedNotification($assignment));
    }


    private function calculateTotalMinutes(int $userId): int
    {


// Get all learning sessions for this user
        $allSessions = LearningSession::where('user_id', $userId)
            ->select(['id', 'total_duration_minutes', 'course_online_id', 'session_start'])
            ->get();



// Only include positive duration values
        $positiveSessions = $allSessions->where('total_duration_minutes', '>', 0);
        $totalMinutes = $positiveSessions->sum('total_duration_minutes');

// Ensure we never return negative values
        $totalMinutes = max(0, $totalMinutes);



        return (int) $totalMinutes;
    }
}
