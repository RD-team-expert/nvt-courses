<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DepartmentPerformanceService
{
    /**
     * Get top and bottom performers by department based on evaluation scores
     */
    public function getDepartmentPerformance(array $filters = []): array
    {
        $departments = Department::query();
        
        if (!empty($filters['department_id'])) {
            $departments->where('id', $filters['department_id']);
        }

        $departments = $departments->get();
        $result = [];
        $allUserStats = [];

        foreach ($departments as $department) {
            // Get all users in department with evaluations
            $users = $this->getDepartmentUsersWithScores($department->id, $filters);
            
            if ($users->isEmpty()) {
                continue;
            }

            // Calculate department stats
            $totalEvaluations = $users->sum('total_evaluations');
            $departmentAvg = $users->avg('avg_score');
            
            // Sort users by average score
            $sortedUsers = $users->sortByDesc('avg_score')->values();
            
            // Get top 3
            $topPerformers = $sortedUsers->take(3)->map(function($user, $index) {
                $user->rank = $index + 1;
                return $user;
            });

            // Get bottom 3 (only if we have enough users, otherwise avoid duplication)
            $bottomPerformers = collect([]);
            if ($sortedUsers->count() > 3) {
                $bottomPerformers = $sortedUsers->sortBy('avg_score')->take(3)->values()->map(function($user, $index) {
                    $user->rank = $index + 1; // Rank from bottom? Or absolute rank? usually bottom 3 just listed.
                    // Let's just keep them as is.
                    return $user;
                });
            }

            $result[] = [
                'id' => $department->id,
                'name' => $department->name,
                'top_performers' => $topPerformers,
                'bottom_performers' => $bottomPerformers,
                'department_avg' => round($departmentAvg, 1),
                'total_users_evaluated' => $users->count(),
                'total_evaluations' => $totalEvaluations
            ];

            $allUserStats = array_merge($allUserStats, $users->toArray());
        }

        return [
            'departments' => $result,
            'stats' => $this->calculateOverallStats($result, $allUserStats)
        ];
    }

    /**
     * Get users with their calculated scores for a specific department
     */
    private function getDepartmentUsersWithScores(int $departmentId, array $filters = [])
    {
        $query = User::query()
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.employee_code',
                'users.department_id'
            ])
            ->where('users.department_id', $departmentId)
            ->join('evaluations', 'users.id', '=', 'evaluations.user_id');

        // Apply date filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('evaluations.created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('evaluations.created_at', '<=', $filters['date_to']);
        }

        // Group and aggregate
        return $query->groupBy(
                'users.id', 
                'users.name', 
                'users.email', 
                'users.employee_code', 
                'users.department_id'
            )
            ->selectRaw('
                COUNT(evaluations.id) as total_evaluations,
                SUM(evaluations.total_score) as sum_scores,
                AVG(evaluations.total_score) as avg_score,
                SUM(CASE WHEN evaluations.course_type = "regular" THEN 1 ELSE 0 END) as regular_courses,
                SUM(CASE WHEN evaluations.course_type = "online" THEN 1 ELSE 0 END) as online_courses
            ')
            ->having('total_evaluations', '>', 0)
            ->get()
            ->map(function($user) {
                $user->avg_score = round($user->avg_score, 1);
                return $user;
            });
    }

    /**
     * Calculate overall statistics
     */
    private function calculateOverallStats(array $departmentResults, array $allUsers): array
    {
        if (empty($departmentResults)) {
            return [
                'total_departments' => 0,
                'total_users_evaluated' => 0,
                'overall_avg_score' => 0,
                'highest_department_avg' => 0,
                'lowest_department_avg' => 0
            ];
        }

        $deptAvgs = array_column($departmentResults, 'department_avg');
        $allScores = array_column($allUsers, 'avg_score');

        return [
            'total_departments' => count($departmentResults),
            'total_users_evaluated' => count($allUsers),
            'overall_avg_score' => !empty($allScores) ? round(array_sum($allScores) / count($allScores), 1) : 0,
            'highest_department_avg' => !empty($deptAvgs) ? max($deptAvgs) : 0,
            'lowest_department_avg' => !empty($deptAvgs) ? min($deptAvgs) : 0
        ];
    }
}
