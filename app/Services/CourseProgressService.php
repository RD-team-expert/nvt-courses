<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CourseProgressService
{
    /**
     * Get progress data for all courses (traditional and online)
     * 
     * @param array $filters
     * @return Collection
     */
    public function getProgressData(array $filters): Collection
    {
        // Get both traditional and online course data
        $traditionalData = $this->getTraditionalCourseData($filters);
        $onlineData = $this->getOnlineCourseData($filters);
        
        // Merge and return
        return $traditionalData->merge($onlineData);
    }
    
    /**
     * Get traditional course assignment data
     * 
     * @param array $filters
     * @return Collection
     */
    private function getTraditionalCourseData(array $filters): Collection
    {
        $query = DB::table('course_assignments')
            ->join('users', 'course_assignments.user_id', '=', 'users.id')
            ->join('courses', 'course_assignments.course_id', '=', 'courses.id')
            ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
            ->select([
                'course_assignments.id',
                'course_assignments.user_id',
                'users.name as user_name',
                'users.employee_code',
                'departments.name as department_name',
                DB::raw("'traditional' as course_type"),
                'courses.id as course_id',
                'courses.name as course_name',
                'course_assignments.status',
                'course_assignments.assigned_at',
                'course_assignments.responded_at as started_at',
                'course_assignments.completed_at',
                DB::raw('0 as progress_percentage'),
                DB::raw('NULL as deadline'),
            ]);
        
        // Apply filters
        $this->applyFilters($query, $filters, 'traditional');
        
        return $query->get()->map(function ($assignment) {
            return $this->formatAssignmentData($assignment, 'traditional');
        });
    }
    
    /**
     * Get online course assignment data
     * 
     * @param array $filters
     * @return Collection
     */
    private function getOnlineCourseData(array $filters): Collection
    {
        $query = DB::table('course_online_assignments')
            ->join('users', 'course_online_assignments.user_id', '=', 'users.id')
            ->join('course_online', 'course_online_assignments.course_online_id', '=', 'course_online.id')
            ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
            ->select([
                'course_online_assignments.id',
                'course_online_assignments.user_id',
                'users.name as user_name',
                'users.employee_code',
                'departments.name as department_name',
                DB::raw("'online' as course_type"),
                'course_online.id as course_id',
                'course_online.name as course_name',
                'course_online_assignments.status',
                'course_online_assignments.assigned_at',
                'course_online_assignments.started_at',
                'course_online_assignments.completed_at',
                'course_online_assignments.progress_percentage',
                'course_online_assignments.deadline',
            ]);
        
        // Apply filters
        $this->applyFilters($query, $filters, 'online');
        
        return $query->get()->map(function ($assignment) {
            return $this->formatAssignmentData($assignment, 'online');
        });
    }
    
    /**
     * Apply filters to query
     * 
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $filters
     * @param string $courseType
     * @return void
     */
    private function applyFilters($query, array $filters, string $courseType): void
    {
        // Department filter
        if (!empty($filters['department_id'])) {
            $query->where('users.department_id', $filters['department_id']);
        }
        
        // Course type filter
        if (!empty($filters['course_type']) && $filters['course_type'] !== $courseType) {
            // Skip this query if course type doesn't match
            $query->whereRaw('1 = 0');
            return;
        }
        
        // Date range filter
        if (!empty($filters['date_from'])) {
            $assignedAtColumn = $courseType === 'traditional' 
                ? 'course_assignments.assigned_at' 
                : 'course_online_assignments.assigned_at';
            $query->whereDate($assignedAtColumn, '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $assignedAtColumn = $courseType === 'traditional' 
                ? 'course_assignments.assigned_at' 
                : 'course_online_assignments.assigned_at';
            $query->whereDate($assignedAtColumn, '<=', $filters['date_to']);
        }
        
        // Status filter
        if (!empty($filters['status'])) {
            $statusColumn = $courseType === 'traditional' 
                ? 'course_assignments.status' 
                : 'course_online_assignments.status';
            $query->where($statusColumn, $filters['status']);
        }
        
        // User filter
        if (!empty($filters['user_id'])) {
            $query->where('users.id', $filters['user_id']);
        }
        
        // Course filter
        if (!empty($filters['course_id'])) {
            if ($courseType === 'traditional') {
                $query->where('courses.id', $filters['course_id']);
            } else {
                $query->where('course_online.id', $filters['course_id']);
            }
        }
    }
    
    /**
     * Format assignment data into standardized structure
     * 
     * @param object $assignment
     * @param string $courseType
     * @return array
     */
    private function formatAssignmentData($assignment, string $courseType): array
    {
        $deadline = $assignment->deadline ? Carbon::parse($assignment->deadline) : null;
        $completedAt = $assignment->completed_at ? Carbon::parse($assignment->completed_at) : null;
        $startedAt = isset($assignment->started_at) && $assignment->started_at 
            ? Carbon::parse($assignment->started_at) 
            : null;
        
        // Determine completion status
        $completionStatus = $this->determineCompletionStatus($assignment->status, $deadline);
        
        return [
            'id' => $assignment->id,
            'user_id' => $assignment->user_id,
            'user_name' => $assignment->user_name,
            'employee_code' => $assignment->employee_code ?? 'N/A',
            'department' => $assignment->department_name ?? 'N/A',
            'course_type' => $courseType,
            'course_id' => $assignment->course_id,
            'course_name' => $assignment->course_name,
            'completion_status' => $completionStatus,
            'status' => $assignment->status,
            'progress_percentage' => (float) ($assignment->progress_percentage ?? 0),
            'assigned_at' => Carbon::parse($assignment->assigned_at),
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'deadline' => $deadline,
            'days_overdue' => $this->calculateDaysOverdue($deadline, $assignment->status),
            'compliance_status' => $this->calculateComplianceStatus($deadline, $assignment->status, $assignment->progress_percentage ?? 0),
        ];
    }
    
    /**
     * Determine completion status label
     * 
     * @param string $status
     * @param Carbon|null $deadline
     * @return string
     */
    private function determineCompletionStatus(string $status, ?Carbon $deadline): string
    {
        if ($status === 'completed') {
            return 'Completed';
        }
        
        if ($deadline && $deadline->isPast()) {
            return 'Overdue';
        }
        
        if ($status === 'in_progress' || $status === 'assigned') {
            return 'In Progress';
        }
        
        return 'In Progress';
    }

    /**
     * Determine score band from learning score
     * 
     * @param float $score
     * @return string
     */
    public function determineScoreBand(float $score): string
    {
        if ($score >= 85) {
            return 'Excellent';
        } elseif ($score >= 70) {
            return 'Good';
        } else {
            return 'Needs Attention';
        }
    }

    /**
     * Calculate compliance status based on deadline and progress
     * 
     * @param Carbon|null $deadline
     * @param string $status
     * @param float $progress
     * @return string
     */
    public function calculateComplianceStatus(?Carbon $deadline, string $status, float $progress): string
    {
        // Completed assignments are always compliant
        if ($status === 'completed') {
            return 'Compliant';
        }
        
        // No deadline means compliant
        if (!$deadline) {
            return 'Compliant';
        }
        
        $now = Carbon::now();
        
        // Past deadline = Non-Compliant
        if ($deadline->isPast()) {
            return 'Non-Compliant';
        }
        
        // Within 7 days of deadline = At Risk
        $daysUntilDeadline = $now->diffInDays($deadline, false);
        if ($daysUntilDeadline >= 0 && $daysUntilDeadline <= 7) {
            return 'At Risk';
        }
        
        // More than 7 days away = Compliant
        return 'Compliant';
    }

    /**
     * Calculate days overdue for incomplete assignments
     * 
     * @param Carbon|null $deadline
     * @param string $status
     * @return int|null
     */
    public function calculateDaysOverdue(?Carbon $deadline, string $status): ?int
    {
        // No deadline or completed assignments don't have days overdue
        if (!$deadline || $status === 'completed') {
            return null;
        }
        
        $now = Carbon::now();
        
        // If deadline is in the future, not overdue
        if ($deadline->isFuture()) {
            return null;
        }
        
        // Calculate days past deadline
        return $now->diffInDays($deadline, false);
    }
}
