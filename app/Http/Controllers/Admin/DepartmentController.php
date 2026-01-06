<?php
// app/Http/Controllers/Admin/DepartmentController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\Models\UserDepartmentRole;
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
        // ✅ FIXED N+1: Added users and recursive children loading with counts
        // Also load parent chain for hierarchy path calculation
        $departments = Department::with([
                'parent.parent.parent', // Load up to 3 levels of parent hierarchy
                'children.parent', // Children need their parent (current dept) loaded
                'children.users', 
                'children.activeManagerRoles.manager',
                'users',
                'activeManagerRoles.manager'
            ])
            ->withCount(['users', 'children'])
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
        // ✅ FIXED N+1: Added parent chain loading for hierarchy path
        $department->load([
            'parent.parent.parent', // Load parent chain for hierarchy path
            'children.parent', // Children need parent for hierarchy path
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

        // Prevent circular references - safely check if parent_id exists and is not null
        $parentId = $validated['parent_id'] ?? null;
        if ($parentId) {
            // Load parent chain on department to check if potential parent is already a descendant
            $department->load('parent.parent.parent.parent');
            $potentialParent = Department::find($parentId);
            
            // Check if the potential parent is actually a descendant of this department
            // This would create a circular reference
            if ($potentialParent) {
                // Load the potential parent's parent chain
                $potentialParent->load('parent.parent.parent.parent');
                
                // Check if potential parent is a child of the current department
                if ($potentialParent->isChildOf($department)) {
                    return back()->withErrors(['parent_id' => 'Cannot set a child department as parent.']);
                }
            }
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
        // ✅ FIXED N+1: Added activeManagerRoles to eager loading
        // Get users who can be managers (L2+ levels)
        $managerCandidates = User::whereHas('userLevel', function ($query) {
            $query->where('hierarchy_level', '>=', 2);
        })
            ->where('status', 'active')
            ->with(['userLevel', 'department', 'activeManagerRoles'])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'level' => $user->userLevel?->name,
                    'department' => $user->department?->name,
                    'current_roles' => $user->relationLoaded('activeManagerRoles') 
                        ? $user->activeManagerRoles->pluck('role_type')->toArray() 
                        : []
                ];
            });

        return response()->json($managerCandidates);
    }

    /**
     * Format department data for frontend
     */
    private function formatDepartmentForFrontend(Department $department): array
    {
        // ✅ FIXED N+1: Use loaded relationships or counts safely
        $usersCount = $department->users_count ?? ($department->relationLoaded('users') ? $department->users->count() : 0);
        $childrenCount = $department->children_count ?? ($department->relationLoaded('children') ? $department->children->count() : 0);
        $managersCount = $department->relationLoaded('activeManagerRoles') ? $department->activeManagerRoles->count() : 0;
        
        return [
            'id' => $department->id,
            'name' => $department->name,
            'description' => $department->description,
            'department_code' => $department->department_code,
            'is_active' => $department->is_active,
            'parent_id' => $department->parent_id,
            'parent_name' => $department->relationLoaded('parent') ? $department->parent?->name : null,
            'children_count' => $childrenCount,
            'users_count' => $usersCount,
            'managers_count' => $managersCount,
            'hierarchy_path' => $department->getHierarchyPath(),
            'children' => $department->relationLoaded('children') 
                ? $department->children->map(function ($child) {
                    return $this->formatDepartmentForFrontend($child);
                })
                : [],
            'managers' => $department->relationLoaded('activeManagerRoles')
                ? $department->activeManagerRoles->map(function ($role) {
                    return [
                        'name' => $role->relationLoaded('manager') ? $role->manager->name : 'Unknown',
                        'role_type' => $role->getRoleDisplayName(),
                        'is_primary' => $role->is_primary
                    ];
                })
                : []
        ];
    }
    public function getEmployees(Department $department)
    {

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


        // ✅ Add debugging info
        if ($employees->isEmpty()) {

            // Check what users ARE in this department
            $allUsers = User::where('department_id', $department->id)->with('userLevel')->get();
        }

        return response()->json($employees);
    }

    public function assignManager(Request $request, Department $department)
    {
        $validated = $request;

        try {
            // Check if user already has a role in this department
            $existingRole = UserDepartmentRole::where('user_id', $validated['user_id'])
                ->where('department_id', $department->id)
                ->where('role_type', $validated['role_type'])
                ->whereNull('end_date')
                ->first();

            if ($existingRole) {
                return back()->withErrors(['error' => 'User already has this role in this department.']);
            }

            // If this is a primary role, remove existing primary roles
            if ($validated['is_primary'] ?? false) {
                UserDepartmentRole::where('department_id', $department->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            // Create the manager role with created_by field
            UserDepartmentRole::create([
                'user_id' => $validated['user_id'],
                'department_id' => $department->id,
                'role_type' => $validated['role_type'],
                'is_primary' => $validated['is_primary'] ?? false,
                'start_date' => $validated['start_date'] ?? now(),
                'end_date' => $validated['end_date']?? null,
                'created_by' => auth()->id(), // Add the authenticated user's ID
            ]);

            return redirect()
                ->route('admin.departments.show', $department)
                ->with('success', 'Manager assigned successfully.');

        } catch (\Exception $e) {\Log::error('Failed to assign manager: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to assign manager. Please try again.']);
        }
    }
    public function removeManager(Department $department, UserDepartmentRole $role)
    {
        try {
            // Verify that the role belongs to the department
            if ($role->department_id !== $department->id) {
                return back()->withErrors(['error' => 'Invalid role for this department.']);
            }

            // Terminate the role by setting end_date to now
            $role->terminate(); // This uses the terminate() method from your model

            return redirect()
                ->route('admin.departments.show', $department)
                ->with('success', 'Manager role removed successfully.');

        } catch (\Exception $e) {


            return back()->withErrors(['error' => 'Failed to remove manager role. Please try again.']);
        }
    }
}
