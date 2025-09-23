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
     *
     * @param array $employeeIds - Array of L1 employee IDs
     * @param array $targetLevels - Array of levels ['L2', 'L3', 'L4'] or ['all']
     * @return array - Grouped managers by level and department
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
            $departmentName = $employee->department?->name ?? 'Unknown Department';

            $managers = $this->getManagersForDepartment($departmentName, $targetLevels);

            foreach ($managers as $level => $levelManagers) {
                if (in_array($level, $targetLevels) || in_array('all', $targetLevels)) {
                    foreach ($levelManagers as $manager) {
                        // Avoid duplicates using manager ID as key
                        $managersByLevel[$level][$manager['id']] = $manager;
                    }
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
     * Get managers for a specific department based on your hierarchy
     *
     * @param string $departmentName
     * @param array $targetLevels
     * @return array
     */
    public function getManagersForDepartment(string $departmentName, array $targetLevels = ['L2', 'L3', 'L4']): array
    {
        $managers = [
            'L2' => [],
            'L3' => [],
            'L4' => []
        ];

        // Your specific hierarchy mappings based on the document
        switch (strtolower($departmentName)) {
            case 'real estate':
                if (in_array('L3', $targetLevels) || in_array('all', $targetLevels)) {
                    $managers['L3'][] = $this->getManagerByName('Batool');
                }
                if (in_array('L4', $targetLevels) || in_array('all', $targetLevels)) {
                    $managers['L4'][] = $this->getManagerByName('Sam');
                }
                break;

            case 'builders':
                if (in_array('L2', $targetLevels) || in_array('all', $targetLevels)) {
                    $managers['L2'][] = $this->getManagerByName('Hannah');
                }
                if (in_array('L3', $targetLevels) || in_array('all', $targetLevels)) {
                    $managers['L3'][] = $this->getManagerByName('Batool');
                }
                if (in_array('L4', $targetLevels) || in_array('all', $targetLevels)) {
                    $managers['L4'][] = $this->getManagerByName('Sam');
                }
                break;

            case 'management':
                if (in_array('L2', $targetLevels) || in_array('all', $targetLevels)) {
                    $managers['L2'][] = $this->getManagerByName('Bia');
                }
                break;

            case '3d design':
                if (in_array('L2', $targetLevels) || in_array('all', $targetLevels)) {
                    $managers['L2'][] = $this->getManagerByName('Kain');
                }
                break;

            case 'r&d':
            case 'research and development':
                if (in_array('L2', $targetLevels) || in_array('all', $targetLevels)) {
                    $managers['L2'][] = $this->getManagerByName('Jaden');
                }
                if (in_array('L3', $targetLevels) || in_array('all', $targetLevels)) {
                    $managers['L3'][] = $this->getManagerByName('George');
                }
                if (in_array('L4', $targetLevels) || in_array('all', $targetLevels)) {
                    $managers['L4'][] = $this->getManagerByName('Sam');
                }
                break;

            case 'marketing':
                if (in_array('L2', $targetLevels) || in_array('all', $targetLevels)) {
                    $managers['L2'][] = $this->getManagerByName('Alina');
                }
                if (in_array('L3', $targetLevels) || in_array('all', $targetLevels)) {
                    $managers['L3'][] = $this->getManagerByName('David');
                }
                if (in_array('L4', $targetLevels) || in_array('all', $targetLevels)) {
                    $managers['L4'][] = $this->getManagerByName('Moe');
                }
                break;
        }

        // Filter out null managers
        foreach ($managers as $level => $levelManagers) {
            $managers[$level] = array_filter($levelManagers, function($manager) {
                return $manager !== null;
            });
        }

        return $managers;
    }
    /**
     * Get manager information by name
     *
     * @param string $name
     * @return array|null
     */
    private function getManagerByName(string $name): ?array
    {
        $user = User::where('name', 'like', '%' . $name . '%')
            ->with(['userLevel', 'department'])
            ->first();

        if (!$user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'level' => $user->userLevel->name ?? 'Unknown',
            'departments' => [$user->department?->name ?? 'No Department']
        ];
    }
    /**
     * Get all L1 employees with completed evaluations for filtering
     *
     * @param array $filters - department_id, course_id, date_range
     * @return Collection
     */
    public function getL1EmployeesWithEvaluations(array $filters = []): Collection
    {
        $query = User::with(['userLevel', 'department', 'evaluations.course', 'evaluations.history']) // FIXED: Changed evaluationHistory to history
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
     *
     * @param array $employeeIds
     * @param array $targetLevels
     * @return array
     */
    public function previewNotification(array $employeeIds, array $targetLevels): array
    {
        $employees = User::with(['userLevel', 'department', 'evaluations.history']) // FIXED: Changed evaluationHistory to history
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
    }    /**
     * Validate that employees are L1 level
     *
     * @param array $employeeIds
     * @return array - ['valid' => [...], 'invalid' => [...]]
     */
    public function validateL1Employees(array $employeeIds): array
    {
        $employees = User::with('userLevel')
            ->whereIn('id', $employeeIds)
            ->get();

        $valid = [];
        $invalid = [];

        foreach ($employees as $employee) {
            if ($employee->user_level_id == 1) { // Check user_level_id directly
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
     * Get department hierarchy chain for display
     *
     * @param string $departmentName
     * @return array
     */
    public function getDepartmentHierarchy(string $departmentName): array
    {
        $hierarchy = [];

        switch (strtolower($departmentName)) {
            case 'real estate':
                $hierarchy = [
                    'L3' => 'Batool (Senior Manager)',
                    'L4' => 'Sam (Executive)'
                ];
                break;

            case 'builders':
                $hierarchy = [
                    'L2' => 'Hannah (Direct Manager)',
                    'L3' => 'Batool (Senior Manager)',
                    'L4' => 'Sam (Executive)'
                ];
                break;

            case 'r&d':
                $hierarchy = [
                    'L2' => 'Jaden (Direct Manager)',
                    'L3' => 'George (Head of Department)',
                    'L4' => 'Sam (Executive)'
                ];
                break;

            case 'marketing':
                $hierarchy = [
                    'L2' => 'Alina (Project Manager)',
                    'L3' => 'David (Head of Department)',
                    'L4' => 'Moe (Executive)'
                ];
                break;

            case 'management':
                $hierarchy = [
                    'L2' => 'Bia (Direct Manager)'
                ];
                break;

            case '3d design':
                $hierarchy = [
                    'L2' => 'Kain (Direct Manager)'
                ];
                break;
        }

        return $hierarchy;
    }

    public function getManagersForUser(int $userId, array $targetLevels = ['L2', 'L3', 'L4']): array
    {
        $user = User::with(['userLevel', 'department'])
            ->find($userId);

        if (!$user) {
            return [];
        }

        $departmentName = $user->department?->name ?? 'Unknown Department';
        $managers = $this->getManagersForDepartment($departmentName, $targetLevels);

        // Convert to the format expected by the controller
        $result = [];
        foreach ($managers as $level => $levelManagers) {
            foreach ($levelManagers as $manager) {
                if ($manager) {
                    $result[] = [
                        'manager' => (object) [
                            'id' => $manager['id'],
                            'name' => $manager['name'],
                            'email' => $manager['email'],
                            'level' => $manager['level'],
                            'departments' => $manager['departments']
                        ],
                        'level' => $level,
                        'relationship' => 'department_hierarchy'
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * 🎯 ALTERNATIVE: Get direct managers for a user (simpler approach)
     *
     * @param int $userId
     * @return array
     */
    public function getDirectManagersForUser(int $userId): array
    {
        $user = User::with(['department'])->find($userId);

        if (!$user) {
            return [];
        }

        // Get L2 managers only (direct managers)
        $departmentName = $user->department?->name ?? 'Unknown Department';
        $managers = $this->getManagersForDepartment($departmentName, ['L2']);

        $result = [];
        foreach ($managers['L2'] as $manager) {
            if ($manager) {
                $result[] = [
                    'manager' => (object) [
                        'id' => $manager['id'],
                        'name' => $manager['name'],
                        'email' => $manager['email'],
                        'level' => $manager['level'],
                        'departments' => $manager['departments']
                    ],
                    'level' => 'L2',
                    'relationship' => 'direct_manager'
                ];
            }
        }

        return $result;
    }

    /**
     * 🎯 BULK VERSION: Get managers for multiple users (optimized)
     *
     * @param array $userIds
     * @param array $targetLevels
     * @return array
     */
    public function getManagersForMultipleUsers(array $userIds, array $targetLevels = ['L2']): array
    {
        $users = User::with(['department'])
            ->whereIn('id', $userIds)
            ->get();

        $managersByUser = [];

        foreach ($users as $user) {
            $departmentName = $user->department?->name ?? 'Unknown Department';
            $managers = $this->getManagersForDepartment($departmentName, $targetLevels);

            $userManagers = [];
            foreach ($managers as $level => $levelManagers) {
                foreach ($levelManagers as $manager) {
                    if ($manager) {
                        $userManagers[] = [
                            'manager' => (object) [
                                'id' => $manager['id'],
                                'name' => $manager['name'],
                                'email' => $manager['email'],
                                'level' => $manager['level'],
                                'departments' => $manager['departments']
                            ],
                            'level' => $level,
                            'relationship' => 'department_hierarchy'
                        ];
                    }
                }
            }

            $managersByUser[$user->id] = $userManagers;
        }

        return $managersByUser;
    }
}
