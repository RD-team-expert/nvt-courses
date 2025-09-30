<?php
// app/Http/Controllers/Admin/UserDepartmentRoleController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserDepartmentRole;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class UserDepartmentRoleController extends Controller
{
    /**
     * Display manager role assignments
     */
    public function index(): Response
    {
        $roles = UserDepartmentRole::with([
            'manager.userLevel',
            'department',
            'managedUser'
        ])
            ->active()
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(function ($role) {
                return [
                    'id' => $role->id,
                    'manager' => [
                        'id' => $role->manager->id,
                        'name' => $role->manager->name,
                        'email' => $role->manager->email,
                        'level' => $role->manager->userLevel?->name,
                    ],
                    'department' => [
                        'id' => $role->department->id,
                        'name' => $role->department->name,
                        'code' => $role->department->department_code,
                    ],
                    'managed_user' => $role->managedUser ? [
                        'id' => $role->managedUser->id,
                        'name' => $role->managedUser->name,
                        'email' => $role->managedUser->email,
                    ] : null,
                    'role_type' => $role->role_type,
                    'role_display' => $role->getRoleDisplayName(),
                    'is_primary' => $role->is_primary,
                    'authority_level' => $role->authority_level,
                    'start_date' => $role->start_date->format('Y-m-d'),
                    'end_date' => $role->end_date?->format('Y-m-d'),
                    'is_active' => $role->isActive(),
                ];
            });

        return Inertia::render('Admin/ManagerRoles/Index', [
            'roles' => $roles,
            'stats' => [
                'total_roles' => UserDepartmentRole::count(),
                'active_roles' => UserDepartmentRole::active()->count(),
                'direct_managers' => UserDepartmentRole::active()->where('role_type', 'direct_manager')->count(),
                'department_heads' => UserDepartmentRole::active()->where('role_type', 'department_head')->count(),
            ]
        ]);
    }

    /**
     * Show create role assignment form
     */
    public function create(): Response
    {
        $managers = User::whereHas('userLevel', function ($query) {
            $query->where('hierarchy_level', '>=', 2); // L2, L3, L4 can be managers
        })
            ->where('status', 'active')
            ->with('userLevel', 'department')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'level' => $user->userLevel?->name,
                    'department' => $user->department?->name,
                ];
            });

        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'department_code']);

        // ✅ FIXED: Get all employees with proper relationships
        $employees = User::where('status', 'active')
            ->with(['userLevel', 'department']) // Make sure relationships are loaded
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'level' => $user->userLevel?->name, // This should not be null
                    'levelcode' => $user->userLevel?->code, // Add level code
                    'department' => $user->department?->name, // This should not be null
                    'departmentid' => $user->department_id, // Add department ID
                    'department_id' => $user->department_id, // Alternative field name
                    'employeecode' => $user->employee_code,
                ];
            });

        Log::info('Controller data:');
        Log::info('Employees count: ' . $employees->count());

        foreach ($employees as $emp) {
            \Log::info("Employee: {$emp['name']} | Level: {$emp['level']} | Department: {$emp['department']}");
        }
