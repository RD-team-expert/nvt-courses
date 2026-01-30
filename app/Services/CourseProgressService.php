<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\LearningScoreCalculator;

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
     * Progress is calculated from Clocking model (attended sessions / total sessions from CourseAvailability)
     * Deadline is taken from CourseAvailability.end_date or courses.end_date as fallback
     * Status, Started, and Completed are merged from course_registrations and course_assignments
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
            // LEFT JOIN course_registrations to get completion data
            ->leftJoin('course_registrations', function($join) {
                $join->on('course_assignments.user_id', '=', 'course_registrations.user_id')
                     ->on('course_assignments.course_id', '=', 'course_registrations.course_id');
            })
            ->select([
                'course_assignments.id',
                'course_assignments.user_id',
                'users.name as user_name',
                'users.employee_code',
                'departments.name as department_name',
                DB::raw("'traditional' as course_type"),
                'courses.id as course_id',
                'courses.name as course_name',
                'course_assignments.status as assignment_status',
                'course_assignments.assigned_at',
                'course_assignments.responded_at',
                'course_assignments.completed_at as assignment_completed_at',
                // Get data from course_registrations (priority source)
                'course_registrations.status as registration_status',
                'course_registrations.registered_at',
                'course_registrations.completed_at as registration_completed_at',
                // Deadline: use courses.end_date (we'll get the latest availability end_date separately)
                'courses.end_date as course_end_date',
            'courses.start_date as course_beginning_date', // ✅ This exists
            ]);
        
        // Apply filters
        $this->applyFilters($query, $filters, 'traditional');
        
        return $query->get()->map(function ($assignment) {
            // Get clocking data for this user/course
            $clockingData = DB::table('clockings')
                ->where('user_id', $assignment->user_id)
                ->where('course_id', $assignment->course_id)
                ->whereNotNull('clock_out')
                ->selectRaw('COUNT(*) as attended_sessions, MIN(clock_in) as first_clock_in, MAX(clock_out) as last_clock_out')
                ->first();
            
            $attendedSessions = $clockingData->attended_sessions ?? 0;
            $firstClockIn = $clockingData->first_clock_in;
            $lastClockOut = $clockingData->last_clock_out;
            
            // Get total sessions from ALL course availabilities for this course
            // Since course_assignments don't specify which availability, we sum all sessions
            $availabilityData = DB::table('course_availabilities')
                ->where('course_id', $assignment->course_id)
                ->selectRaw('SUM(sessions) as total_sessions, MAX(end_date) as latest_end_date')
                ->first();
            
            $totalSessions = $availabilityData->total_sessions ?? 0;
            $latestEndDate = $availabilityData->latest_end_date;
            
            // Use the latest availability end_date as deadline, fallback to course end_date
            $deadline = $latestEndDate ?? $assignment->course_end_date;
            
            // STATUS CALCULATION - Priority: course_registrations > course_assignments
            // This matches CourseCompletion report logic
            $calculatedStatus = 'pending';
            $completedAt = null;
            $startedAt = null;
            
            // 1. Check course_registrations first (highest priority)
            if ($assignment->registration_status) {
                $calculatedStatus = $assignment->registration_status;
                if ($assignment->registration_completed_at) {
                    $calculatedStatus = 'completed';
                    $completedAt = Carbon::parse($assignment->registration_completed_at);
                }
                if ($assignment->registered_at) {
                    $startedAt = Carbon::parse($assignment->registered_at);
                }
            }
            // 2. Fall back to course_assignments data
            else {
                if ($assignment->assignment_completed_at) {
                    $calculatedStatus = 'completed';
                    $completedAt = Carbon::parse($assignment->assignment_completed_at);
                } elseif ($assignment->assignment_status === 'in_progress' || $assignment->assignment_status === 'inprogress') {
                    $calculatedStatus = 'in_progress';
                } else {
                    $calculatedStatus = $assignment->assignment_status;
                }
                
                // Use responded_at as started date
                if ($assignment->responded_at) {
                    $startedAt = Carbon::parse($assignment->responded_at);
                }
            }
            
            // 3. Override with clocking data if available
            if ($firstClockIn && !$startedAt) {
                $startedAt = Carbon::parse($firstClockIn);
            }
            
            // Calculate progress percentage based on actual attendance
            if ($totalSessions > 0) {
                $progressPercentage = min(round(($attendedSessions / $totalSessions) * 100, 2), 100);
            } else {
                // No total sessions defined - can't calculate accurate progress
                $progressPercentage = 0;
            }
            
            // IMPORTANT: If status is 'completed', override progress to 100%
            // This ensures consistency - completed courses should always show 100% progress
            // Admin may mark as completed even if attendance is incomplete (makeup sessions, special cases, etc.)
            if ($calculatedStatus === 'completed') {
                $progressPercentage = 100;
                // Use completion date if no clock out data
                if (!$completedAt && $lastClockOut) {
                    $completedAt = Carbon::parse($lastClockOut);
                }
            }
            // If progress is 100% but not marked completed, auto-complete
            elseif ($progressPercentage >= 100 && $lastClockOut) {
                $calculatedStatus = 'completed';
                $completedAt = Carbon::parse($lastClockOut);
            }
            
            // Update assignment object with calculated values
            $assignment->progress_percentage = $progressPercentage;
            $assignment->attended_sessions = $attendedSessions;
            $assignment->total_sessions = $totalSessions;
            $assignment->status = $calculatedStatus; // Use calculated status
            $assignment->started_at = $startedAt;
            $assignment->completed_at = $completedAt;
            $assignment->deadline = $deadline; // Use calculated deadline
            
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
                // Use course deadline if assignment deadline is null
                DB::raw('COALESCE(course_online_assignments.deadline, course_online.deadline) as deadline'),
                // Course beginning date for online courses (use assignment date)
                'course_online_assignments.assigned_at as course_beginning_date',
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
    
    // Parse course beginning date - will be null for online courses
    $courseBeginningDate = isset($assignment->course_beginning_date) && $assignment->course_beginning_date
        ? Carbon::parse($assignment->course_beginning_date)
        : null;
    
    $progressPercentage = (float) ($assignment->progress_percentage ?? 0);
    
    // Calculate learning score
    $learningScore = $this->calculateLearningScore(
        $assignment->user_id, 
        $assignment->course_id, 
        $courseType, 
        $assignment->status,
        $progressPercentage
    );
    
    // Determine completion status
    $completionStatus = $this->determineCompletionStatus($assignment->status, $deadline);
    
    // Calculate days overdue
    $daysOverdue = $this->calculateDaysOverdue($deadline, $assignment->status);
    
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
        'progress_percentage' => $progressPercentage,
        'assigned_at' => Carbon::parse($assignment->assigned_at),
        'started_at' => $startedAt,
        'completed_at' => $completedAt,
        'deadline' => $deadline,
        'course_beginning_date' => $courseBeginningDate, // Will be Carbon for traditional, null for online
        'days_overdue' => $daysOverdue,
        'learning_score' => $learningScore,
        'score_band' => $this->determineScoreBand($learningScore),
        'compliance_status' => $this->calculateComplianceStatus($deadline, $assignment->status, $progressPercentage, $learningScore),
        'total_sessions' => $assignment->total_sessions ?? null,
        'attended_sessions' => $assignment->attended_sessions ?? null,
    ];
}

    
    /**
     * Determine completion status label based on calculated status
     * 
     * @param string $status - The calculated status (completed, in_progress, assigned, pending)
     * @param Carbon|null $deadline
     * @return string
     */
    private function determineCompletionStatus(string $status, ?Carbon $deadline): string
    {
        // Map status to display labels
        if ($status === 'completed') {
            return 'Completed';
        }
        
        if ($status === 'in_progress') {
            return 'In Progress';
        }
        
        // For assigned/pending status
        return 'Not Started';
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
    public function calculateComplianceStatus(?Carbon $deadline, string $status, float $progress, float $learningScore = 0): string
    {
        // For completed assignments, check learning score
        if ($status === 'completed') {
            // If learning score indicates 'Needs Attention', mark as Non-Compliant
            if ($learningScore < 70) {
                return 'Non-Compliant';
            }
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
     * For traditional courses: uses CourseAvailability.end_date (passed as $deadline)
     * For online courses: uses CourseOnline.deadline or CourseOnlineAssignment.deadline (passed as $deadline)
     * 
     * @param Carbon|null $deadline - CourseAvailability.end_date for traditional, CourseOnline.deadline for online
     * @param string $status
     * @return int|null
     */
    public function calculateDaysOverdue(?Carbon $deadline, string $status): ?int
    {
        // No deadline or completed assignments don't have days overdue
        if (!$deadline || $status === 'completed') {
            return null;
        }
        
        $now = Carbon::now()->startOfDay();
        $deadlineDate = $deadline->copy()->startOfDay();
        
        // If deadline is in the future or today, not overdue
        if ($deadlineDate->gte($now)) {
            return null;
        }
        
        // Calculate days past deadline (positive number = days overdue)
        // Use absolute value to ensure positive result
        return (int) abs($now->diffInDays($deadlineDate));
    }

    /**
     * Calculate learning score for an assignment
     * 
     * Traditional courses: Uses completion rate, progress, and quiz score (3 components)
     * Online courses: Uses completion rate, progress, attention score, and quiz score (4 components)
     * 
     * @param int $userId
     * @param int $courseId
     * @param string $courseType
     * @param string $status
     * @param float $progress
     * @return float
     */
    public function calculateLearningScore(int $userId, int $courseId, string $courseType, string $status, float $progress): float
    {
        $learningScoreCalculator = new LearningScoreCalculator();
        
        // Calculate completion rate
        $completionRate = $status === 'completed' ? 100 : 0;
        
        // Get attention score (only for online courses)
        $attentionScore = 0;
        if ($courseType === 'online') {
            $attentionScore = $learningScoreCalculator->getAttentionScore($userId, $courseId, $courseType);
        }
        
        // Get quiz score
        $quizScore = $this->getQuizScore($userId, $courseId, $courseType);
        
        // Get suspicious activities (only for online courses)
        $suspiciousActivities = 0;
        $totalSessions = 1; // Default to avoid division by zero
        
        if ($courseType === 'online') {
            // Count suspicious sessions for online courses
            $suspiciousCount = DB::table('learning_sessions')
                ->where('user_id', $userId)
                ->where('course_online_id', $courseId)
                ->where('is_suspicious_activity', true)
                ->count();
            
            $totalSessionsCount = DB::table('learning_sessions')
                ->where('user_id', $userId)
                ->where('course_online_id', $courseId)
                ->count();
            
            $suspiciousActivities = $suspiciousCount;
            $totalSessions = max(1, $totalSessionsCount);
        }
        
        // Calculate final score with course type
        return $learningScoreCalculator->calculate(
            $completionRate,
            $progress,
            $attentionScore,
            $quizScore,
            $suspiciousActivities,
            $totalSessions,
            $courseType
        );
    }

    /**
     * Get quiz score for a user's course
     * 
     * @param int $userId
     * @param int $courseId
     * @param string $courseType
     * @return float
     */
    private function getQuizScore(int $userId, int $courseId, string $courseType): float
    {
        // Delegate to the LearningScoreCalculator to keep quiz scoring logic centralized
        try {
            $calculator = new LearningScoreCalculator();
            return $calculator->getQuizScore($userId, $courseType === 'online' ? $courseId : $courseId);
        } catch (\Exception $e) {
            \Log::error('Error fetching quiz score in CourseProgressService: ' . $e->getMessage());
            return 0;
        }
    }
}
