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
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CourseOnlineController extends Controller
{
    /**
     * Display user's learning dashboard
     */
    public function index()
    {
        $user = Auth::user();

        // ✅ DEBUG: Dashboard access
        Log::info('=== USER DASHBOARD DEBUG START ===', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'timestamp' => now(),
        ]);

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

                // ✅ DEBUG: Assignment processing start
                Log::info('🔍 Processing assignment START', [
                    'assignment_id' => $assignment->id,
                    'course_id' => $assignment->courseOnline->id,
                    'course_name' => $assignment->courseOnline->name,
                    'original_progress' => $assignment->progress_percentage,
                    'original_total_time_spent' => $assignment->total_time_spent,
                ]);

                // ✅ FIXED: Calculate real-time assignment progress
                $realTimeProgress = $this->calculateCourseProgress($assignment->courseOnline->id, $user->id);

                // ✅ DEBUG: Progress comparison
                Log::info('🔍 Progress comparison', [
                    'assignment_id' => $assignment->id,
                    'stored_progress' => $assignment->progress_percentage,
                    'calculated_progress' => $realTimeProgress,
                    'needs_update' => $assignment->progress_percentage != $realTimeProgress,
                ]);

                // ✅ UPDATE: Update assignment with real progress if different
                if ($assignment->progress_percentage != $realTimeProgress) {
                    $assignment->update(['progress_percentage' => $realTimeProgress]);
                    $assignment->refresh(); // Refresh the model with new data

                    Log::info('✅ Assignment progress updated', [
                        'assignment_id' => $assignment->id,
                        'old_progress' => $assignment->getOriginal('progress_percentage'),
                        'new_progress' => $assignment->progress_percentage,
                    ]);
                }

                // ✅ DEBUG: Calculate time spent for this assignment
                $timeSpentCalculation = $this->calculateAssignmentTimeSpent($assignment->courseOnline->id, $user->id);

                Log::info('🔍 Assignment time calculation', [
                    'assignment_id' => $assignment->id,
                    'course_id' => $assignment->courseOnline->id,
                    'calculated_time_minutes' => $timeSpentCalculation,
                    'stored_total_time_spent' => $assignment->total_time_spent,
                    'formatted_time' => $this->formatTimeSpent($timeSpentCalculation),
                ]);

                // Get next content for in-progress courses
                $nextContent = null;
                if ($assignment->status === 'in_progress' && $assignment->currentModule) {
                    $nextContent = $this->getNextUnlockedContent($assignment->courseOnline, $user->id);

                    Log::info('🔍 Next content lookup', [
                        'assignment_id' => $assignment->id,
                        'current_module_id' => $assignment->currentModule->id,
                        'next_content_found' => $nextContent ? 'YES' : 'NO',
                        'next_content_id' => $nextContent?->id,
                        'next_content_title' => $nextContent?->title,
                    ]);
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

                Log::info('🔍 Assignment data final', [
                    'assignment_id' => $assignment->id,
                    'final_progress' => $assignmentData['progress_percentage'],
                    'final_time_spent' => $assignmentData['time_spent'],
                    'status' => $assignmentData['status'],
                    // ✅ NEW: Log deadline info
                    'deadline_status' => $deadlineInfo['status'],
                    'days_remaining' => $deadlineInfo['days_remaining'],
                ]);

                return $assignmentData;
            });

        // ✅ DEBUG: Calculate total hours with detailed logging
        $totalHours = $this->calculateTotalHours($user->id);

        Log::info('🔍 Total hours calculation for dashboard', [
            'user_id' => $user->id,
            'calculated_hours' => $totalHours,
        ]);

        // Calculate user statistics
        $stats = [
            'total_assignments' => $assignments->count(),
            'completed_courses' => $assignments->where('status', 'completed')->count(),
            'in_progress_courses' => $assignments->where('status', 'in_progress')->count(),
            'total_hours_spent' => $totalHours,
            'average_completion_rate' => $assignments->avg('progress_percentage') ?? 0,
            'certificates_earned' => $assignments->where('status', 'completed')->count(),
            // ✅ NEW: Add deadline-related stats
            'overdue_courses' => $assignments->where('deadline_info.status', 'overdue')->count(),
            'due_soon_courses' => $assignments->where('deadline_info.status', 'due_soon')->count(),
        ];

        // ✅ DEBUG: Final dashboard stats
        Log::info('🔍 Dashboard stats final', [
            'user_id' => $user->id,
            'stats' => $stats,
        ]);

        Log::info('=== USER DASHBOARD DEBUG END ===');

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

        Log::info('=== USER COURSE SHOW DEBUG START ===', [
            'user_id' => $user->id,
            'course_id' => $courseOnline->id,
            'course_name' => $courseOnline->name,
        ]);

        // Check if user has access to this course
        $assignment = CourseOnlineAssignment::where('course_online_id', $courseOnline->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$assignment) {
            Log::warning('🚫 User denied access to course', [
                'user_id' => $user->id,
                'course_id' => $courseOnline->id,
                'reason' => 'No assignment found',
            ]);

            return redirect()->route('courses-online.index')
                ->with('error', 'You do not have access to this course.');
        }

        // ✅ DEBUG: Assignment found
        Log::info('✅ Assignment found', [
            'user_id' => $user->id,
            'assignment_id' => $assignment->id,
            'assignment_status' => $assignment->status,
            'stored_progress' => $assignment->progress_percentage,
            'stored_total_time_spent' => $assignment->total_time_spent,
        ]);

        // ✅ FIXED: Update assignment progress with current calculation
        $currentProgress = $this->calculateCourseProgress($courseOnline->id, $user->id);
        if ($assignment->progress_percentage != $currentProgress) {
            Log::info('🔄 Updating assignment progress', [
                'assignment_id' => $assignment->id,
                'old_progress' => $assignment->progress_percentage,
                'new_progress' => $currentProgress,
            ]);

            $assignment->update(['progress_percentage' => $currentProgress]);
            $assignment->refresh();
        }

        $courseOnline->load(['modules.content.video']);

        // Get user progress for each module
        $modulesWithProgress = $courseOnline->modules()
            ->orderBy('order_number')
            ->get()
            ->map(function ($module) use ($user) {
                $progress = $this->getModuleProgress($module, $user->id);

                Log::info('🔍 Module progress in show', [
                    'user_id' => $user->id,
                    'module_id' => $module->id,
                    'module_name' => $module->name,
                    'progress' => $progress,
                ]);

                return [
                    'id' => $module->id,
                    'name' => $module->name,
                    'description' => $module->description,
                    'order_number' => $module->order_number,
                    'estimated_duration' => $module->estimated_duration,
                    'is_required' => $module->is_required,
                    'is_unlocked' => $this->isModuleUnlocked($module, $user->id),
                    'progress' => $progress,
                    'content' => $module->content()->orderBy('order_number')->get()->map(function ($content) use ($user) {
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
            Log::info('🚀 Marking assignment as started', [
                'assignment_id' => $assignment->id,
                'user_id' => $user->id,
            ]);

            $assignment->update([
                'status' => 'in_progress',
                'started_at' => now(),
                'current_module_id' => $courseOnline->modules()->orderBy('order_number')->first()?->id,
            ]);
        }

        // ✅ DEBUG: Calculate time spent for assignment display
        $assignmentTimeSpent = $this->calculateAssignmentTimeSpent($courseOnline->id, $user->id);

        Log::info('🔍 Assignment time for display', [
            'assignment_id' => $assignment->id,
            'calculated_time_minutes' => $assignmentTimeSpent,
            'formatted_time' => $this->formatTimeSpent($assignmentTimeSpent),
        ]);

        // ✅ NEW: Calculate deadline info for course show
        $deadlineInfo = $this->calculateDeadlineInfo($assignment);

        Log::info('=== USER COURSE SHOW DEBUG END ===');

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
                // ✅ NEW: Add course deadline info
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
                // ✅ DEBUG: Use calculated time
                'time_spent' => $this->formatTimeSpent($assignmentTimeSpent),
                // ✅ NEW: Add assignment deadline info
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
        Log::info('🔍 Calculating assignment time spent', [
            'user_id' => $userId,
            'course_id' => $courseId,
        ]);

        // Get all learning sessions for this user and course
        $sessions = LearningSession::where('user_id', $userId)
            ->where('course_online_id', $courseId)
            ->select(['id', 'total_duration_minutes', 'session_start', 'session_end'])
            ->get();

        Log::info('🔍 Learning sessions found', [
            'user_id' => $userId,
            'course_id' => $courseId,
            'sessions_count' => $sessions->count(),
            'sessions_data' => $sessions->map(function($session) {
                return [
                    'id' => $session->id,
                    'duration' => $session->total_duration_minutes,
                    'start' => $session->session_start,
                    'end' => $session->session_end,
                ];
            })->toArray(),
        ]);

        // ✅ FIXED: Only sum positive durations
        $totalMinutes = $sessions->where('total_duration_minutes', '>', 0)->sum('total_duration_minutes');
        $negativeCount = $sessions->where('total_duration_minutes', '<=', 0)->count();

        Log::info('🔍 Assignment time calculation result', [
            'user_id' => $userId,
            'course_id' => $courseId,
            'total_positive_minutes' => $totalMinutes,
            'negative_sessions_count' => $negativeCount,
            'final_result' => max(0, $totalMinutes),
        ]);

        return max(0, intval($totalMinutes));
    }

    /**
     * ✅ ENHANCED: Calculate total hours spent by user with detailed logging
     */
    private function calculateTotalHours(int $userId): float
    {
        Log::info('🔍 Calculating total hours for user', [
            'user_id' => $userId,
        ]);

        // Get all learning sessions for this user
        $allSessions = LearningSession::where('user_id', $userId)
            ->select(['id', 'total_duration_minutes', 'course_online_id', 'session_start'])
            ->get();

        Log::info('🔍 All user learning sessions', [
            'user_id' => $userId,
            'total_sessions' => $allSessions->count(),
            'positive_sessions' => $allSessions->where('total_duration_minutes', '>', 0)->count(),
            'zero_sessions' => $allSessions->where('total_duration_minutes', '=', 0)->count(),
            'negative_sessions' => $allSessions->where('total_duration_minutes', '<', 0)->count(),
        ]);

        // ✅ DEBUG: Show sample session data
        $sampleSessions = $allSessions->take(5);
        Log::info('🔍 Sample sessions data', [
            'user_id' => $userId,
            'sample_sessions' => $sampleSessions->map(function($session) {
                return [
                    'id' => $session->id,
                    'course_id' => $session->course_online_id,
                    'duration' => $session->total_duration_minutes,
                    'start' => $session->session_start,
                ];
            })->toArray(),
        ]);

        // ✅ FIXED: Only include positive duration values
        $positiveSessions = $allSessions->where('total_duration_minutes', '>', 0);
        $totalMinutes = $positiveSessions->sum('total_duration_minutes');
        $totalHours = round(max(0, $totalMinutes) / 60, 1);

        Log::info('🔍 Total hours calculation final', [
            'user_id' => $userId,
            'positive_sessions_count' => $positiveSessions->count(),
            'total_positive_minutes' => $totalMinutes,
            'calculated_hours' => $totalHours,
        ]);

        return $totalHours;
    }

    /**
     * ✅ ENHANCED: Get module progress with detailed debugging
     */
    private function getModuleProgress(CourseModule $module, int $userId): array
    {
        Log::info('🔍 Getting module progress', [
            'user_id' => $userId,
            'module_id' => $module->id,
            'module_name' => $module->name,
        ]);

        $totalContent = $module->content()->count();

        if ($totalContent === 0) {
            Log::info('⚠️ Module has no content', [
                'user_id' => $userId,
                'module_id' => $module->id,
            ]);

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

        Log::info('🔍 Module progress records', [
            'user_id' => $userId,
            'module_id' => $module->id,
            'total_content' => $totalContent,
            'progress_records_found' => $progressRecords->count(),
            'progress_data' => $progressRecords->map(function($record) {
                return [
                    'content_id' => $record->content_id,
                    'completion_percentage' => $record->completion_percentage,
                    'is_completed' => $record->is_completed,
                ];
            })->toArray(),
        ]);

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

        Log::info('🔍 Module progress result', [
            'user_id' => $userId,
            'module_id' => $module->id,
            'calculation' => [
                'total_content' => $totalContent,
                'total_progress_sum' => $totalProgressSum,
                'average_progress' => $averageProgress,
                'completed_content' => $completedContent,
            ],
            'result' => $result,
        ]);

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
        Log::info('🔍 Calculating course progress', [
            'user_id' => $userId,
            'course_id' => $courseId,
        ]);

        // Get all required content in the course
        $totalContent = ModuleContent::whereHas('module', function ($query) use ($courseId) {
            $query->where('course_online_id', $courseId)->where('is_required', true);
        })->where('is_required', true)->count();

        if ($totalContent === 0) {
            Log::info('⚠️ Course has no required content', [
                'user_id' => $userId,
                'course_id' => $courseId,
            ]);
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

        Log::info('🔍 Course progress calculation', [
            'user_id' => $userId,
            'course_id' => $courseId,
            'total_required_content' => $totalContent,
            'progress_records_count' => $progressRecords->count(),
            'total_progress_sum' => $totalProgressSum,
            'progress_records' => $progressRecords->map(function($record) {
                return [
                    'content_id' => $record->content_id,
                    'completion_percentage' => $record->completion_percentage,
                ];
            })->toArray(),
        ]);

        // ✅ FIXED: Calculate average progress
        $averageProgress = round($totalProgressSum / $totalContent, 2);

        Log::info('🔍 Course progress final result', [
            'user_id' => $userId,
            'course_id' => $courseId,
            'calculation' => [
                'total_content' => $totalContent,
                'total_progress_sum' => $totalProgressSum,
                'average_progress' => $averageProgress,
            ],
        ]);

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

        Log::info('=== COURSE COMPLETION REQUEST START ===', [
            'user_id' => $user->id,
            'course_id' => $courseOnline->id,
        ]);

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

            Log::info('✅ Course completed successfully', [
                'assignment_id' => $assignment->id,
                'final_time_spent' => $finalTimeSpent,
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
            Log::error('❌ Course completion failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

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

            Log::info('🔍 Recommended courses found', [
                'user_id' => $userId,
                'completed_course_id' => $completedCourseId,
                'recommended_count' => count($recommendedCourses),
            ]);

            return $recommendedCourses;

        } catch (\Exception $e) {
            Log::warning('Failed to get recommended courses', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

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

        Log::info('📜 Certificate generation placeholder', [
            'assignment_id' => $assignment->id,
            'user_id' => $assignment->user_id,
            'course_id' => $assignment->course_online_id,
        ]);

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

        Log::info('📧 Completion notification placeholder', [
            'assignment_id' => $assignment->id,
            'user_email' => $assignment->user->email,
            'course_name' => $assignment->courseOnline->name,
        ]);

        // Example implementation:
        // Mail::to($assignment->user->email)->send(new CourseCompletedNotification($assignment));
    }
}
