<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Department;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\CourseCompletion;
use App\Services\CsvExportService;
use App\Services\MonthlyKpiService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Vtiful\Kernel\Excel;

class ReportController extends Controller
{
    protected $reportService;
    protected $csvExportService;
    protected $monthlyKpiService; // 🎯 NEW SERVICE


    public function __construct(ReportService $reportService, CsvExportService $csvExportService, MonthlyKpiService $monthlyKpiService)
    {
        $this->reportService = $reportService;
        $this->csvExportService = $csvExportService;
        $this->monthlyKpiService = $monthlyKpiService; // 🎯 INJECT NEW SERVICE
    }

    /**
     * Display the reports dashboard with comprehensive filtering
     */
    public function index(Request $request)
    {
        // Get filters from request
        $filters = $request->only(['date_from', 'date_to', 'course_id', 'quiz_id', 'user_id']);

        // Get filtered analytics for all sections
        $analytics = $this->getFilteredAnalytics($filters);

        // Get dropdown data for filters
        $courses = Course::select('id', 'name')->orderBy('name')->get();
        $quizzes = Quiz::where('status', 'published')->select('id', 'title')->orderBy('title')->get();
        $users = User::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Reports/Index', [
            'analytics' => $analytics,
            'courses' => $courses,
            'quizzes' => $quizzes,
            'users' => $users,
            'filters' => $filters
        ]);
    }

    /**
     * Get filtered analytics for all sections
     */
    private function getFilteredAnalytics($filters = [])
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $courseId = $filters['course_id'] ?? null;
        $quizId = $filters['quiz_id'] ?? null;
        $userId = $filters['user_id'] ?? null;

        // 1. USERS ANALYTICS (with filters)
        $usersQuery = User::query();

        if ($dateFrom) {
            $usersQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $usersQuery->whereDate('created_at', '<=', $dateTo);
        }
        if ($courseId) {
            $usersQuery->whereHas('courses', function($q) use ($courseId) {
                $q->where('courses.id', $courseId);
            });
        }
        if ($userId) {
            $usersQuery->where('id', $userId);
        }

        $totalUsers = $usersQuery->count();
        $activeUsers = (clone $usersQuery)->where('updated_at', '>=', now()->subDays(30))->count();

        $usersAnalytics = [
            'total' => $totalUsers,
            'active' => $activeUsers,
            'active_percentage' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0,
        ];

        // 2. COURSES ANALYTICS (with filters)
        $coursesQuery = Course::query();

        if ($dateFrom) {
            $coursesQuery->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $coursesQuery->whereDate('created_at', '<=', $dateTo);
        }
        if ($courseId) {
            $coursesQuery->where('id', $courseId);
        }
        if ($userId) {
            $coursesQuery->whereHas('users', function($q) use ($userId) {
                $q->where('users.id', $userId);
            });
        }

        $totalCourses = $coursesQuery->count();
        $activeCourses = (clone $coursesQuery)->where('status', 'active')->count();
        $completedCourses = CourseCompletion::query()
            ->when($courseId, fn($q) => $q->where('course_id', $courseId))
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($dateFrom, fn($q) => $q->whereDate('completed_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('completed_at', '<=', $dateTo))
            ->distinct('course_id')
            ->count();

        $coursesAnalytics = [
            'total' => $totalCourses,
            'active' => $activeCourses,
            'completed' => $completedCourses,
        ];

        // 3. REGISTRATIONS ANALYTICS (with filters)
        $totalRegistrations = 0;
        $completedRegistrations = 0;

        // Check if course_user table exists
        if (Schema::hasTable('course_user')) {
            $registrationsQuery = DB::table('course_user');

            if ($courseId) {
                $registrationsQuery->where('course_id', $courseId);
            }
            if ($userId) {
                $registrationsQuery->where('user_id', $userId);
            }
            if ($dateFrom && Schema::hasColumn('course_user', 'created_at')) {
                $registrationsQuery->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo && Schema::hasColumn('course_user', 'created_at')) {
                $registrationsQuery->whereDate('created_at', '<=', $dateTo);
            }

            $totalRegistrations = $registrationsQuery->count();
        }

        $completedRegistrations = CourseCompletion::query()
            ->when($courseId, fn($q) => $q->where('course_id', $courseId))
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($dateFrom, fn($q) => $q->whereDate('completed_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('completed_at', '<=', $dateTo))
            ->count();

        $registrationsAnalytics = [
            'total' => $totalRegistrations,
            'completed' => $completedRegistrations,
            'completion_rate' => $totalRegistrations > 0 ? round(($completedRegistrations / $totalRegistrations) * 100, 1) : 0,
        ];

        // 4. ATTENDANCE ANALYTICS (with filters)
        $totalClockings = 0;
        $avgDuration = 0;
        $avgRating = 0;

        // Check if clockings table exists
        if (Schema::hasTable('clockings')) {
            $attendanceQuery = DB::table('clockings');

            if ($courseId && Schema::hasColumn('clockings', 'course_id')) {
                $attendanceQuery->where('course_id', $courseId);
            }
            if ($userId && Schema::hasColumn('clockings', 'user_id')) {
                $attendanceQuery->where('user_id', $userId);
            }
            if ($dateFrom) {
                $attendanceQuery->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $attendanceQuery->whereDate('created_at', '<=', $dateTo);
            }

            $totalClockings = $attendanceQuery->count();
            $avgDuration = $attendanceQuery->avg('duration_in_minutes') ?? 0;
            $avgRating = Schema::hasColumn('clockings', 'rating') ? $attendanceQuery->avg('rating') ?? 0 : 0;
        }

        $attendanceAnalytics = [
            'total_clockings' => $totalClockings,
            'average_duration' => round($avgDuration, 1),
            'average_rating' => round($avgRating, 1),
        ];

        // 5. QUIZ ANALYTICS (with filters)
        $quizAnalytics = $this->getQuizAnalytics($filters);

        // 6. TRENDS DATA (with filters)
        $trendsAnalytics = $this->getTrendsAnalytics($filters);

        return [
            'users' => $usersAnalytics,
            'courses' => $coursesAnalytics,
            'registrations' => $registrationsAnalytics,
            'attendance' => $attendanceAnalytics,
            'quiz' => $quizAnalytics,
            'trends' => $trendsAnalytics,
        ];
    }

    /**
     * Get Quiz Analytics data with optional filters
     */
    private function getQuizAnalytics($filters = [])
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $courseId = $filters['course_id'] ?? null;
        $quizId = $filters['quiz_id'] ?? null;
        $userId = $filters['user_id'] ?? null;

        $query = QuizAttempt::query();

        // Apply filters
        if ($dateFrom) {
            $query->whereDate('completed_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('completed_at', '<=', $dateTo);
        }
        if ($courseId) {
            $query->whereHas('quiz', function ($q) use ($courseId) {
                $q->where('course_id', $courseId);
            });
        }
        if ($quizId) {
            $query->where('quiz_id', $quizId);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }

        // Get the filtered data
        $totalAttempts = $query->count();
        $passedAttempts = (clone $query)->where('passed', true)->count();
        $failedAttempts = (clone $query)->where('passed', false)->count();
        $averageScore = round((clone $query)->avg('total_score') ?? 0, 1);

        // Get trending quiz data
        $topQuizzes = QuizAttempt::with('quiz')
            ->when($dateFrom, fn($q) => $q->whereDate('completed_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('completed_at', '<=', $dateTo))
            ->when($courseId, fn($q) => $q->whereHas('quiz', fn($subQ) => $subQ->where('course_id', $courseId)))
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->selectRaw('quiz_id, COUNT(*) as attempt_count, AVG(total_score) as avg_score')
            ->groupBy('quiz_id')
            ->orderByDesc('attempt_count')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'quiz_title' => $item->quiz->title ?? 'Unknown Quiz',
                    'attempt_count' => $item->attempt_count,
                    'avg_score' => round($item->avg_score, 1),
                ];
            });

        // Get monthly quiz trends
        $monthlyTrends = QuizAttempt::query()
            ->when($dateFrom, fn($q) => $q->whereDate('completed_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('completed_at', '<=', $dateTo))
            ->when($courseId, fn($q) => $q->whereHas('quiz', fn($subQ) => $subQ->where('course_id', $courseId)))
            ->when($quizId, fn($q) => $q->where('quiz_id', $quizId))
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->selectRaw('DATE_FORMAT(completed_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->limit(12)
            ->pluck('count', 'month')
            ->toArray();

        return [
            'total_attempts' => $totalAttempts,
            'passed_attempts' => $passedAttempts,
            'failed_attempts' => $failedAttempts,
            'average_score' => $averageScore,
            'pass_rate' => $totalAttempts > 0 ? round(($passedAttempts / $totalAttempts) * 100, 1) : 0,
            'top_quizzes' => $topQuizzes,
            'monthly_trends' => $monthlyTrends,
        ];
    }

    /**
     * Get trends analytics with filters
     */
    private function getTrendsAnalytics($filters = [])
    {
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $courseId = $filters['course_id'] ?? null;
        $userId = $filters['user_id'] ?? null;

        // Monthly attendance trends
        $monthlyAttendance = [];
        if (Schema::hasTable('clockings')) {
            $monthlyAttendance = DB::table('clockings')
                ->when($dateFrom, fn($q) => $q->whereDate('created_at', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('created_at', '<=', $dateTo))
                ->when($courseId && Schema::hasColumn('clockings', 'course_id'), fn($q) => $q->where('course_id', $courseId))
                ->when($userId && Schema::hasColumn('clockings', 'user_id'), fn($q) => $q->where('user_id', $userId))
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->groupBy('month')
                ->orderBy('month')
                ->limit(12)
                ->pluck('count', 'month')
                ->toArray();
        }

        // Popular courses - Simplified version
        $popularCourses = Course::query()
            ->when($courseId, fn($q) => $q->where('id', $courseId))
            ->limit(8)
            ->get()
            ->map(function ($course) use ($userId) {
                // Count users enrolled in each course
                $registrationCount = $course->users()
                    ->when($userId, fn($q) => $q->where('users.id', $userId))
                    ->count();

                return [
                    'name' => $course->name,
                    'registrations' => $registrationCount,
                ];
            })
            ->filter(function ($course) {
                return $course['registrations'] > 0;
            })
            ->sortByDesc('registrations')
            ->values();

        return [
            'monthly_attendance' => $monthlyAttendance,
            'popular_courses' => $popularCourses,
        ];
    }

    /**
     * Display course registrations report
     */
    public function courseRegistrations(Request $request)
    {
        $filters = $request->only(['course_id', 'status', 'date_from', 'date_to']);
        $registrations = $this->reportService->getCourseRegistrationsReport($filters);
        $courses = Course::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Reports/CourseRegistrations', [
            'registrations' => $registrations,
            'courses' => $courses,
            'filters' => $filters
        ]);
    }

    /**
     * Display attendance report
     */
    public function attendance(Request $request)
    {
        $filters = $request->only(['user_id', 'date_from', 'date_to', 'course_id']);
        $attendance = $this->reportService->getAttendanceReport($filters);
        $users = User::select('id', 'name')->orderBy('name')->get();
        $courses = Course::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Reports/Attendance', [
            'attendance' => $attendance,
            'users' => $users,
            'courses' => $courses,
            'filters' => $filters
        ]);
    }

    /**
     * Export attendance records to CSV
     */
    public function exportAttendance(Request $request)
    {
        $filters = $request->only(['user_id', 'date_from', 'date_to', 'course_id']);
        $attendance = $this->reportService->getAttendanceReport($filters);

        $headers = [
            'ID', 'User Name', 'User Email', 'Course Name', 'Clock In', 'Clock Out',
            'Duration (minutes)', 'Rating', 'Comment'
        ];

        $data = $attendance->map(function($record) {
            return [
                'id' => $record['id'],
                'user_name' => $record['user_name'],
                'user_email' => $record['user_email'],
                'course_name' => $record['course_name'] ?? 'General Attendance',
                'clock_in' => $record['clock_in'],
                'clock_out' => $record['clock_out'],
                'duration_in_minutes' => $record['duration_in_minutes'],
                'rating' => $record['rating'],
                'comment' => $record['comment']
            ];
        });

        return $this->csvExportService->export(
            'attendance_' . date('Y-m-d') . '.csv',
            $headers,
            $data->toArray()
        );
    }

    /**
     * Display course completion report
     */
    public function courseCompletion(Request $request)
    {
        $filters = $request->only(['course_id', 'date_from', 'date_to']);
        $page = $request->get('page', 1);

        // ✅ START WITH CourseRegistration and PROPERLY JOIN CourseCompletion
        $query = CourseRegistration::query()
            ->join('users', 'course_registrations.user_id', '=', 'users.id')
            ->join('courses', 'course_registrations.course_id', '=', 'courses.id');

        // ✅ FIXED: Proper join with course completions for rating and feedback
        $query->leftJoin('course_completions', function($join) {
            $join->on('course_registrations.user_id', '=', 'course_completions.user_id')
                ->on('course_registrations.course_id', '=', 'course_completions.course_id');
        });

        if (Schema::hasTable('clockings')) {
            $query->leftJoin('clockings as latest_clocking', function ($join) {
                $join->on('course_registrations.user_id', '=', 'latest_clocking.user_id')
                    ->on('course_registrations.course_id', '=', 'latest_clocking.course_id')
                    ->whereRaw('latest_clocking.id = (SELECT MAX(id) FROM clockings WHERE clockings.user_id = course_registrations.user_id AND clockings.course_id = course_registrations.course_id)');
            });
        }

        $selectFields = [
            'course_registrations.id',
            'users.name as user_name',
            'users.email as user_email',
            'courses.name as course_name',
            'course_registrations.registered_at',
            'course_registrations.status as course_status',

            // ✅ FIXED: Get completion data, rating, and feedback from course_completions table
            'course_completions.completed_at',
            'course_completions.rating',
            'course_completions.feedback',

            // ✅ Add a computed status field that shows completion status
            DB::raw('CASE
            WHEN course_completions.completed_at IS NOT NULL THEN "completed"
            WHEN course_registrations.status = "in_progress" THEN "in_progress"
            ELSE course_registrations.status
        END as actual_status'),

            // ✅ Add completion percentage or progress if available
            DB::raw('CASE
            WHEN course_completions.completed_at IS NOT NULL THEN 100
            ELSE 0
        END as completion_percentage')
        ];

        if (Schema::hasTable('clockings')) {
            $selectFields[] = 'latest_clocking.comment';
        } else {
            $selectFields[] = DB::raw('NULL as comment');
        }

        $query->select($selectFields);

        // Apply filters
        if (!empty($filters['course_id'])) {
            $query->where('course_registrations.course_id', $filters['course_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where(function($q) use ($filters) {
                $q->whereDate('course_registrations.registered_at', '>=', $filters['date_from'])
                    ->orWhereDate('course_completions.completed_at', '>=', $filters['date_from']);
            });
        }

        if (!empty($filters['date_to'])) {
            $query->where(function($q) use ($filters) {
                $q->whereDate('course_registrations.registered_at', '<=', $filters['date_to'])
                    ->orWhereDate('course_completions.completed_at', '<=', $filters['date_to']);
            });
        }

        // ✅ ORDER BY: Show completed courses first, then by registration date
        $completions = $query->orderByDesc('course_completions.completed_at')
            ->orderByDesc('course_registrations.created_at')
            ->paginate(15)
            ->withQueryString();

        $courses = Course::select('id', 'name')->orderBy('name')->get();

        // ✅ ENHANCED DEBUG: Show completion statistics
        $stats = [
            'total_registrations' => CourseRegistration::count(),
            'total_completions' => CourseCompletion::count(),
            'completions_with_rating' => CourseCompletion::whereNotNull('rating')->count(),
            'completions_with_feedback' => CourseCompletion::whereNotNull('feedback')->count(),
            'average_rating' => round(CourseCompletion::whereNotNull('rating')->avg('rating'), 2)
        ];

        $debug = [
            'filter_count' => count(array_filter($filters)),
            'completion_count' => $completions->total(),
            'has_courses' => $courses->count() > 0,
            'current_page' => $completions->currentPage(),
            'per_page' => $completions->perPage(),
            'total_pages' => $completions->lastPage(),
            'stats' => $stats
        ];

        return Inertia::render('Admin/Reports/CourseCompletion', [
            'completions' => $completions,
            'courses' => $courses,
            'filters' => $filters,
            'debug' => $debug
        ]);
    }

    public function exportCourseCompletion(Request $request)
    {
        $filters = $request->only(['course_id', 'date_from', 'date_to']);

        // ✅ USE CourseRegistration as base (same as the report)
        $query = CourseRegistration::query()
            ->join('users', 'course_registrations.user_id', '=', 'users.id')
            ->join('courses', 'course_registrations.course_id', '=', 'courses.id')
            ->join('departments', 'users.department_id', '=', 'departments.id', 'left')
            ->join('user_levels', 'users.user_level_id', '=', 'user_levels.id', 'left');

        // Left join with course completions for additional completion data
        $query->leftJoin('course_completions', function($join) {
            $join->on('course_registrations.user_id', '=', 'course_completions.user_id')
                ->on('course_registrations.course_id', '=', 'course_completions.course_id');
        });

        if (Schema::hasTable('clockings')) {
            $query->leftJoin('clockings as latest_clocking', function ($join) {
                $join->on('course_registrations.user_id', '=', 'latest_clocking.user_id')
                    ->on('course_registrations.course_id', '=', 'latest_clocking.course_id')
                    ->whereRaw('latest_clocking.id = (SELECT MAX(id) FROM clockings WHERE clockings.user_id = course_registrations.user_id AND clockings.course_id = course_registrations.course_id)');
            });
        }

        // ✅ ENHANCED SELECT FIELDS with more user data
        $selectFields = [
            'course_registrations.id',
            'users.name as user_name',
            'users.email as user_email',
            'users.employee_code',
            'departments.name as department_name',
            'user_levels.name as user_level',
            'courses.name as course_name',
            'courses.level as course_level',
            'courses.duration as course_duration',
            'course_registrations.registered_at',
            'course_registrations.completed_at',
            'course_registrations.rating',
            'course_registrations.feedback',
            'course_registrations.status as course_status',
            'course_registrations.created_at as enrollment_date',
            // Additional completion data if available
            'course_completions.completed_at as completion_completed_at',
            'course_completions.rating as completion_rating',
            'course_completions.feedback as completion_feedback'
        ];

        if (Schema::hasTable('clockings')) {
            $selectFields[] = 'latest_clocking.comment';
            $selectFields[] = 'latest_clocking.created_at as last_activity_date';
        } else {
            $selectFields[] = DB::raw('NULL as comment');
            $selectFields[] = DB::raw('NULL as last_activity_date');
        }

        $query->select($selectFields);

        // ✅ ENHANCED FILTERING
        if (!empty($filters['course_id'])) {
            $query->where('course_registrations.course_id', $filters['course_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where(function($q) use ($filters) {
                $q->whereDate('course_registrations.registered_at', '>=', $filters['date_from'])
                    ->orWhereDate('course_registrations.completed_at', '>=', $filters['date_from'])
                    ->orWhereDate('course_registrations.created_at', '>=', $filters['date_from']);
            });
        }

        if (!empty($filters['date_to'])) {
            $query->where(function($q) use ($filters) {
                $q->whereDate('course_registrations.registered_at', '<=', $filters['date_to'])
                    ->orWhereDate('course_registrations.completed_at', '<=', $filters['date_to'])
                    ->orWhereDate('course_registrations.created_at', '<=', $filters['date_to']);
            });
        }

        $completions = $query->orderBy('course_registrations.created_at', 'desc')->get();

        // ✅ ENHANCED HEADERS with more data
        $headers = [
            'Registration ID',
            'User Name',
            'User Email',
            'Employee Code',
            'Department',
            'User Level',
            'Course Name',
            'Course Level',
            'Course Duration (hours)',
            'Status',
            'Enrollment Date',
            'Registered At',
            'Completed At',
            'Rating',
            'Feedback',
            'Comment',
            'Last Activity Date',
            'Days Since Enrollment',
            'Days to Complete',
            'Completion Rate'
        ];

        // ✅ ENHANCED DATA MAPPING with calculations
        $data = $completions->map(function($record) {
            // Calculate days since enrollment
            $enrollmentDate = $record->enrollment_date ? Carbon::parse($record->enrollment_date) : null;
            $completedDate = $record->completed_at ? Carbon::parse($record->completed_at) : null;
            $daysSinceEnrollment = $enrollmentDate ? $enrollmentDate->diffInDays(now()) : null;
            $daysToComplete = $enrollmentDate && $completedDate ? $enrollmentDate->diffInDays($completedDate) : null;

            // Determine completion rate
            $completionRate = match(strtolower($record->course_status ?? '')) {
                'completed' => '100%',
                'in_progress', 'in-progress', 'active' => '50%',
                'enrolled' => '25%',
                default => '0%'
            };

            return [
                'registration_id' => $record->id,
                'user_name' => $record->user_name,
                'user_email' => $record->user_email,
                'employee_code' => $record->employee_code ?: 'N/A',
                'department' => $record->department_name ?: 'N/A',
                'user_level' => $record->user_level ?: 'N/A',
                'course_name' => $record->course_name,
                'course_level' => $record->course_level ?: 'N/A',
                'course_duration' => $record->course_duration ?: 'N/A',
                'status' => $this->formatStatus($record->course_status),
                'enrollment_date' => $this->formatDateTime($record->enrollment_date),
                'registered_at' => $this->formatDateTime($record->registered_at),
                'completed_at' => $this->formatDateTime($record->completed_at),
                'rating' => $record->rating ? $record->rating . '/5' : 'N/A',
                'feedback' => $this->cleanText($record->feedback),
                'comment' => $this->cleanText($record->comment),
                'last_activity_date' => $this->formatDateTime($record->last_activity_date),
                'days_since_enrollment' => $daysSinceEnrollment !== null ? $daysSinceEnrollment . ' days' : 'N/A',
                'days_to_complete' => $daysToComplete !== null ? $daysToComplete . ' days' : 'N/A',
                'completion_rate' => $completionRate
            ];
        })->toArray();

        // ✅ ENHANCED FILENAME with filters info
        $filename = 'course_registrations_' . date('Y-m-d');

        if (!empty($filters['course_id'])) {
            $course = Course::find($filters['course_id']);
            $filename .= '_' . Str::slug($course->name ?? 'course_' . $filters['course_id']);
        }

        if (!empty($filters['date_from'])) {
            $filename .= '_from_' . $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $filename .= '_to_' . $filters['date_to'];
        }

        $filename .= '.csv';

        return $this->csvExportService->export(
            $filename,
            $headers,
            $data
        );
    }

// ✅ HELPER METHODS for formatting
    private function formatDateTime($datetime)
    {
        if (!$datetime) return 'N/A';

        try {
            return Carbon::parse($datetime)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            return 'Invalid Date';
        }
    }

    private function formatStatus($status)
    {
        if (!$status) return 'Unknown';

        return match(strtolower($status)) {
            'completed' => 'Completed',
            'enrolled' => 'Enrolled',
            'in_progress', 'in-progress' => 'In Progress',
            'active' => 'Active',
            'pending' => 'Pending',
            'cancelled', 'canceled' => 'Cancelled',
            'on_hold', 'on-hold' => 'On Hold',
            default => ucfirst($status)
        };
    }

    private function cleanText($text)
    {
        if (!$text) return 'N/A';

        // Remove line breaks and extra spaces for CSV compatibility
        $cleaned = preg_replace('/\s+/', ' ', $text);
        $cleaned = str_replace(['"', "\n", "\r"], ['""', ' ', ' '], $cleaned);

        return trim($cleaned);
    }

    /**
     * Export course registrations to CSV
     */
    public function exportCourseRegistrations(Request $request)
    {
        $filters = $request->only(['course_id', 'status', 'date_from', 'date_to']);
        $registrations = $this->reportService->getCourseRegistrationsReport($filters);

        $headers = [
            'ID', 'User Name', 'User Email', 'Course Name', 'Status',
            'Rating', 'Feedback', 'Registered At', 'Completed At'
        ];

        return $this->csvExportService->export(
            'course_registrations_' . date('Y-m-d') . '.csv',
            $headers,
            $registrations->toArray()
        );
    }

    /**
     * Display quiz attempts report
     */
    public function quizAttempts(Request $request)
    {
        $filters = $request->only(['quiz_id', 'status', 'date_from', 'date_to']);

        $quizId = $filters['quiz_id'] ?? null;
        $status = $filters['status'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $attempts = QuizAttempt::with(['user', 'quiz'])
            ->when($quizId, fn($q) => $q->where('quiz_id', $quizId))
            ->when($status, fn($q) => $q->where('passed', $status === 'passed'))
            ->when($dateFrom, fn($q) => $q->where('completed_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('completed_at', '<=', $dateTo . ' 23:59:59'))
            ->orderBy('completed_at', 'desc')
            ->paginate(10);

        $attempts->getCollection()->transform(function ($attempt) {
            return [
                'id' => $attempt->id,
                'user_name' => $attempt->user->name,
                'user_email' => $attempt->user->email,
                'quiz_title' => $attempt->quiz->title,
                'quiz_total_points' => $attempt->quiz->total_points,
                'total_score' => $attempt->total_score,
                'passed' => $attempt->passed,
                'attempt_number' => $attempt->attempt_number,
                'completed_at' => $attempt->completed_at?->format('Y-m-d H:i:s'),
            ];
        });

        $quizzes = Quiz::where('status', 'published')->get(['id', 'title']);

        return Inertia::render('Admin/Reports/QuizAttemptsReport', [
            'attempts' => $attempts,
            'quizzes' => $quizzes,
            'filters' => $filters,
        ]);
    }

    /**
     * Export quiz attempts to CSV
     */
    public function exportQuizAttempts(Request $request)
    {
        $filters = $request->only(['quiz_id', 'status', 'date_from', 'date_to']);

        $quizId = $filters['quiz_id'] ?? null;
        $status = $filters['status'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        $attempts = QuizAttempt::with(['user', 'quiz'])
            ->when($quizId, fn($q) => $q->where('quiz_id', $quizId))
            ->when($status, fn($q) => $q->where('passed', $status === 'passed'))
            ->when($dateFrom, fn($q) => $q->where('completed_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('completed_at', '<=', $dateTo . ' 23:59:59'))
            ->orderBy('completed_at', 'desc')
            ->get();

        $headers = [
            'ID', 'User Name', 'User Email', 'Quiz Title', 'Total Points', 'Score Achieved',
            'Percentage', 'Status', 'Attempt Number', 'Completed At'
        ];

        $data = $attempts->map(function($attempt) {
            return [
                'id' => $attempt->id,
                'user_name' => $attempt->user->name,
                'user_email' => $attempt->user->email,
                'quiz_title' => $attempt->quiz->title,
                'total_points' => $attempt->quiz->total_points,
                'score_achieved' => $attempt->total_score,
                'percentage' => $attempt->quiz->total_points > 0 ? round(($attempt->total_score / $attempt->quiz->total_points) * 100, 1) : 0,
                'status' => $attempt->passed ? 'Passed' : 'Failed',
                'attempt_number' => $attempt->attempt_number,
                'completed_at' => $attempt->completed_at?->format('Y-m-d H:i:s'),
            ];
        })->toArray();

        return $this->csvExportService->export(
            'quiz_attempts_' . date('Y-m-d') . '.csv',
            $headers,
            $data
        );
    }

    public function monthlyKpiDashboard(Request $request)
    {
        try {
            Log::info('🎯 Loading Monthly KPI Dashboard', [
                'user_id' => auth()->id(),
                'request_params' => $request->all()
            ]);

            // Get filters from request with defaults
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);
            $filters = $request->only(['department_id', 'course_id', 'user_level_id']);

            // Generate complete KPI data
            $kpiData = $this->monthlyKpiService->generateCompleteKpiReport($month, $year, $filters);

            // Get dropdown data for filters
            $filterData = $this->getFilterDropdownData();

            Log::info('✅ Monthly KPI Dashboard data loaded successfully', [
                'period' => "{$month}/{$year}",
                'filters_applied' => array_filter($filters),
                'data_sections' => array_keys($kpiData)
            ]);

            return Inertia::render('Admin/Reports/MonthlyKpiDashboard', [
                'kpiData' => $kpiData,
                'filterData' => $filterData,
                'currentFilters' => array_merge(['month' => $month, 'year' => $year], $filters),
                'pageTitle' => 'Monthly Training KPI Report',
                'lastUpdated' => Carbon::now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error loading Monthly KPI Dashboard', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to load KPI dashboard. Please try again.');
        }
    }

    /**
     * 🎯 NEW: AJAX endpoint for real-time KPI data updates
     */
    public function getKpiData(Request $request)
    {
        try {
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);
            $filters = $request->only(['department_id', 'course_id', 'user_level_id']);

            Log::info('📊 AJAX KPI data request', [
                'period' => "{$month}/{$year}",
                'filters' => $filters
            ]);

            $kpiData = $this->monthlyKpiService->generateCompleteKpiReport($month, $year, $filters);

            return response()->json([
                'success' => true,
                'data' => $kpiData,
                'timestamp' => Carbon::now()->toDateTimeString(),
                'cache_duration' => 5 // minutes
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error fetching KPI data via AJAX', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch KPI data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🎯 NEW: Export Monthly KPI Report (PDF/Excel)
     */
    public function exportMonthlyKpiReport(Request $request)
    {
        try {
            $format = $request->get('format', 'pdf'); // pdf or excel
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);
            $filters = $request->only(['department_id', 'course_id', 'user_level_id']);

            Log::info("📄 Exporting Monthly KPI Report as {$format}", [
                'period' => "{$month}/{$year}",
                'filters' => $filters
            ]);

            $kpiData = $this->monthlyKpiService->generateCompleteKpiReport($month, $year, $filters);
            $periodName = Carbon::createFromDate($year, $month, 1)->format('F_Y');
            $fileName = "Monthly_KPI_Report_{$periodName}";

//            if ($format === 'pdf') {
//                return $this->exportKpiAsPdf($kpiData, $fileName);
//            } else {
//                return $this->exportKpiAsExcel($kpiData, $fileName);
//            }

        } catch (\Exception $e) {
            Log::error('❌ Error exporting Monthly KPI Report', [
                'format' => $format ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to export KPI report. Please try again.');
        }
    }

    /**
     * 🎯 NEW: Get comparison data between periods
     */
    public function getKpiComparison(Request $request)
    {
        try {
            $currentMonth = $request->get('current_month', Carbon::now()->month);
            $currentYear = $request->get('current_year', Carbon::now()->year);
            $compareMonth = $request->get('compare_month', Carbon::now()->subMonth()->month);
            $compareYear = $request->get('compare_year', Carbon::now()->subMonth()->year);
            $filters = $request->only(['department_id', 'course_id', 'user_level_id']);

            Log::info('📊 KPI Comparison request', [
                'current_period' => "{$currentMonth}/{$currentYear}",
                'compare_period' => "{$compareMonth}/{$compareYear}"
            ]);

            $currentData = $this->monthlyKpiService->generateCompleteKpiReport($currentMonth, $currentYear, $filters);
            $compareData = $this->monthlyKpiService->generateCompleteKpiReport($compareMonth, $compareYear, $filters);

            $comparison = $this->calculateKpiComparison($currentData, $compareData);

            return response()->json([
                'success' => true,
                'current' => $currentData,
                'previous' => $compareData,
                'comparison' => $comparison,
                'timestamp' => Carbon::now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error generating KPI comparison', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate comparison data'
            ], 500);
        }
    }

    /**
     * 🎯 NEW: Get specific KPI section data (for drill-down)
     */
    public function getKpiSection(Request $request, $section)
    {
        try {
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);
            $filters = $request->only(['department_id', 'course_id', 'user_level_id']);

            Log::info("🎯 KPI Section drill-down: {$section}", [
                'period' => "{$month}/{$year}",
                'filters' => $filters
            ]);

            $kpiData = $this->monthlyKpiService->generateCompleteKpiReport($month, $year, $filters);

            if (!isset($kpiData[$section])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid section requested'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'section' => $section,
                'data' => $kpiData[$section],
                'period' => $kpiData['period'],
                'timestamp' => Carbon::now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Error fetching KPI section: {$section}", [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch section data'
            ], 500);
        }
    }

    /**
     * 🎯 NEW: Get trend data for charts
     */
    public function getKpiTrends(Request $request)
    {
        try {
            $months = $request->get('months', 6); // Default 6 months
            $endMonth = $request->get('month', Carbon::now()->month);
            $endYear = $request->get('year', Carbon::now()->year);
            $filters = $request->only(['department_id', 'course_id', 'user_level_id']);

            Log::info("📈 KPI Trends request for {$months} months", [
                'ending_period' => "{$endMonth}/{$endYear}",
                'filters' => $filters
            ]);

            $trends = [];
            $endDate = Carbon::createFromDate($endYear, $endMonth, 1);

            for ($i = $months - 1; $i >= 0; $i--) {
                $periodDate = $endDate->copy()->subMonths($i);
                $monthData = $this->monthlyKpiService->generateCompleteKpiReport(
                    $periodDate->month,
                    $periodDate->year,
                    $filters
                );

                $trends[] = [
                    'period' => $periodDate->format('M Y'),
                    'month' => $periodDate->month,
                    'year' => $periodDate->year,
                    'delivery_overview' => $monthData['delivery_overview'],
                    'engagement' => $monthData['attendance_engagement'],
                    'outcomes' => $monthData['learning_outcomes']
                ];
            }

            return response()->json([
                'success' => true,
                'trends' => $trends,
                'periods' => $months,
                'timestamp' => Carbon::now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error generating KPI trends', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to generate trend data'
            ], 500);
        }
    }

    /**
     * 🎯 NEW: Real-time dashboard stats (for live updates)
     */
    public function getLiveKpiStats(Request $request)
    {
        try {
            $filters = $request->only(['department_id', 'course_id', 'user_level_id']);

            // Get current month stats with minimal processing for speed
            $currentMonth = Carbon::now()->month;
            $currentYear = Carbon::now()->year;

            $liveStats = [
                'active_users_today' => User::whereDate('last_login_at', Carbon::today())->count(),
                'courses_completed_today' => $this->getCoursesCompletedToday($filters),
                'new_enrollments_today' => $this->getNewEnrollmentsToday($filters),
                'average_rating_today' => $this->getAverageRatingToday($filters),
                'system_status' => 'operational',
                'last_updated' => Carbon::now()->toTimeString()
            ];

            return response()->json([
                'success' => true,
                'live_stats' => $liveStats,
                'timestamp' => Carbon::now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error fetching live KPI stats', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch live stats'
            ], 500);
        }
    }

    // ===============================================
    // 🔧 PRIVATE HELPER METHODS
    // ===============================================

    /**
     * Get filter dropdown data for KPI dashboard
     */
    private function getFilterDropdownData()
    {
        return [
            'departments' => Department::select('id', 'name')->orderBy('name')->get(),
            'courses' => Course::select('id', 'name')->orderBy('name')->get(),
            'user_levels' => \App\Models\UserLevel::select('id', 'name')->orderBy('name')->get(),
            'months' => $this->getMonthOptions(),
            'years' => $this->getYearOptions()
        ];
    }

    /**
     * Get month options for filter
     */
    private function getMonthOptions()
    {
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = [
                'value' => $i,
                'label' => Carbon::createFromDate(null, $i, 1)->format('F')
            ];
        }
        return $months;
    }

    /**
     * Get year options for filter
     */
    private function getYearOptions()
    {
        $currentYear = Carbon::now()->year;
        $years = [];

        for ($year = $currentYear - 2; $year <= $currentYear + 1; $year++) {
            $years[] = [
                'value' => $year,
                'label' => (string) $year
            ];
        }

        return $years;
    }

    /**
     * Export KPI data as PDF
     */
//    private function exportKpiAsPdf($kpiData, $fileName)
//    {
//        $pdf = PDF::loadView('exports.monthly-kpi-report', compact('kpiData'))
//            ->setPaper('a4', 'portrait')
//            ->setOptions(['dpi' => 150, 'defaultFont' => 'sans-serif']);
//
//        return $pdf->download("{$fileName}.pdf");
//    }

    /**
     * Export KPI data as Excel
     */
//    private function exportKpiAsExcel($kpiData, $fileName)
//    {
//        return Excel::download(
//            new \App\Exports\MonthlyKpiReportExport($kpiData),
//            "{$fileName}.xlsx"
//        );
//    }

    /**
     * Calculate comparison between two KPI datasets
     */
    private function calculateKpiComparison($current, $previous)
    {
        $comparison = [];

        // Delivery Overview Comparison
        $comparison['delivery_overview'] = [
            'courses_delivered' => $this->calculatePercentageChange(
                $previous['delivery_overview']['courses_delivered'],
                $current['delivery_overview']['courses_delivered']
            ),
            'total_enrolled' => $this->calculatePercentageChange(
                $previous['delivery_overview']['total_enrolled'],
                $current['delivery_overview']['total_enrolled']
            ),
            'completion_rate' => $this->calculatePercentageChange(
                $previous['delivery_overview']['completion_rate'],
                $current['delivery_overview']['completion_rate']
            )
        ];

        // Add more comparison calculations as needed...

        return $comparison;
    }

    /**
     * Calculate percentage change between two values
     */
    private function calculatePercentageChange($previous, $current)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        $change = (($current - $previous) / $previous) * 100;

        return [
            'previous' => $previous,
            'current' => $current,
            'change' => round($change, 2),
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
            'is_positive' => $change >= 0
        ];
    }

    /**
     * Get courses completed today (for live stats)
     */
    private function getCoursesCompletedToday($filters)
    {
        $query = \App\Models\CourseCompletion::whereDate('completed_at', Carbon::today());

        if (!empty($filters['department_id'])) {
            $query->whereHas('user', function($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        return $query->count();
    }

    /**
     * Get new enrollments today (for live stats)
     */
    private function getNewEnrollmentsToday($filters)
    {
        $query = \App\Models\CourseRegistration::whereDate('registered_at', Carbon::today());

        if (!empty($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        return $query->count();
    }

    /**
     * Get average rating today (for live stats)
     */
    private function getAverageRatingToday($filters)
    {
        $query = \App\Models\CourseCompletion::whereDate('completed_at', Carbon::today())
            ->whereNotNull('rating');

        return round($query->avg('rating') ?: 0, 2);
    }
    /**
     * 📄 Export Monthly Training KPI Report to CSV
     */
    public function exportMonthlyKpiCsv(Request $request)
    {
        try {
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);
            $filters = $request->only(['department_id', 'course_id', 'user_level_id']);

            Log::info('📄 Exporting Monthly KPI Report as CSV', [
                'period' => "{$month}/{$year}",
                'filters' => $filters
            ]);

            // Generate the KPI data
            $kpiData = $this->monthlyKpiService->generateCompleteKpiReport($month, $year, $filters);
            $periodName = Carbon::createFromDate($year, $month, 1)->format('F_Y');

            // Prepare CSV data structure
            $csvData = $this->prepareKpiCsvData($kpiData);

            // Enhanced filename with filters
            $filename = "Monthly_KPI_Report_{$periodName}";

            if (!empty($filters['department_id'])) {
                $dept = Department::find($filters['department_id']);
                $filename .= '_' . Str::slug($dept->name ?? 'dept_' . $filters['department_id']);
            }

            if (!empty($filters['course_id'])) {
                $course = Course::find($filters['course_id']);
                $filename .= '_' . Str::slug($course->name ?? 'course_' . $filters['course_id']);
            }

            $filename .= '_' . date('Y-m-d') . '.csv';

            return $this->csvExportService->export(
                $filename,
                $csvData['headers'],
                $csvData['data']
            );

        } catch (\Exception $e) {
            Log::error('❌ Error exporting Monthly KPI Report CSV', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to export KPI report. Please try again.');
        }
    }

    /**
     * 📊 Prepare KPI data for CSV export
     */
    private function prepareKpiCsvData($kpiData)
    {
        // 📋 CSV HEADERS - Comprehensive KPI structure
        $headers = [
            // Period Information
            'Report Period',
            'Month',
            'Year',
            'Generated Date',

            // 📊 Section 1: Training Delivery Overview
            'Courses Delivered',
            'Total Enrolled',
            'Active Participants',
            'Completion Rate (%)',

            // 🎯 Section 2: Attendance & Engagement
            'Average Attendance Rate (%)',
            'Average Time Spent (hours)',
            'Clocking Consistency (%)',
            'Login Frequency (%)',
            'Session Duration Average (min)',
            'Dropout Rate (%)',
            'Engagement Score (%)',

            // 📈 Section 3: Learning Outcomes
            'Quiz Pass Rate (%)',
            'Quiz Fail Rate (%)',
            'Average Quiz Score (%)',
            'Improvement Rate (%)',
            'Retake Rate (%)',

            // ⭐ Section 4: Course Quality & Feedback
            'Average Rating (/5)',
            'Total Feedback Count',
            'Positive Sentiment (%)',
            'Neutral Sentiment (%)',
            'Negative Sentiment (%)',

            // 🏆 Section 5: Performance Analysis - Top Courses
            'Top Course 1 Name',
            'Top Course 1 Rating',
            'Top Course 1 Completion Rate (%)',
            'Top Course 2 Name',
            'Top Course 2 Rating',
            'Top Course 2 Completion Rate (%)',
            'Top Course 3 Name',
            'Top Course 3 Rating',
            'Top Course 3 Completion Rate (%)',

            // 👤 Section 6: User Performance Analysis - Top Users
            'Top User 1 Name',
            'Top User 1 Score (%)',
            'Top User 1 Courses Completed',
            'Top User 2 Name',
            'Top User 2 Score (%)',
            'Top User 2 Courses Completed',
            'Top User 3 Name',
            'Top User 3 Score (%)',
            'Top User 3 Courses Completed',

            // 📈 Section 7: Monthly Engagement Trends
            'Current Month Engagement (%)',
            'Previous Month Engagement (%)',
            'Trend Direction',
            'Trend Percentage Change (%)',

            // 📋 Additional Metrics
            'Total Courses Available',
            'Total Users in System',
            'Most Popular Keywords',
            'Main Issues Identified',
            'System Status'
        ];

        // 📊 CSV DATA ROW - Single comprehensive row with all metrics
        $data = [
            [
                // Period Information
                'report_period' => $kpiData['period']['period_name'] ?? 'N/A',
                'month' => $kpiData['period']['month'] ?? 'N/A',
                'year' => $kpiData['period']['year'] ?? 'N/A',
                'generated_date' => Carbon::now()->format('Y-m-d H:i:s'),

                // 📊 Section 1: Training Delivery Overview
                'courses_delivered' => $kpiData['delivery_overview']['courses_delivered'] ?? 0,
                'total_enrolled' => $kpiData['delivery_overview']['total_enrolled'] ?? 0,
                'active_participants' => $kpiData['delivery_overview']['active_participants'] ?? 0,
                'completion_rate' => $kpiData['delivery_overview']['completion_rate'] ?? 0,

                // 🎯 Section 2: Attendance & Engagement
                'average_attendance_rate' => $kpiData['attendance_engagement']['average_attendance_rate'] ?? 0,
                'average_time_spent' => $kpiData['attendance_engagement']['average_time_spent'] ?? 0,
                'clocking_consistency' => $kpiData['attendance_engagement']['clocking_consistency'] ?? 0,
                'login_frequency' => $kpiData['attendance_engagement']['login_frequency'] ?? 0,
                'session_duration_avg' => $kpiData['attendance_engagement']['session_duration_avg'] ?? 0,
                'dropout_rate' => $kpiData['attendance_engagement']['dropout_rate'] ?? 0,
                'engagement_score' => $kpiData['attendance_engagement']['engagement_score'] ?? 0,

                // 📈 Section 3: Learning Outcomes
                'quiz_pass_rate' => $kpiData['learning_outcomes']['quiz_pass_rate'] ?? 0,
                'quiz_fail_rate' => $kpiData['learning_outcomes']['quiz_fail_rate'] ?? 0,
                'average_quiz_score' => $kpiData['learning_outcomes']['average_quiz_score'] ?? 0,
                'improvement_rate' => $kpiData['learning_outcomes']['improvement_rate'] ?? 0,
                'retake_rate' => $kpiData['learning_outcomes']['retake_rate'] ?? 0,

                // ⭐ Section 4: Course Quality & Feedback
                'average_rating' => $kpiData['feedback_analysis']['average_rating'] ?? 0,
                'total_feedback_count' => $kpiData['feedback_analysis']['total_feedback_count'] ?? 0,
                'positive_sentiment' => $kpiData['feedback_analysis']['feedback_sentiment']['positive'] ?? 0,
                'neutral_sentiment' => $kpiData['feedback_analysis']['feedback_sentiment']['neutral'] ?? 0,
                'negative_sentiment' => $kpiData['feedback_analysis']['feedback_sentiment']['negative'] ?? 0,

                // 🏆 Section 5: Performance Analysis - Top Courses (Top 3)
                'top_course_1_name' => $this->getArrayValue($kpiData, 'performance_analysis.top_performing_courses.0.name', 'N/A'),
                'top_course_1_rating' => $this->getArrayValue($kpiData, 'performance_analysis.top_performing_courses.0.rating', 0),
                'top_course_1_completion_rate' => $this->getArrayValue($kpiData, 'performance_analysis.top_performing_courses.0.completion_rate', 0),
                'top_course_2_name' => $this->getArrayValue($kpiData, 'performance_analysis.top_performing_courses.1.name', 'N/A'),
                'top_course_2_rating' => $this->getArrayValue($kpiData, 'performance_analysis.top_performing_courses.1.rating', 0),
                'top_course_2_completion_rate' => $this->getArrayValue($kpiData, 'performance_analysis.top_performing_courses.1.completion_rate', 0),
                'top_course_3_name' => $this->getArrayValue($kpiData, 'performance_analysis.top_performing_courses.2.name', 'N/A'),
                'top_course_3_rating' => $this->getArrayValue($kpiData, 'performance_analysis.top_performing_courses.2.rating', 0),
                'top_course_3_completion_rate' => $this->getArrayValue($kpiData, 'performance_analysis.top_performing_courses.2.completion_rate', 0),

                // 👤 Section 6: User Performance Analysis - Top Users (Top 3)
                'top_user_1_name' => $this->getArrayValue($kpiData, 'performance_analysis.top_performing_users.0.name', 'N/A'),
                'top_user_1_score' => $this->getArrayValue($kpiData, 'performance_analysis.top_performing_users.0.score', 0),
                'top_user_1_courses_completed' => $this->getArrayValue($kpiData, 'performance_analysis.top_performing_users.0.courses_completed', 0),
                'top_user_2_name' => $this->getArrayValue($kpiData, 'performance_analysis.top_performing_users.1.name', 'N/A'),
                'top_user_2_score' => $this->getArrayValue($kpiData, 'performance_analysis.top_performing_users.1.score', 0),
                'top_user_2_courses_completed' => $this->getArrayValue($kpiData, 'performance_analysis.top_performing_users.1.courses_completed', 0),
                'top_user_3_name' => $this->getArrayValue($kpiData, 'performance_analysis.top_performing_users.2.name', 'N/A'),
                'top_user_3_score' => $this->getArrayValue($kpiData, 'performance_analysis.top_performing_users.2.score', 0),
                'top_user_3_courses_completed' => $this->getArrayValue($kpiData, 'performance_analysis.top_performing_users.2.courses_completed', 0),

                // 📈 Section 7: Monthly Engagement Trends
                'current_month_engagement' => $kpiData['engagement_trends']['current_month_engagement'] ?? 0,
                'previous_month_engagement' => $kpiData['engagement_trends']['previous_month_engagement'] ?? 0,
                'trend_direction' => $kpiData['engagement_trends']['trend_direction'] ?? 'stable',
                'trend_percentage_change' => $kpiData['engagement_trends']['trend_percentage'] ?? 0,

                // 📋 Additional Metrics
                'total_courses_available' => Course::count(),
                'total_users_in_system' => User::count(),
                'most_popular_keywords' => implode(', ', $kpiData['feedback_analysis']['top_positive_keywords'] ?? []),
                'main_issues_identified' => implode(', ', $kpiData['feedback_analysis']['top_issues'] ?? []),
                'system_status' => 'Operational'
            ]
        ];

        return [
            'headers' => $headers,
            'data' => $data
        ];
    }

    /**
     * 🔧 Helper method to safely get nested array values
     */
    private function getArrayValue($array, $path, $default = null)
    {
        $keys = explode('.', $path);
        $current = $array;

        foreach ($keys as $key) {
            if (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } elseif (is_object($current) && property_exists($current, $key)) {
                $current = $current->$key;
            } elseif (is_object($current) && method_exists($current, 'offsetExists') && $current->offsetExists($key)) {
                $current = $current[$key];
            } else {
                return $default;
            }
        }

        return $current ?? $default;
    }
    public function monthlyKpiScreenshot(Request $request)
    {
        try {
            $month = $request->get('month', Carbon::now()->month);
            $year = $request->get('year', Carbon::now()->year);
            $filters = $request->only(['department_id', 'course_id', 'user_level_id']);

            Log::info('📸 Loading Monthly KPI Screenshot Report', [
                'period' => "{$month}/{$year}",
                'filters' => $filters
            ]);

            // Generate the KPI data using the same service
            $kpiData = $this->monthlyKpiService->generateCompleteKpiReport($month, $year, $filters);

            // Get filter data for display
            $departments = Department::select('id', 'name')->orderBy('name')->get();
            $courses = Course::select('id', 'name')->orderBy('name')->get();

            return Inertia::render('Admin/Reports/MonthlyKpiScreenshot', [
                'kpiData' => $kpiData,
                'currentFilters' => [
                    'month' => $month,
                    'year' => $year,
                    'department_id' => $filters['department_id'] ?? null,
                    'course_id' => $filters['course_id'] ?? null,
                ],
                'departments' => $departments,
                'courses' => $courses,
                'lastUpdated' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error loading Monthly KPI Screenshot Report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to load KPI screenshot report. Please try again.');
        }
    }
}
