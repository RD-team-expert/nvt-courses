<?php
// app/Http/Controllers/Admin/DepartmentController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    /**
     * Display departments with hierarchy
     */
    public function index(): Response
    {
        $departments = Department::with(['parent', 'children', 'activeManagerRoles.manager'])
            ->whereNull('parent_id') // Get top-level departments
            ->get()
            ->map(function ($department) {
                return $this->formatDepartmentForFrontend($department);
            });

        return Inertia::render('Admin/Departments/Index', [
            'departments' => $departments,
            'stats' => [
                'total_departments' => Department::count(),
                'active_departments' => Department::where('is_active', true)->count(),
                'departments_with_managers' => Department::whereHas('activeManagerRoles')->count(),
            ]
        ]);
    }

    /**
     * Show create department form
     */
    public function create(): Response
    {
        $parentDepartments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Departments/Create', [
            'parentDepartments' => $parentDepartments
        ]);
    }

    /**
     * Store new department
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:departments,id',
            'department_code' => 'required|string|max:20|unique:departments',
        ]);

        $department = Department::create($validated);

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    /**
     * Show specific department
     */
    public function show(Department $department): Response
    {
        $department->load([
            'parent',
            'children.activeManagerRoles.manager',
            'activeManagerRoles.manager.userLevel',
            'users.userLevel'
        ]);

        return Inertia::render('Admin/Departments/Show', [
            'department' => $this->formatDepartmentForFrontend($department),
            'managerRoles' => $department->activeManagerRoles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'role_type' => $role->role_type,
                    'role_display' => $role->getRoleDisplayName(),
                    'is_primary' => $role->is_primary,
                    'manager' => [
                        'id' => $role->manager->id,
                        'name' => $role->manager->name,
                        'email' => $role->manager->email,
                        'level' => $role->manager->userLevel?->name,
                    ],
                    'start_date' => $role->start_date->format('Y-m-d'),
                ];
            }),
            'users' => $department->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'level' => $user->userLevel?->name,
                    'employee_code' => $user->employee_code,
                ];
            })
        ]);
    }

    /**
     * Show edit department form
     */
    public function edit(Department $department): Response
    {
        $parentDepartments = Department::where('is_active', true)
            ->where('id', '!=', $department->id) // Exclude self
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Departments/Edit', [
            'department' => $department,
            'parentDepartments' => $parentDepartments
        ]);
    }

    /**
     * Update department
     */
    public function update(Request $request, Department $department)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:departments,id',
            'department_code' => 'required|string|max:20|unique:departments,department_code,' . $department->id,
            'is_active' => 'boolean',
        ]);

        // Prevent circular references
        if ($validated['parent_id'] && $department->isChildOf(Department::find($validated['parent_id']))) {
            return back()->withErrors(['parent_id' => 'Cannot set a child department as parent.']);
        }

        $department->update($validated);

        return redirect()
            ->route('admin.departments.show', $department)
            ->with('success', 'Department updated successfully.');
    }

    /**
     * Delete department
     */
    public function destroy(Department $department)
    {
        // Check if department has children
        if ($department->children()->count() > 0) {
            return back()->withErrors(['delete' => 'Cannot delete department with sub-departments.']);
        }

        // Check if department has users
        if ($department->users()->count() > 0) {
            return back()->withErrors(['delete' => 'Cannot delete department with assigned users.']);
        }

        $department->delete();

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department deleted successfully.');
    }

    /**
     * Get department managers for assignment
     */
    public function getManagerCandidates(Department $department)
    {
        // Get users who can be managers (L2+ levels)
        $managerCandidates = User::whereHas('userLevel', function ($query) {
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
                    'current_roles' => $user->activeManagerRoles->pluck('role_type')->toArray()
                ];
            });

        return response()->json($managerCandidates);
    }

    /**
     * Format department data for frontend
     */
    private function formatDepartmentForFrontend(Department $department): array
    {
        return [
            'id' => $department->id,
            'name' => $department->name,
            'description' => $department->description,
            'department_code' => $department->department_code,
            'is_active' => $department->is_active,
            'parent_id' => $department->parent_id,
            'parent_name' => $department->parent?->name,
            'children_count' => $department->children->count(),
            'users_count' => $department->users->count(),
            'managers_count' => $department->activeManagerRoles->count(),
            'hierarchy_path' => $department->getHierarchyPath(),
            'children' => $department->children->map(function ($child) {
                return $this->formatDepartmentForFrontend($child);
            }),
            'managers' => $department->activeManagerRoles->map(function ($role) {
                return [
                    'name' => $role->manager->name,
                    'role_type' => $role->getRoleDisplayName(),
                    'is_primary' => $role->is_primary
                ];
            })
        ];
    }
    public function getEmployees(Department $department)
    {
        dd();
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
