<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Department;
use App\Models\User;
use App\Models\UserLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    // Remove the constructor with middleware calls
    // The middleware is already applied at the route level in web.php


    public function assignment()
    {
//        dd();
        $users = User::with('userLevel', 'department')
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_code' => $user->employee_code,
                    'level' => $user->userLevel?->name,
                    'level_code' => $user->userLevel?->code,
                    'department' => $user->department?->name,
                    'department_id' => $user->department_id,
                    'status' => $user->status,
                ];
            });

        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'department_code']);

        $userLevels = UserLevel::orderBy('hierarchy_level')
            ->get(['id', 'code', 'name', 'hierarchy_level']);

        return Inertia::render('Admin/Users/Assignment', [
            'users' => $users,
            'departments' => $departments,
            'userLevels' => $userLevels,
            'stats' => [
                'total_users' => $users->count(),
                'with_departments' => $users->where('department_id', '!=', null)->count(),
                'with_levels' => $users->where('userLevel', '!=', null)->count(),
                'without_assignments' => $users->where('department_id', null)->where('userLevel', null)->count(),
            ]
        ]);
    }

    public function index()
    {
        // List all users
        // You can paginate if you have many
        $users = User::orderBy('created_at', 'desc')->paginate('10');

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    public function create()
    {
        // Return a view for creating a new user
        return Inertia::render('Admin/Users/Create');
    }

    public function store(StoreUserRequest $request)
    {
        // Validate & create user
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully!');
    }

    public function show(User $user)
    {
        // Optional method if you want a detail page
        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user)
    {
        // Return a view to edit an existing user
        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            // Don't overwrite existing password if not provided
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        // Delete user (careful if you want to preserve data or soft-delete)
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully!');
    }

    public function getAvailableUsers(Request $request)
    {
        $query = User::where('status', 'active');

        // Filter users without levels if requested
        if ($request->boolean('without_level')) {
            $query->whereNull('user_level_id');
        }

        // Filter by specific criteria if needed
        if ($request->has('exclude_level')) {
            $query->where('user_level_id', '!=', $request->exclude_level);
        }

        $users = $query->with('userLevel', 'department')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'level' => $user->userLevel?->name,
                    'department' => $user->department?->name,
                    'employee_code' => $user->employee_code,
                ];
            });

        return response()->json(['users' => $users]);
    }


    /**
     * Bulk assign users to departments/levels
     */
    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'user_level_id' => 'nullable|exists:user_levels,id',
        ]);

        if (!$validated['department_id'] && !$validated['user_level_id']) {
            return back()->withErrors(['assignment' => 'Please select either a department or user level to assign.']);
        }

        $updateData = [];
        if ($validated['department_id']) {
            $updateData['department_id'] = $validated['department_id'];
        }
        if ($validated['user_level_id']) {
            $updateData['user_level_id'] = $validated['user_level_id'];
        }

        User::whereIn('id', $validated['user_ids'])->update($updateData);

        $assignmentType = [];
        if ($validated['department_id']) $assignmentType[] = 'department';
        if ($validated['user_level_id']) $assignmentType[] = 'level';

        return redirect()->back()->with('success',
            count($validated['user_ids']) . ' users assigned to ' . implode(' and ', $assignmentType) . ' successfully.'
        );
    }

    /**
     * Assign level to specific user
     */
    public function assignLevel(Request $request, User $user)
    {
        $validated = $request->validate([
            'user_level_id' => 'nullable|exists:user_levels,id',
        ]);

        $user->update(['user_level_id' => $validated['user_level_id']]);

        return redirect()->back()->with('success', 'User level updated successfully.');
    }

    /**
     * Assign department to specific user
     */
    public function assignDepartment(Request $request, User $user)
    {
        $validated = $request->validate([
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $user->update(['department_id' => $validated['department_id']]);

        return redirect()->back()->with('success', 'User department updated successfully.');
    }

    public function organizationalInfo(User $user)
    {
        $user->load([
            'userLevel',
            'department.parent',
            'managerRoles.department',
            'managedRoles.manager',
            'managedRoles.department'
        ]);

        // Get users this person manages
        $directReports = User::whereHas('managedRoles', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with('userLevel', 'department')->get();

        // Get who manages this user
        $managers = User::whereHas('managerRoles', function($query) use ($user) {
            $query->where('manages_user_id', $user->id);
        })->with('userLevel', 'department')->get();

        return Inertia::render('Admin/Users/Organizational', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_code' => $user->employee_code,
                'status' => $user->status,
                'created_at' => $user->created_at?->format('Y-m-d'),
                'level' => $user->userLevel ? [
                    'id' => $user->userLevel->id,
                    'code' => $user->userLevel->code,
                    'name' => $user->userLevel->name,
                    'hierarchy_level' => $user->userLevel->hierarchy_level,
                ] : null,
                'department' => $user->department ? [
                    'id' => $user->department->id,
                    'name' => $user->department->name,
                    'code' => $user->department->department_code,
                    'parent' => $user->department->parent?->name,
                ] : null,
            ],
            'managerRoles' => $user->managerRoles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'role_type' => $role->role_type,
                    'role_display' => $role->getRoleDisplayName(),
                    'department' => $role->department?->name,
                    'is_primary' => $role->is_primary,
                    'authority_level' => $role->authority_level,
                    'start_date' => $role->start_date?->format('Y-m-d'),
                    'is_active' => $role->isActive(),
                ];
            }),
            'directReports' => $directReports->map(function ($report) {
                return [
                    'id' => $report->id,
                    'name' => $report->name,
                    'email' => $report->email,
                    'level' => $report->userLevel?->name,
                    'department' => $report->department?->name,
                ];
            }),
            'managers' => $managers->map(function ($manager) {
                return [
                    'id' => $manager->id,
                    'name' => $manager->name,
                    'email' => $manager->email,
                    'level' => $manager->userLevel?->name,
                    'department' => $manager->department?->name,
                ];
            }),
        ]);
    }

    /**
     * Show user's reporting chain
     */
    public function reportingChain(User $user)
    {
        // Build reporting chain going up
        $reportingChain = [];
        $currentUser = $user;
        $maxLevels = 10; // Prevent infinite loops
        $level = 0;

        while ($level < $maxLevels) {
            // Find who manages current user
            $manager = User::whereHas('managerRoles', function($query) use ($currentUser) {
                $query->where('manages_user_id', $currentUser->id)
                    ->where('is_primary', true);
            })->with('userLevel', 'department')->first();

            if (!$manager) {
                break;
            }

            $reportingChain[] = [
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
                'level' => $manager->userLevel?->name,
                'level_hierarchy' => $manager->userLevel?->hierarchy_level,
                'department' => $manager->department?->name,
                'position_level' => $level + 1,
            ];

            $currentUser = $manager;
            $level++;
        }

        return Inertia::render('Admin/Users/ReportingChain', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'level' => $user->userLevel?->name,
                'department' => $user->department?->name,
            ],
            'reportingChain' => $reportingChain,
            'chainLength' => count($reportingChain),
        ]);
    }

    /**
     * Show user's direct reports
     */
    public function directReports(User $user)
    {
        // Get all users this person directly manages
        $directReports = User::whereHas('managedRoles', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with([
            'userLevel',
            'department',
            'managedRoles' => function($query) use ($user) {
                $query->where('user_id', $user->id);
            }
        ])->get();

        // Also get department-wide roles
        $departmentRoles = $user->managerRoles()
            ->whereNull('manages_user_id')
            ->with('department')
            ->get();

        return Inertia::render('Admin/Users/DirectReports', [
            'manager' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'level' => $user->userLevel?->name,
                'department' => $user->department?->name,
            ],
            'directReports' => $directReports->map(function ($report) use ($user) {
                $role = $report->managedRoles->where('user_id', $user->id)->first();
                return [
                    'id' => $report->id,
                    'name' => $report->name,
                    'email' => $report->email,
                    'employee_code' => $report->employee_code,
                    'level' => $report->userLevel?->name,
                    'level_hierarchy' => $report->userLevel?->hierarchy_level,
                    'department' => $report->department?->name,
                    'role_type' => $role?->role_type,
                    'role_display' => $role?->getRoleDisplayName(),
                    'start_date' => $role?->start_date?->format('Y-m-d'),
                    'is_primary' => $role?->is_primary,
                    'status' => $report->status,
                ];
            }),
            'departmentRoles' => $departmentRoles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'role_type' => $role->role_type,
                    'role_display' => $role->getRoleDisplayName(),
                    'department' => $role->department?->name,
                    'is_primary' => $role->is_primary,
                    'authority_level' => $role->authority_level,
                ];
            }),
            'stats' => [
                'total_reports' => $directReports->count(),
                'active_reports' => $directReports->where('status', 'active')->count(),
                'department_roles' => $departmentRoles->count(),
            ],
        ]);
    }
}
