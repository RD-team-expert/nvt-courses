<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Models\CourseCompletion;
use App\Services\CsvExportService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
     * Display the reports dashboard
     */
    public function index()
    {
        $analytics = $this->reportService->getDashboardAnalytics();
        
        return Inertia::render('Admin/Reports/Index', [
            'analytics' => $analytics
        ]);
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
        
        // Build the query
        $query = CourseCompletion::query()
            ->join('users', 'course_completions.user_id', '=', 'users.id')
            ->join('courses', 'course_completions.course_id', '=', 'courses.id')
            ->leftJoin('course_user', function ($join) {
                $join->on('course_completions.user_id', '=', 'course_user.user_id')
                     ->on('course_completions.course_id', '=', 'course_user.course_id');
            })
            ->select([
                'course_completions.id',
                'users.name as user_name',
                'users.email as user_email',
                'courses.name as course_name',
                'course_user.created_at as registered_at',
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
        
        // Get the results with pagination
        $completions = $query->orderBy('course_completions.completed_at', 'desc')
            ->paginate(10)
            ->withQueryString();
        
        // Get all courses for the filter dropdown
        $courses = Course::select('id', 'name')->orderBy('name')->get();
        
        // Debug info
        $debug = [
            'filter_count' => count(array_filter($filters)),
            'completion_count' => $completions->total(),
            'has_courses' => $courses->count() > 0
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
        
        // Build the query (same as above)
        $query = CourseCompletion::query()
            ->join('users', 'course_completions.user_id', '=', 'users.id')
            ->join('courses', 'course_completions.course_id', '=', 'courses.id')
            ->leftJoin('course_user', function ($join) {
                $join->on('course_completions.user_id', '=', 'course_user.user_id')
                     ->on('course_completions.course_id', '=', 'course_user.course_id');
            })
            ->select([
                'users.name as user_name',
                'users.email as user_email',
                'courses.name as course_name',
                'course_user.created_at as registered_at',
                'course_completions.completed_at',
                'course_completions.rating',
                'course_completions.feedback'
            ]);
        
        // Apply the same filters
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
        
        // Generate CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="course_completions.csv"',
        ];
        
        $callback = function() use ($completions) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'User Name',
                'User Email',
                'Course Name',
                'Registration Date',
                'Completion Date',
                'Rating',
                'Feedback'
            ]);
            
            // Add data rows
            foreach ($completions as $completion) {
                fputcsv($file, [
                    $completion->user_name,
                    $completion->user_email,
                    $completion->course_name,
                    $completion->registered_at ? date('Y-m-d H:i:s', strtotime($completion->registered_at)) : '',
                    $completion->completed_at ? date('Y-m-d H:i:s', strtotime($completion->completed_at)) : '',
                    $completion->rating ?: '',
                    $completion->feedback ?: ''
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
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
     * Export course completions to CSV
     */
  
}