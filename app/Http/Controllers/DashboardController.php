<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseOnline;
use App\Models\CourseOnlineAssignment;
use App\Models\User;
use App\Models\Clocking; // Changed from Attendance to Clocking
use App\Models\CourseSession;
use Illuminate\Http\Request;
use App\Services\ActivityService;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $isAdmin = $user->is_admin || $user->role === 'admin';

        $stats = [];
        $recentActivity = [];
        $userCourses = [];
        $userOnlineCourses = [];
        $userAttendance = [];

        if ($isAdmin) {
            // Admin stats - this section remains completely unchanged
            $stats = [
                'totalCourses' => Course::count(),
                'totalUsers' => User::count(),
                'activeSessions' => $this->getActiveSessions(),
                'completionRate' => $this->calculateCompletionRate(),
                'pendingCourses' => Course::where('status', 'pending')->count(),
                'completedCourses' => Course::where('status', 'completed')->count(),
            ];

            // Recent activity for admin
            $recentActivity = $this->getRecentActivity();
        } else {
            // User stats - fixing the counting issue
            // Get the raw count from the course_user table directly
            $totalEnrolled = DB::table('course_user')->where('user_id', $user->id)->count();

            // Check if status column exists in course_user table
            $columns = Schema::getColumnListing('course_user');
            $hasStatusColumn = in_array('status', $columns);

            // Count completed courses only if status column exists
            $completedCourses = 0;
            if ($hasStatusColumn) {
                $completedCourses = DB::table('course_user')
                    ->where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->count();
            }

            $stats = [
                'userCourses' => $totalEnrolled,
                'userAttendanceRate' => $this->calculateUserAttendanceRate($user->id),
                'userCompletedCourses' => $completedCourses,
                'userTotalCourses' => $totalEnrolled,
                'upcomingSessions' => $this->getUpcomingUserSessions($user->id),
            ];

            // User's courses
            $userCourses = $this->getUserCourses($user->id);

            // User's online courses
            $userOnlineCourses = $this->getUserOnlineCourses($user->id);

            // User's attendance records
            $userAttendance = $this->getUserAttendance($user->id);
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'recentActivity' => $recentActivity,
            'userCourses' => $userCourses,
            'userOnlineCourses' => $userOnlineCourses,
            'userAttendance' => $userAttendance,
        ]);
    }

    private function calculateCompletionRate()
    {
        $totalEnrollments = DB::table('course_user')->count();

        if ($totalEnrollments === 0) {
            return 0;
        }

        // Check if the status column exists in the course_user table
        $columns = Schema::getColumnListing('course_user');

        // If status column doesn't exist, return 0 or a default value
        if (!in_array('status', $columns)) {
            return 0;
        }

        $completedEnrollments = DB::table('course_user')
            ->where('status', 'completed')
            ->count();

        return round(($completedEnrollments / $totalEnrollments) * 100);
    }

    private function calculateUserAttendanceRate($userId)
    {
        $attendanceRecords = Clocking::where('user_id', $userId)->count(); // Changed from Attendance to Clocking

        if ($attendanceRecords === 0) {
            return 0;
        }

        $presentRecords = Clocking::where('user_id', $userId) // Changed from Attendance to Clocking
            ->whereNotNull('clock_out') // Assuming completed clockings are "present"
            ->count();

        return round(($presentRecords / $attendanceRecords) * 100);
    }

    private function getActiveSessions()
    {
        // Check if CourseSession model exists, otherwise fall back to counting active courses
        if (class_exists('App\Models\CourseSession')) {
            return CourseSession::whereDate('session_date', '>=', Carbon::today())
                ->count();
        }

        return Course::where('status', 'in_progress')->count();
    }

    private function getUpcomingUserSessions($userId)
    {
        // Check if CourseSession model exists
        if (class_exists('App\Models\CourseSession')) {
            return CourseSession::join('courses', 'course_sessions.course_id', '=', 'courses.id')
                ->join('course_user', 'courses.id', '=', 'course_user.course_id')
                ->where('course_user.user_id', $userId)
                ->whereDate('course_sessions.session_date', '>=', Carbon::today())
                ->count();
        }

        return 0;
    }

    // Update the getRecentActivity method in DashboardController
    private function getRecentActivity()
    {
        // Use the ActivityService to get recent activities
        return app(ActivityService::class)->getRecent(4)
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'created_at' => $activity->created_at,
                    'user' => $activity->user ? [
                        'name' => $activity->user->name,
                        'avatar' => $activity->user->profile_photo_url ?? null,
                    ] : null,
                ];
            });
    }

    private function getUserCourses($userId)
    {
        // Get courses directly from the database to ensure we have all data
        $query = DB::table('courses')
            ->join('course_user', 'courses.id', '=', 'course_user.course_id')
            ->where('course_user.user_id', $userId)
            ->select(
                'courses.id',
                'courses.name',
                'courses.image_path',
                'courses.start_date',
                'courses.end_date'
            );

        // Check if status column exists in course_user table
        $columns = Schema::getColumnListing('course_user');
        $hasStatusColumn = in_array('status', $columns);

        // Only select status if the column exists
        if ($hasStatusColumn) {
            $query->addSelect('course_user.status');
        }

        $courses = $query->orderBy('course_user.created_at', 'desc')
            ->limit(3)
            ->get();

        return $courses->map(function ($course) use ($hasStatusColumn) {
            $data = [
                'id' => $course->id,
                'name' => $course->name,
                'image_path' => $course->image_path,
                'start_date' => $course->start_date,
                'end_date' => $course->end_date,
            ];

            // Add status to the data if the column exists
            if ($hasStatusColumn) {
                $data['status'] = $course->status ?? 'enrolled';
            } else {
                $data['status'] = 'enrolled'; // Default status
            }

            return $data;
        });
    }

    private function getUserAttendance($userId)
    {
        // Check if course_id column exists in clockings table
        $columns = Schema::getColumnListing('clockings');
        $hasCourseId = in_array('course_id', $columns);

        $query = Clocking::where('user_id', $userId)
            ->select(
                'clockings.id',
                'clockings.clock_in as date',
                'clockings.clock_out'
            );

        // Only select and join with course if course_id exists
        if ($hasCourseId) {
            $query->addSelect('clockings.course_id');

            // Join with courses table to get course name directly
            $query->leftJoin('courses', 'clockings.course_id', '=', 'courses.id')
                  ->addSelect('courses.name as course_name');
        }

        return $query->orderBy('clock_in', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($clocking) use ($hasCourseId) {
                $data = [
                    'id' => $clocking->id,
                    'date' => $clocking->date,
                    'status' => $clocking->clock_out ? 'completed' : 'in_progress',
                ];

                // Add course name if it exists from the join
                if ($hasCourseId && isset($clocking->course_name)) {
                    $data['course_name'] = $clocking->course_name;
                } else {
                    $data['course_name'] = 'General Attendance';
                }

                return $data;
            });
    }

    private function getUserOnlineCourses($userId)
    {
        // Get online courses assigned to the user
        $onlineCourses = CourseOnlineAssignment::where('user_id', $userId)
            ->with(['courseOnline' => function($query) {
                $query->select('id', 'name', 'image_path', 'estimated_duration', 'difficulty_level', 'is_active');
            }])
            ->orderBy('assigned_at', 'desc')
            ->limit(3)
            ->get();

        return $onlineCourses->map(function ($assignment) {
            $course = $assignment->courseOnline;
            if (!$course) {
                return null;
            }

            return [
                'id' => $course->id,
                'name' => $course->name,
                'image_path' => $course->image_path,
                'estimated_duration' => $course->estimated_duration,
                'difficulty_level' => $course->difficulty_level,
                'status' => $assignment->status ?? 'assigned',
                'progress_percentage' => $assignment->progress_percentage ?? 0,
                'assigned_at' => $assignment->assigned_at,
                'started_at' => $assignment->started_at,
                'completed_at' => $assignment->completed_at,
                'is_active' => $course->is_active,
            ];
        })->filter();
    }
}
