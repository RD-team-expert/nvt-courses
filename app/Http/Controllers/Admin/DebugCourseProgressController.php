<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DebugCourseProgressController extends Controller
{
    /**
     * Debug endpoint to test data retrieval for course progress report
     * Access via: /admin/debug-course-progress
     */
    public function index()
    {
        $results = [];
        
        // 1. Check course_assignments table
        $results['course_assignments'] = DB::table('course_assignments')
            ->select('id', 'user_id', 'course_id', 'course_availability_id', 'status', 'assigned_at', 'responded_at', 'completed_at')
            ->limit(5)
            ->get();
        
        // 2. Check course_availabilities table
        $results['course_availabilities'] = DB::table('course_availabilities')
            ->select('id', 'course_id', 'start_date', 'end_date', 'sessions', 'status')
            ->limit(5)
            ->get();
        
        // 3. Check clockings table
        $results['clockings'] = DB::table('clockings')
            ->select('id', 'user_id', 'course_id', 'clock_in', 'clock_out')
            ->limit(5)
            ->get();
        
        // 4. Check course_online_assignments table
        $results['course_online_assignments'] = DB::table('course_online_assignments')
            ->select('id', 'user_id', 'course_online_id', 'status', 'assigned_at', 'started_at', 'completed_at', 'progress_percentage', 'deadline')
            ->limit(5)
            ->get();
        
        // 5. Check course_online table
        $results['course_online'] = DB::table('course_online')
            ->select('id', 'name', 'deadline', 'has_deadline')
            ->limit(5)
            ->get();
        
        // 6. Test traditional course progress calculation
        $traditionalTest = DB::table('course_assignments as ca')
            ->join('course_availabilities as cav', 'ca.course_availability_id', '=', 'cav.id')
            ->leftJoin('clockings as c', function($join) {
                $join->on('c.user_id', '=', 'ca.user_id')
                     ->on('c.course_id', '=', 'ca.course_id')
                     ->whereNotNull('c.clock_out');
            })
            ->select([
                'ca.id as assignment_id',
                'ca.user_id',
                'ca.course_id',
                'ca.status',
                'ca.responded_at as started_at',
                'ca.completed_at',
                'cav.end_date as deadline',
                'cav.sessions as total_sessions',
                DB::raw('COUNT(DISTINCT c.id) as attended_sessions'),
            ])
            ->groupBy('ca.id', 'ca.user_id', 'ca.course_id', 'ca.status', 'ca.responded_at', 'ca.completed_at', 'cav.end_date', 'cav.sessions')
            ->limit(5)
            ->get();
        
        $results['traditional_progress_test'] = $traditionalTest->map(function($row) {
            $totalSessions = $row->total_sessions ?? 0;
            $attendedSessions = $row->attended_sessions ?? 0;
            $progress = $totalSessions > 0 ? round(($attendedSessions / $totalSessions) * 100, 2) : 0;
            
            return [
                'assignment_id' => $row->assignment_id,
                'user_id' => $row->user_id,
                'course_id' => $row->course_id,
                'status' => $row->status,
                'started_at' => $row->started_at,
                'completed_at' => $row->completed_at,
                'deadline' => $row->deadline,
                'total_sessions' => $totalSessions,
                'attended_sessions' => $attendedSessions,
                'calculated_progress' => $progress . '%',
                'days_overdue' => $row->deadline ? $this->calculateDaysOverdue($row->deadline, $row->status) : null,
            ];
        });
        
        // 7. Test online course data
        $onlineTest = DB::table('course_online_assignments as coa')
            ->join('course_online as co', 'coa.course_online_id', '=', 'co.id')
            ->select([
                'coa.id as assignment_id',
                'coa.user_id',
                'coa.course_online_id',
                'coa.status',
                'coa.started_at',
                'coa.completed_at',
                'coa.progress_percentage',
                DB::raw('COALESCE(coa.deadline, co.deadline) as deadline'),
            ])
            ->limit(5)
            ->get();
        
        $results['online_progress_test'] = $onlineTest->map(function($row) {
            return [
                'assignment_id' => $row->assignment_id,
                'user_id' => $row->user_id,
                'course_online_id' => $row->course_online_id,
                'status' => $row->status,
                'started_at' => $row->started_at,
                'completed_at' => $row->completed_at,
                'progress_percentage' => $row->progress_percentage,
                'deadline' => $row->deadline,
                'days_overdue' => $row->deadline ? $this->calculateDaysOverdue($row->deadline, $row->status) : null,
            ];
        });
        
        // 8. Check table columns
        $results['table_columns'] = [
            'course_assignments' => $this->getTableColumns('course_assignments'),
            'course_availabilities' => $this->getTableColumns('course_availabilities'),
            'clockings' => $this->getTableColumns('clockings'),
            'course_online_assignments' => $this->getTableColumns('course_online_assignments'),
        ];
        
        return response()->json($results, 200, [], JSON_PRETTY_PRINT);
    }
    
    private function calculateDaysOverdue($deadline, $status)
    {
        if (!$deadline || $status === 'completed') {
            return null;
        }
        
        $deadlineDate = Carbon::parse($deadline)->startOfDay();
        $now = Carbon::now()->startOfDay();
        
        if ($deadlineDate->isFuture()) {
            return null;
        }
        
        return (int) $now->diffInDays($deadlineDate);
    }
    
    private function getTableColumns($tableName)
    {
        try {
            return DB::getSchemaBuilder()->getColumnListing($tableName);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
