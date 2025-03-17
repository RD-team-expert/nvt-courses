<?php

namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\Clocking;
use App\Models\CourseRegistration;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Get analytics data for the dashboard
     */
    public function getDashboardAnalytics()
    {
        // Get user statistics
        $totalUsers = User::count();
        $activeUsers = User::whereHas('clockings', function ($query) {
            $query->where('clock_in', '>=', Carbon::now()->subDays(30));
        })->count();
        
        $activePercentage = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100) : 0;
        
        // Get course statistics
        $totalCourses = Course::count();
        $activeCourses = Course::where('status', 'active')->count();
        $completedCourses = Course::where('status', 'completed')->count();
        
        // Get registration statistics
        $totalRegistrations = DB::table('course_user')->count();
        $completedRegistrations = DB::table('course_user')->where('user_status', 'completed')->count();
        $completionRate = $totalRegistrations > 0 ? round(($completedRegistrations / $totalRegistrations) * 100) : 0;
        
        // Get attendance statistics
        $totalClockings = Clocking::count();
        $avgDuration = Clocking::whereNotNull('duration_in_minutes')->avg('duration_in_minutes') ?? 0;
        $avgRating = Clocking::whereNotNull('rating')->avg('rating') ?? 0;
        
        // Get monthly attendance trend (last 6 months)
        $monthlyAttendance = Clocking::select(
            DB::raw('DATE_FORMAT(clock_in, "%Y-%m") as month'),
            DB::raw('COUNT(*) as count')
        )
        ->where('clock_in', '>=', Carbon::now()->subMonths(6))
        ->groupBy('month')
        ->orderBy('month')
        ->pluck('count', 'month')
        ->toArray();
        
        // Get popular courses
        $popularCourses = DB::table('course_user')
            ->select(
                'course_id',
                DB::raw('COUNT(*) as registrations')
            )
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->groupBy('course_id')
            ->orderByDesc('registrations')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $course = Course::find($item->course_id);
                return [
                    'id' => $item->course_id,
                    'name' => $course ? $course->name : 'Unknown Course',
                    'registrations' => (int)$item->registrations
                ];
            })->toArray();
        
        return [
            'users' => [
                'total' => $totalUsers,
                'active' => $activeUsers,
                'active_percentage' => $activePercentage
            ],
            'courses' => [
                'total' => $totalCourses,
                'active' => $activeCourses,
                'completed' => $completedCourses
            ],
            'registrations' => [
                'total' => $totalRegistrations,
                'completed' => $completedRegistrations,
                'completion_rate' => $completionRate
            ],
            'attendance' => [
                'total_clockings' => $totalClockings,
                'average_duration' => round($avgDuration),
                'average_rating' => round($avgRating, 1)
            ],
            'trends' => [
                'monthly_attendance' => $monthlyAttendance,
                'popular_courses' => $popularCourses
            ]
        ];
    }

    /**
     * Get course registrations report
     */
    public function getCourseRegistrationsReport($filters = [])
    {
        // Use the course_user pivot table instead of CourseRegistration model
        $query = DB::table('course_user')
            ->join('users', 'course_user.user_id', '=', 'users.id')
            ->join('courses', 'course_user.course_id', '=', 'courses.id')
            ->select(
                'course_user.*',
                'users.name as user_name',
                'users.email as user_email',
                'courses.name as course_name'
            );
        
        // Apply filters
        if (!empty($filters['course_id'])) {
            $query->where('course_user.course_id', $filters['course_id']);
        }
        
        if (!empty($filters['status'])) {
            $query->where('course_user.user_status', $filters['status']);
        }
        
        if (!empty($filters['date_from'])) {
            $query->where('course_user.created_at', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('course_user.created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }
        
        $registrations = $query->orderBy('course_user.created_at', 'desc')->get();
        
        return $registrations->map(function ($registration) {
            return [
                'id' => $registration->id,
                'user_id' => $registration->user_id,
                'user_name' => $registration->user_name ?? 'Unknown User',
                'user_email' => $registration->user_email ?? 'unknown@example.com',
                'course_id' => $registration->course_id,
                'course_name' => $registration->course_name ?? 'Unknown Course',
                'status' => $registration->user_status,
                'registered_at' => $registration->created_at,
                'completed_at' => $registration->updated_at, // This might need adjustment based on your data model
                'rating' => null, // Add rating if available in your pivot table
                'feedback' => null // Add feedback if available in your pivot table
            ];
        });
    }

    /**
     * Get attendance report
     */
    public function getAttendanceReport($filters = [])
    {
        $query = Clocking::with(['user:id,name,email', 'course:id,name'])
            ->select('clockings.*');
        
        // Apply filters
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        if (!empty($filters['date_from'])) {
            $query->where('clock_in', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('clock_in', '<=', $filters['date_to'] . ' 23:59:59');
        }
        
        // Handle course filter
        if (!empty($filters['course_id'])) {
            if ($filters['course_id'] === 'general') {
                $query->whereNull('course_id');
            } else {
                $query->where('course_id', $filters['course_id']);
            }
        }
        
        $attendance = $query->orderBy('clock_in', 'desc')->get();
        
        return $attendance->map(function ($record) {
            return [
                'id' => $record->id,
                'user_id' => $record->user_id,
                'user_name' => $record->user->name ?? 'Unknown User',
                'user_email' => $record->user->email ?? 'unknown@example.com',
                'clock_in' => $record->clock_in,
                'clock_out' => $record->clock_out,
                'duration_in_minutes' => $record->duration_in_minutes,
                'rating' => $record->rating,
                'comment' => $record->comment,
                'course_name' => $record->course ? $record->course->name : null
            ];
        });
    }

    /**
     * Get course completion report
     */
    // Add this method to your ReportService class
    
    /**
     * Get course completion report data
     *
     * @param array $filters
     * @return \Illuminate\Support\Collection
     */
    public function getCourseCompletionReport($filters = [])
    {
        $query = \App\Models\CourseCompletion::query()
            ->join('users', 'course_completions.user_id', '=', 'users.id')
            ->join('courses', 'course_completions.course_id', '=', 'courses.id')
            ->join('course_registrations', function ($join) {
                $join->on('course_completions.user_id', '=', 'course_registrations.user_id')
                     ->on('course_completions.course_id', '=', 'course_registrations.course_id');
            })
            ->select([
                'course_completions.id',
                'users.name as user_name',
                'users.email as user_email',
                'courses.name as course_name',
                'course_registrations.created_at as registered_at',
                'course_completions.completed_at',
                'course_completions.rating',
                'course_completions.feedback'
            ]);
    
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
    
        return $query->orderBy('course_completions.completed_at', 'desc')->get();
    }
}