//        dd($employees);

        return Inertia::render('Admin/ManagerRoles/Assignment', [
            'managers' => $managers,
            'departments' => $departments,
            'employees' => $employees,
            'roleTypes' => [
                'direct_manager' => 'Direct Manager',
                'project_manager' => 'Project Manager',
                'director' => 'Director',
                'senior_manager' => 'Senior Manager',
                'supervisor ' => 'Supervisor ',
            ]
        ]);
    }
    /**
     * Store new role assignment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'department_id' => 'required|exists:departments,id',
            'role_type' => 'required|in:direct_manager,project_manager,director,senior_manager,supervisor',
            'manages_user_id' => 'nullable|exists:users,id',
            'is_primary' => 'boolean',
            'authority_level' => 'required|integer|min:1|max:3',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        // Check for conflicts (same user, department, role type)
        $existingRole = UserDepartmentRole::where('user_id', $validated['user_id'])
            ->where('department_id', $validated['department_id'])
            ->where('role_type', $validated['role_type'])
            ->active()
            ->first();

        if ($existingRole) {
            return back()->withErrors([
                'role_conflict' => 'User already has this role in the department.'
            ]);
        }

        UserDepartmentRole::create($validated);

        return redirect()
            ->route('admin.manager-roles.index')
            ->with('success', 'Manager role assigned successfully.');
    }

    /**
     * Show specific role assignment
     */
    public function show(UserDepartmentRole $userDepartmentRole): Response
    {
        $userDepartmentRole->load([
            'manager.userLevel',
            'department.parent',
            'managedUser.userLevel',
            'creator'
        ]);

        // ✅ FULLY FIXED: All null checks for dates and relationships
        return Inertia::render('Admin/ManagerRoles/Show', [
            'role' => [
                'id' => $userDepartmentRole->id,
                // ✅ Safe manager data
                'manager' => $userDepartmentRole->manager ? [
                    'id' => $userDepartmentRole->manager->id,
                    'name' => $userDepartmentRole->manager->name,
                    'email' => $userDepartmentRole->manager->email,
                    'level' => $userDepartmentRole->manager->userLevel?->name,
                ] : [
                    'id' => null,
                    'name' => 'Deleted User',
                    'email' => 'N/A',
                    'level' => 'N/A',
                ],
                // ✅ Safe department data
                'department' => $userDepartmentRole->department ? [
                    'id' => $userDepartmentRole->department->id,
                    'name' => $userDepartmentRole->department->name,
                    'code' => $userDepartmentRole->department->department_code,
                    'parent' => $userDepartmentRole->department->parent?->name,
                ] : [
                    'id' => null,
                    'name' => 'Deleted Department',
                    'code' => 'N/A',
                    'parent' => null,
                ],
                // ✅ Safe managed user data
                'managed_user' => $userDepartmentRole->managedUser ? [
                    'id' => $userDepartmentRole->managedUser->id,
                    'name' => $userDepartmentRole->managedUser->name,
                    'email' => $userDepartmentRole->managedUser->email,
                    'level' => $userDepartmentRole->managedUser->userLevel?->name,
                ] : null,
                'role_type' => $userDepartmentRole->role_type,
                'role_display' => $userDepartmentRole->getRoleDisplayName(),
                'is_primary' => $userDepartmentRole->is_primary,
                'authority_level' => $userDepartmentRole->authority_level,
                'authority_display' => $userDepartmentRole->getAuthorityLevelDescription(),
                // ✅ FIXED: Safe date formatting
                'start_date' => $userDepartmentRole->start_date ? $userDepartmentRole->start_date->format('Y-m-d') : 'N/A',
                'end_date' => $userDepartmentRole->end_date?->format('Y-m-d'),
                'is_active' => $userDepartmentRole->isActive(),
                'notes' => $userDepartmentRole->notes,
                // ✅ Safe creator data
                'created_by' => $userDepartmentRole->creator?->name ?? 'Unknown Admin',
                // ✅ FIXED: Safe created_at formatting
                'created_at' => $userDepartmentRole->created_at ? $userDepartmentRole->created_at->format('Y-m-d H:i:s') : 'N/A',
            ]
        ]);
    }    /**
     * Show edit role assignment form
     */
    public function edit(UserDepartmentRole $userDepartmentRole): Response
    {
        $userDepartmentRole->load([
            'manager.userLevel',
            'department',
            'managedUser.userLevel',
        ]);

        // Get managers who can be assigned roles (L2, L3, L4)
        $managers = User::whereHas('userLevel', function ($query) {
            $query->where('hierarchy_level', '>=', 2);
        })
            ->where('status', 'active')
            ->with('userLevel', 'department')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'level' => $user->userLevel?->name,
                    'department' => $user->department?->name,
                ];
            });

        // Get all active departments
        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'department_code']);

        // Get employees who can be managed (L1 users)
        $employees = User::where('status', 'active')
            ->whereHas('userLevel', function ($query) {
                $query->where('code', 'L1');
            })
            ->with(['userLevel', 'department'])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'level' => $user->userLevel?->name,
                    'levelcode' => $user->userLevel?->code,
                    'department' => $user->department?->name,
                    'department_id' => $user->department_id,
                    'employee_code' => $user->employee_code,
                ];
            });

        return Inertia::render('Admin/ManagerRoles/Edit', [
            'role' => [
                'id' => $userDepartmentRole->id,
                'user_id' => $userDepartmentRole->user_id,
                'department_id' => $userDepartmentRole->department_id,
                'role_type' => $userDepartmentRole->role_type,
                'manages_user_id' => $userDepartmentRole->manages_user_id,
                'is_primary' => $userDepartmentRole->is_primary,
                'authority_level' => $userDepartmentRole->authority_level,
                // ✅ FIXED: Safe date formatting with null checks
                'start_date' => $userDepartmentRole->start_date ? $userDepartmentRole->start_date->format('Y-m-d') : now()->format('Y-m-d'),
                'end_date' => $userDepartmentRole->end_date?->format('Y-m-d'),
                'notes' => $userDepartmentRole->notes,
                // Current assignments for display
                'manager' => $userDepartmentRole->manager ? [
                    'id' => $userDepartmentRole->manager->id,
                    'name' => $userDepartmentRole->manager->name,
                    'email' => $userDepartmentRole->manager->email,
                    'level' => $userDepartmentRole->manager->userLevel?->name,
                ] : null,
                'department' => $userDepartmentRole->department ? [
                    'id' => $userDepartmentRole->department->id,
                    'name' => $userDepartmentRole->department->name,
                    'code' => $userDepartmentRole->department->department_code,
                ] : null,
                'managed_user' => $userDepartmentRole->managedUser ? [
                    'id' => $userDepartmentRole->managedUser->id,
                    'name' => $userDepartmentRole->managedUser->name,
                    'email' => $userDepartmentRole->managedUser->email,
                    'level' => $userDepartmentRole->managedUser->userLevel?->name,
                ] : null,
            ],
            'managers' => $managers,
            'departments' => $departments,
            'employees' => $employees,
            'roleTypes' => [
                'direct_manager' => 'Direct Manager',
                'project_manager' => 'Project Manager',
                'director' => 'Director',
                'senior_manager' => 'Senior Manager',
                'supervisor ' => 'Supervisor ',
            ]
        ]);
    }
    /**
     * Update role assignment
     */
    public function update(Request $request, UserDepartmentRole $userDepartmentRole)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'department_id' => 'required|exists:departments,id',
            'role_type' => 'required|in:direct_manager,project_manager,director,senior_manager,supervisor',
            'manages_user_id' => 'nullable|exists:users,id',
            'is_primary' => 'boolean',
            'authority_level' => 'required|integer|min:1|max:3',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        // Check for conflicts (excluding current role)
        $existingRole = UserDepartmentRole::where('user_id', $validated['user_id'])
            ->where('department_id', $validated['department_id'])
            ->where('role_type', $validated['role_type'])
            ->where('id', '!=', $userDepartmentRole->id)
            ->active()
            ->first();

        if ($existingRole) {
            return back()->withErrors([
                'role_conflict' => 'User already has this role in the department.'
            ]);
        }

        // Clear manages_user_id if not managing specific user
        if (!$validated['manages_user_id']) {
            $validated['manages_user_id'] = null;
        }

        // ✅ FIXED: Ensure start_date is not null
        if (!$validated['start_date']) {
            $validated['start_date'] = now()->format('Y-m-d');
        }

        try {
            $userDepartmentRole->update($validated);

            return redirect()
                ->route('admin.manager-roles.show', $userDepartmentRole)
                ->with('success', 'Manager role updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to update manager role: ' . $e->getMessage());

            return back()->withErrors([
                'update_error' => 'Failed to update manager role. Please try again.'
            ]);
        }
    }
    /**
     * Terminate role assignment
     */
    public function destroy(UserDepartmentRole $userDepartmentRole)
    {
        $userDepartmentRole->terminate();

        return redirect()
            ->route('admin.manager-roles.index')
            ->with('success', 'Manager role terminated successfully.');
    }

    /**
     * Get employees for a specific department
     */
    public function getDepartmentEmployees(Department $department)
    {
        $employees = $department->users()
            ->where('status', 'active')
            ->with('userLevel')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'level' => $user->userLevel?->name,
                    'employee_code' => $user->employee_code,
                ];
            });

        return response()->json($employees);
    }

    /**
     * Matrix view of all manager-employee relationships
     */
    public function matrix(): Response
    {
        $departments = Department::with([
            'activeManagerRoles.manager.userLevel',
            'activeManagerRoles.managedUser.userLevel'
        ])
            ->where('is_active', true)
            ->get()
            ->map(function ($department) {
                return [
                    'id' => $department->id,
                    'name' => $department->name,
                    'roles' => $department->activeManagerRoles->map(function ($role) {
                        return [
                            'manager' => [
                                'name' => $role->manager->name,
                                'level' => $role->manager->userLevel?->name,
                            ],
                            'managed_user' => $role->managedUser ? [
                                'name' => $role->managedUser->name,
                                'level' => $role->managedUser->userLevel?->name,
                            ] : null,
                            'role_type' => $role->getRoleDisplayName(),
                            'is_primary' => $role->is_primary,
                        ];
                    })
                ];
            });

        return Inertia::render('Admin/ManagerRoles/Matrix', [
            'departments' => $departments
        ]);
    }

    public function getEmployees(Department $department)
    {
//        dd();
        Log::info('Getting employees for department: ' . $department->name . ' (ID: ' . $department->id . ')');

        // Get all L1 users in this department
        $employees = User::where('department_id', $department->id)
            ->whereHas('userLevel', function ($query) {
                $query->where('code', 'L1'); // Only L1 employees
            })
            ->where('status', 'active')
            ->with('userLevel')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'level' => $user->userLevel?->name,
                    'level_code' => $user->userLevel?->code,
                    'employee_code' => $user->employee_code,
                ];
            });

        Log::info('Found ' . $employees->count() . ' L1 employees in ' . $department->name);

        // ✅ Add debugging info
        if ($employees->isEmpty()) {
            Log::warning('No L1 employees found in department: ' . $department->name);

            // Check what users ARE in this department
            $allUsers = User::where('department_id', $department->id)->with('userLevel')->get();
            Log::info('All users in department: ' . $allUsers->pluck('name', 'userLevel.code')->toJson());
        }

        return response()->json($employees);
    }

}
