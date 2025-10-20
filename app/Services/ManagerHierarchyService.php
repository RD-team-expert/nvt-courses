<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserLevel;
use App\Models\UserDepartmentRole;
use App\Models\Department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ManagerHierarchyService
{
    /**
     * Get managers for specific employees at target levels
     * Uses database relationships instead of hardcoded mappings
     */
    public function getManagersForEmployees(array $employeeIds, array $targetLevels = ['L2', 'L3', 'L4']): array
    {
        $employees = User::with(['userLevel', 'department'])
            ->whereIn('id', $employeeIds)
            ->get();

        $managersByLevel = [
            'L2' => [],
            'L3' => [],
            'L4' => []
        ];

        foreach ($employees as $employee) {
            $managers = $this->getManagersForUser($employee->id, $targetLevels);

            foreach ($managers as $managerData) {
                $level = $managerData['level'];
                if (in_array($level, $targetLevels) || in_array('all', $targetLevels)) {
                    $manager = $managerData['manager'];
                    // Avoid duplicates using manager ID as key
                    $managersByLevel[$level][$manager->id] = [
                        'id' => $manager->id,
                        'name' => $manager->name,
                        'email' => $manager->email,
                        'level' => $manager->userLevel?->name ?? 'Unknown',
                        'departments' => [$manager->department?->name ?? 'No Department']
                    ];
                }
            }
        }

        // Convert to arrays and remove keys
        foreach ($managersByLevel as $level => $managers) {
            $managersByLevel[$level] = array_values($managers);
        }

        return $managersByLevel;
    }

    /**
     * Get managers for a specific user based on database relationships
     * Uses UserDepartmentRole to find assigned managers
     */
    public function getManagersForUser(int $userId, array $targetLevels = ['L2', 'L3', 'L4']): array
    {
        $user = User::with(['department', 'userLevel'])->find($userId);

        if (!$user || !$user->department) {
            return [];
        }

        // Get managers from UserDepartmentRole relationships
        $managers = $this->getManagersFromDepartmentRoles($user, $targetLevels);

        // If no specific managers found, get department-level managers
        if (empty($managers)) {
            $managers = $this->getDepartmentManagers($user->department, $targetLevels);
        }

        return $managers;
    }

    /**
     * Get managers from UserDepartmentRole relationships
     */
    private function getManagersFromDepartmentRoles(User $user, array $targetLevels): array
    {
        $managers = [];

        // Get active manager roles in the user's department
        $managerRoles = UserDepartmentRole::with(['manager.userLevel', 'manager.department'])
            ->where('department_id', $user->department_id)
            ->active() // Uses the scope you already have
            ->whereIn('role_type', $this->mapLevelsToRoleTypes($targetLevels))
            ->get();

        foreach ($managerRoles as $role) {
            if (!$role->manager) continue;

            $managerLevel = $this->getUserLevelCode($role->manager);

            if (in_array($managerLevel, $targetLevels) || in_array('all', $targetLevels)) {
                $managers[] = [
                    'manager' => $role->manager,
                    'level' => $managerLevel,
                    'relationship' => 'department_role',
                    'role_type' => $role->role_type,
                    'is_primary' => $role->is_primary
                ];
            }
        }

        return $managers;
    }

    /**
     * Get department-level managers as fallback
     */
    private function getDepartmentManagers(Department $department, array $targetLevels): array
    {
        $managers = [];

        // Get users with manager roles in this department
        $departmentManagers = $department->managers()
            ->with(['userLevel'])
            ->get();

        foreach ($departmentManagers as $manager) {
            $managerLevel = $this->getUserLevelCode($manager);

            if (in_array($managerLevel, $targetLevels) || in_array('all', $targetLevels)) {
                $managers[] = [
                    'manager' => $manager,
                    'level' => $managerLevel,
                    'relationship' => 'department_manager',
                    'role_type' => $manager->pivot->role_type ?? 'manager',
                    'is_primary' => $manager->pivot->is_primary ?? false
                ];
            }
        }

        return $managers;
    }

    /**
     * Map user levels to role types
     */
    private function mapLevelsToRoleTypes(array $levels): array
    {
        $roleTypes = [];

        foreach ($levels as $level) {
            switch ($level) {
                case 'L2':
                    $roleTypes[] = 'direct_manager';
                    $roleTypes[] = 'project_manager';
                    break;
                case 'L3':
                    $roleTypes[] = 'senior_manager';
                    $roleTypes[] = 'department_head';
                    break;
                case 'L4':
                    $roleTypes[] = 'executive';
                    $roleTypes[] = 'director';
                    break;
            }
        }

        return array_unique($roleTypes);
    }

    /**
     * Get user level code (L1, L2, L3, L4)
     */
    private function getUserLevelCode(User $user): string
    {
        if (!$user->userLevel) {
            return 'Unknown';
        }

        // Map user_level_id to level codes
        return match($user->user_level_id) {
            1 => 'L1',
            2 => 'L2',
            3 => 'L3',
            4 => 'L4',
            default => $user->userLevel->code ?? 'Unknown'
        };
    }

    /**
     * Get direct managers for a user (L2 only)
     */
    public function getDirectManagersForUser(int $userId): array
    {
        return $this->getManagersForUser($userId, ['L2']);
    }

    /**
     * Get managers for multiple users (optimized)
     */
    public function getManagersForMultipleUsers(array $userIds, array $targetLevels = ['L2']): array
    {
        $users = User::with(['department', 'userLevel'])
            ->whereIn('id', $userIds)
            ->get();

        $managersByUser = [];

        foreach ($users as $user) {
            $managersByUser[$user->id] = $this->getManagersForUser($user->id, $targetLevels);
        }

        return $managersByUser;
    }

    /**
     * Assign a manager to a user
     */
    public function assignManager(int $userId, int $managerId, string $roleType = 'direct_manager', ?int $departmentId = null): UserDepartmentRole
    {
        $user = User::findOrFail($userId);
        $manager = User::findOrFail($managerId);

        // Use user's department if not specified
        $departmentId = $departmentId ?? $user->department_id;

        if (!$departmentId) {
            throw new \Exception('Department is required for manager assignment');
        }

        return UserDepartmentRole::create([
            'user_id' => $managerId,
            'department_id' => $departmentId,
            'role_type' => $roleType,
            'manages_user_id' => $userId,
            'is_primary' => true,
            'start_date' => now(),
            'created_by' => auth()->id()
        ]);
    }

    /**
     * Remove manager assignment
     */
    public function removeManager(int $userId, int $managerId): bool
    {
        return UserDepartmentRole::where('user_id', $managerId)
                ->where('manages_user_id', $userId)
                ->active()
                ->update(['end_date' => now()]) > 0;
    }

    /**
     * Get all possible managers for assignment (users with management levels)
     */
    public function getAvailableManagers(?int $departmentId = null): Collection
    {
        $query = User::with(['userLevel', 'department'])
            ->whereHas('userLevel', function($q) {
                $q->where('hierarchy_level', '>', 1); // Above L1
            });

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Validate that employees are L1 level
     */
    /**
     * Validate that employees are L1 or L2 level
     */
    public function validateL1Employees(array $employeeIds): array
    {
        $employees = User::with('userLevel')
            ->whereIn('id', $employeeIds)
            ->get();

        $valid = [];
        $invalid = [];

        foreach ($employees as $employee) {
            if (in_array($employee->user_level_id, [1, 2])) { // Allow L1 and L2
                $valid[] = $employee->id;
            } else {
                $invalid[] = [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'level' => $employee->userLevel?->name ?? 'Unknown'
                ];
            }
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid
        ];
    }
    /**
     * Get L1 employees with completed evaluations for filtering
     */
    public function getL1EmployeesWithEvaluations(array $filters = []): Collection
    {
        $query = User::with(['userLevel', 'department', 'evaluations.course', 'evaluations.history'])
            ->where('user_level_id', 1)
            ->whereHas('evaluations');

        // Apply filters
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['course_id'])) {
            $query->whereHas('evaluations', function($q) use ($filters) {
                $q->where('course_id', $filters['course_id']);
            });
        }

        if (!empty($filters['start_date'])) {
            $query->whereHas('evaluations', function($q) use ($filters) {
                $q->whereDate('created_at', '>=', $filters['start_date']);
            });
        }

        if (!empty($filters['end_date'])) {
            $query->whereHas('evaluations', function($q) use ($filters) {
                $q->whereDate('created_at', '<=', $filters['end_date']);
            });
        }

        return $query->get();
    }

    /**
     * Preview notification - show which managers will receive emails
     */
    public function previewNotification(array $employeeIds, array $targetLevels): array
    {
        $employees = User::with(['userLevel', 'department', 'evaluations.history'])
            ->whereIn('id', $employeeIds)
            ->get();

        $managers = $this->getManagersForEmployees($employeeIds, $targetLevels);

        $preview = [
            'employees' => $employees->map(function($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'department' => $employee->department?->name ?? 'No Department',
                    'level' => $employee->userLevel?->name ?? 'Unknown',
                    'evaluation_count' => $employee->evaluations->count()
                ];
            })->toArray(),
            'managers' => $managers,
            'summary' => [
                'total_employees' => count($employeeIds),
                'total_managers' => array_sum(array_map('count', $managers)),
                'departments' => $employees->pluck('department.name')->filter()->unique()->values()->toArray(),
                'target_levels' => $targetLevels
            ]
        ];

        return $preview;
    }

    /**
     * Get department hierarchy chain for display
     */
    public function getDepartmentHierarchy(int $departmentId): array
    {
        $hierarchy = [];

        $managerRoles = UserDepartmentRole::with(['manager.userLevel'])
            ->where('department_id', $departmentId)
            ->active()
            ->orderBy('authority_level')
            ->get();

        foreach ($managerRoles as $role) {
            if (!$role->manager) continue;

            $level = $this->getUserLevelCode($role->manager);
            $hierarchy[$level] = $role->manager->name . ' (' . $role->getRoleDisplayName() . ')';
        }

        return $hierarchy;
    }
}
