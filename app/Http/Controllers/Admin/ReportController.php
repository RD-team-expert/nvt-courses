<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\CourseCompletion;
use App\Services\CsvExportService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportController extends Controller
{
    protected $reportService;
    protected $csvExportService;

    public function __construct(ReportService $reportService, CsvExportService $csvExportService)
    {
        $this->reportService = $reportService;
        $this->csvExportService = $csvExportService;
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

        $query = CourseCompletion::query()
            ->join('users', 'course_completions.user_id', '=', 'users.id')
            ->join('courses', 'course_completions.course_id', '=', 'courses.id');

        // Add optional joins only if tables exist
        if (Schema::hasTable('course_user')) {
            $query->leftJoin('course_user', function ($join) {
                $join->on('course_completions.user_id', '=', 'course_user.user_id')
                    ->on('course_completions.course_id', '=', 'course_user.course_id');
            });
        }

        if (Schema::hasTable('clockings')) {
            $query->leftJoin('clockings as latest_clocking', function ($join) {
                $join->on('course_completions.user_id', '=', 'latest_clocking.user_id')
                    ->on('course_completions.course_id', '=', 'latest_clocking.course_id')
                    ->whereRaw('latest_clocking.id = (SELECT MAX(id) FROM clockings WHERE clockings.user_id = course_completions.user_id AND clockings.course_id = course_completions.course_id)');
            });
        }

        $selectFields = [
            'course_completions.id',
            'users.name as user_name',
            'users.email as user_email',
            'courses.name as course_name',
            'course_completions.completed_at',
            'course_completions.rating',
            'course_completions.feedback',
        ];

        if (Schema::hasTable('course_user')) {
            $selectFields[] = 'course_user.created_at as registered_at';
        } else {
            $selectFields[] = DB::raw('NULL as registered_at');
        }

        if (Schema::hasTable('clockings')) {
            $selectFields[] = 'latest_clocking.comment';
        } else {
            $selectFields[] = DB::raw('NULL as comment');
        }

        $query->select($selectFields);

        // Apply filters
        if (!empty($filters['course_id'])) {
            $query->where('course_completions.course_id', $filters['course_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('course_completions.completed_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('course_completions.completed_at', '<=', $filters['date_to']);
        }

        $completions = $query->orderBy('course_completions.completed_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $courses = Course::select('id', 'name')->orderBy('name')->get();

        $debug = [
            'filter_count' => count(array_filter($filters)),
            'completion_count' => $completions->total(),
            'has_courses' => $courses->count() > 0,
            'current_page' => $completions->currentPage(),
            'per_page' => $completions->perPage(),
            'total_pages' => $completions->lastPage()
        ];

        return Inertia::render('Admin/Reports/CourseCompletion', [
            'completions' => $completions,
            'courses' => $courses,
            'filters' => $filters,
            'debug' => $debug
        ]);
    }

    /**
     * Export course completions to CSV
     */
    public function exportCourseCompletion(Request $request)
    {
        $filters = $request->only(['course_id', 'date_from', 'date_to']);

        $query = CourseCompletion::query()
            ->join('users', 'course_completions.user_id', '=', 'users.id')
            ->join('courses', 'course_completions.course_id', '=', 'courses.id');

        if (Schema::hasTable('course_user')) {
            $query->leftJoin('course_user', function ($join) {
                $join->on('course_completions.user_id', '=', 'course_user.user_id')
                    ->on('course_completions.course_id', '=', 'course_user.course_id');
            });
        }

        if (Schema::hasTable('clockings')) {
            $query->leftJoin('clockings as latest_clocking', function ($join) {
                $join->on('course_completions.user_id', '=', 'latest_clocking.user_id')
                    ->on('course_completions.course_id', '=', 'latest_clocking.course_id')
                    ->whereRaw('latest_clocking.id = (SELECT MAX(id) FROM clockings WHERE clockings.user_id = course_completions.user_id AND clockings.course_id = course_completions.course_id)');
            });
        }

        $selectFields = [
            'course_completions.id',
            'users.name as user_name',
            'users.email as user_email',
            'courses.name as course_name',
            'course_completions.completed_at',
            'course_completions.rating',
            'course_completions.feedback',
        ];

        if (Schema::hasTable('course_user')) {
            $selectFields[] = 'course_user.created_at as registered_at';
        } else {
            $selectFields[] = DB::raw('NULL as registered_at');
        }

        if (Schema::hasTable('clockings')) {
            $selectFields[] = 'latest_clocking.comment';
        } else {
            $selectFields[] = DB::raw('NULL as comment');
        }

        $query->select($selectFields);

        if (!empty($filters['course_id'])) {
            $query->where('course_completions.course_id', $filters['course_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('course_completions.completed_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('course_completions.completed_at', '<=', $filters['date_to']);
        }

        $completions = $query->orderBy('course_completions.completed_at', 'desc')->get();

        $headers = [
            'ID', 'User Name', 'User Email', 'Course Name', 'Registered At',
            'Completed At', 'Rating', 'Feedback', 'Comment'
        ];

        $data = $completions->map(function($record) {
            return [
                'id' => $record->id,
                'user_name' => $record->user_name,
                'user_email' => $record->user_email,
                'course_name' => $record->course_name,
                'registered_at' => $record->registered_at,
                'completed_at' => $record->completed_at,
                'rating' => $record->rating,
                'feedback' => $record->feedback,
                'comment' => $record->comment
            ];
        })->toArray();

        return $this->csvExportService->export(
            'course_completions_' . date('Y-m-d') . '.csv',
            $headers,
            $data
        );
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
}
