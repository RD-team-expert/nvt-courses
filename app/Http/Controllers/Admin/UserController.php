<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Department;
use App\Models\User;
use App\Models\UserLevel;
use App\Models\UserLevelTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function assignment()
    {
        $users = User::with(['userLevel', 'userLevelTier', 'department'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_code' => $user->employee_code,
                    // Level info
                    'level' => $user->userLevel?->name,
                    'level_code' => $user->userLevel?->code,
                    'user_level_id' => $user->user_level_id,
                    // Tier info - NEW
                    'tier' => $user->userLevelTier?->tier_name,
                    'tier_order' => $user->userLevelTier?->tier_order,
                    'user_level_tier_id' => $user->user_level_tier_id,
                    // Department info
                    'department' => $user->department?->name,
                    'department_id' => $user->department_id,
                    'status' => $user->status,
                ];
            });

        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'department_code']);

        // Load user levels with their tiers - ENHANCED
        $userLevels = UserLevel::with(['tiers' => function($query) {
            $query->orderBy('tier_order');
        }])
            ->orderBy('hierarchy_level')
            ->get()
            ->map(function ($level) {
                return [
                    'id' => $level->id,
                    'code' => $level->code,
                    'name' => $level->name,
                    'hierarchy_level' => $level->hierarchy_level,
                    'tiers' => $level->tiers->map(function ($tier) {
                        return [
                            'id' => $tier->id,
                            'tier_name' => $tier->tier_name,
                            'tier_order' => $tier->tier_order,
                            'description' => $tier->description,
                        ];
                    }),
                ];
            });

        return Inertia::render('Admin/Users/Assignment', [
            'users' => $users,
            'departments' => $departments,
            'userLevels' => $userLevels, // Enhanced with tiers
            'stats' => [
                'total_users' => $users->count(),
                'with_departments' => $users->where('department_id', '!=', null)->count(),
                'with_levels' => $users->where('user_level_id', '!=', null)->count(),
                'with_tiers' => $users->where('user_level_tier_id', '!=', null)->count(), // NEW
                'without_assignments' => $users->where('department_id', null)->where('user_level_id', null)->count(),
            ]
        ]);
    }

    public function index(Request $request)
    {
        $search = $request->get('search', '');

        // Enhanced to include tier information and search functionality
        $users = User::with(['userLevel', 'userLevelTier', 'department'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('email', 'LIKE', '%' . $search . '%')
                        ->orWhere('role', 'LIKE', '%' . $search . '%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'search' => $search, // Pass the search term back to the frontend
        ]);
    }



    public function create()
    {
        // Load levels with tiers for user creation form
        $userLevels = UserLevel::with(['tiers' => function($query) {
            $query->orderBy('tier_order');
        }])
            ->orderBy('hierarchy_level')
            ->get();

        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Users/Create', [
            'userLevels' => $userLevels,
            'departments' => $departments,
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        // Enhanced validation for tier assignment
        $data = $request->validated();

        // Validate tier belongs to selected level if both are provided
        if (isset($data['user_level_id']) && isset($data['user_level_tier_id'])) {
            $tier = UserLevelTier::find($data['user_level_tier_id']);
            if (!$tier || $tier->user_level_id != $data['user_level_id']) {
                return back()->withErrors([
                    'user_level_tier_id' => 'Selected tier must belong to the selected level.'
                ]);
            }
        }

        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully!');
    }

    public function show(User $user)
    {
        // Enhanced to show tier information
        $user->load(['userLevel', 'userLevelTier', 'department']);

        return Inertia::render('Admin/Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_code' => $user->employee_code,
                'level' => $user->userLevel ? [
                    'id' => $user->userLevel->id,
                    'name' => $user->userLevel->name,
                    'code' => $user->userLevel->code,
                ] : null,
                'tier' => $user->userLevelTier ? [
                    'id' => $user->userLevelTier->id,
                    'tier_name' => $user->userLevelTier->tier_name,
                    'tier_order' => $user->userLevelTier->tier_order,
                ] : null,
                'department' => $user->department?->name,
                'status' => $user->status,
            ],
        ]);
    }

    public function edit(User $user)
    {
        $user->load(['userLevel', 'userLevelTier', 'department']);

        $userLevels = UserLevel::with(['tiers' => function($query) {
            $query->orderBy('tier_order');
        }])
            ->orderBy('hierarchy_level')
            ->get();

        $departments = Department::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'userLevels' => $userLevels,
            'departments' => $departments,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        // Validate tier belongs to selected level if both are provided
        if (isset($data['user_level_id']) && isset($data['user_level_tier_id'])) {
            $tier = UserLevelTier::find($data['user_level_tier_id']);
            if (!$tier || $tier->user_level_id != $data['user_level_id']) {
                return back()->withErrors([
                    'user_level_tier_id' => 'Selected tier must belong to the selected level.'
                ]);
            }
        }

        // If level is being cleared, also clear tier
        if (isset($data['user_level_id']) && !$data['user_level_id']) {
            $data['user_level_tier_id'] = null;
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully!');
    }

    public function getAvailableUsers(Request $request)
    {
        $query = User::where('status', 'active');

        if ($request->boolean('without_level')) {
            $query->whereNull('user_level_id');
        }

        if ($request->boolean('without_tier')) {
            $query->whereNull('user_level_tier_id');
        }

        if ($request->has('exclude_level')) {
            $query->where('user_level_id', '!=', $request->exclude_level);
        }

        $users = $query->with(['userLevel', 'userLevelTier', 'department'])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'level' => $user->userLevel?->name,
                    'tier' => $user->userLevelTier?->tier_name, // NEW
                    'department' => $user->department?->name,
                    'employee_code' => $user->employee_code,
                ];
            });

        return response()->json(['users' => $users]);
    }

    /**
     * Enhanced bulk assign users to departments/levels/tiers
     */
    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'user_level_id' => 'nullable|exists:user_levels,id',
            'user_level_tier_id' => 'nullable|exists:user_level_tiers,id', // NEW
        ]);

        // Validate tier belongs to level if both provided
        if ($validated['user_level_id'] && $validated['user_level_tier_id']) {
            $tier = UserLevelTier::find($validated['user_level_tier_id']);
            if (!$tier || $tier->user_level_id != $validated['user_level_id']) {
                return back()->withErrors([
                    'assignment' => 'Selected tier must belong to the selected level.'
                ]);
            }
        }

        if (!$validated['department_id'] && !$validated['user_level_id']) {
            return back()->withErrors([
                'assignment' => 'Please select either a department or user level to assign.'
            ]);
        }

        $updateData = [];
        if ($validated['department_id']) {
            $updateData['department_id'] = $validated['department_id'];
        }
        if ($validated['user_level_id']) {
            $updateData['user_level_id'] = $validated['user_level_id'];
        }
        if (isset($validated['user_level_tier_id'])) {
            $updateData['user_level_tier_id'] = $validated['user_level_tier_id'];
        }

        User::whereIn('id', $validated['user_ids'])->update($updateData);

        $assignmentType = [];
        if ($validated['department_id']) $assignmentType[] = 'department';
        if ($validated['user_level_id']) $assignmentType[] = 'level';
        if ($validated['user_level_tier_id']) $assignmentType[] = 'tier';

        return redirect()->back()->with('success',
            count($validated['user_ids']) . ' users assigned to ' . implode(' and ', $assignmentType) . ' successfully.'
        );
    }

    /**
     * Enhanced assign level and tier to specific user
     */
    public function assignLevel(Request $request, User $user)
    {
        $validated = $request->validate([
            'user_level_id' => 'nullable|exists:user_levels,id',
            'user_level_tier_id' => 'nullable|exists:user_level_tiers,id', // NEW
        ]);

        // Validate tier belongs to level if both provided
        if ($validated['user_level_id'] && $validated['user_level_tier_id']) {
            $tier = UserLevelTier::find($validated['user_level_tier_id']);
            if (!$tier || $tier->user_level_id != $validated['user_level_id']) {
                return back()->withErrors([
                    'user_level_tier_id' => 'Selected tier must belong to the selected level.'
                ]);
            }
        }

        // If clearing level, also clear tier
        if (!$validated['user_level_id']) {
            $validated['user_level_tier_id'] = null;
        }

        $user->update($validated);

        $message = 'User level';
        if ($validated['user_level_tier_id']) {
            $message .= ' and tier';
        }
        $message .= ' updated successfully.';

        return redirect()->back()->with('success', $message);
    }

    /**
     * NEW: Assign tier to specific user
     */
    public function assignTier(Request $request, User $user)
    {
        $validated = $request->validate([
            'user_level_tier_id' => 'nullable|exists:user_level_tiers,id',
        ]);

        if ($validated['user_level_tier_id']) {
            $tier = UserLevelTier::find($validated['user_level_tier_id']);

            // Ensure user has the correct level for this tier
            if (!$user->user_level_id || $tier->user_level_id != $user->user_level_id) {
                return back()->withErrors([
                    'user_level_tier_id' => 'User must be assigned to the correct level before assigning this tier.'
                ]);
            }
        }

        $user->update(['user_level_tier_id' => $validated['user_level_tier_id']]);

        return redirect()->back()->with('success', 'User tier updated successfully.');
    }

    /**
     * NEW: Get available tiers for a specific level
     */
    public function getTiersForLevel(Request $request)
    {
        $validated = $request->validate([
            'user_level_id' => 'required|exists:user_levels,id',
        ]);

        $tiers = UserLevelTier::where('user_level_id', $validated['user_level_id'])
            ->orderBy('tier_order')
            ->get(['id', 'tier_name', 'tier_order', 'description']);

        return response()->json(['tiers' => $tiers]);
    }

    public function assignDepartment(Request $request, User $user)
    {
        $validated = $request->validate([
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $user->update(['department_id' => $validated['department_id']]);

        return redirect()->back()->with('success', 'User department updated successfully.');
    }

    // ... rest of your existing methods remain the same ...
    // (organizationalInfo, reportingChain, directReports methods don't need changes)

    public function organizationalInfo(User $user)
    {
        $user->load([
            'userLevel',
            'userLevelTier', // NEW
            'department.parent',
            'managerRoles.department',
            'managedRoles.manager',
            'managedRoles.department'
        ]);

        // Get users this person manages - enhanced with tier info
        $directReports = User::whereHas('managedRoles', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with(['userLevel', 'userLevelTier', 'department'])->get();

        // Get who manages this user - enhanced with tier info
        $managers = User::whereHas('managerRoles', function($query) use ($user) {
            $query->where('manages_user_id', $user->id);
        })->with(['userLevel', 'userLevelTier', 'department'])->get();

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
                'tier' => $user->userLevelTier ? [ // NEW
                    'id' => $user->userLevelTier->id,
                    'tier_name' => $user->userLevelTier->tier_name,
                    'tier_order' => $user->userLevelTier->tier_order,
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
                    'tier' => $report->userLevelTier?->tier_name, // NEW
                    'department' => $report->department?->name,
                ];
            }),
            'managers' => $managers->map(function ($manager) {
                return [
                    'id' => $manager->id,
                    'name' => $manager->name,
                    'email' => $manager->email,
                    'level' => $manager->userLevel?->name,
                    'tier' => $manager->userLevelTier?->tier_name, // NEW
                    'department' => $manager->department?->name,
                ];
            }),
        ]);
    }

    public function reportingChain(User $user)
    {
        // Build reporting chain going up - enhanced with tier info
        $reportingChain = [];
        $currentUser = $user;
        $maxLevels = 10;
        $level = 0;

        while ($level < $maxLevels) {
            $manager = User::whereHas('managerRoles', function($query) use ($currentUser) {
                $query->where('manages_user_id', $currentUser->id)
                    ->where('is_primary', true);
            })->with(['userLevel', 'userLevelTier', 'department'])->first();

            if (!$manager) {
                break;
            }

            $reportingChain[] = [
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
                'level' => $manager->userLevel?->name,
                'level_hierarchy' => $manager->userLevel?->hierarchy_level,
                'tier' => $manager->userLevelTier?->tier_name, // NEW
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
                'tier' => $user->userLevelTier?->tier_name, // NEW
                'department' => $user->department?->name,
            ],
            'reportingChain' => $reportingChain,
            'chainLength' => count($reportingChain),
        ]);
    }

    public function directReports(User $user)
    {
        // Enhanced with tier information
        $directReports = User::whereHas('managedRoles', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with([
            'userLevel',
            'userLevelTier', // NEW
            'department',
            'managedRoles' => function($query) use ($user) {
                $query->where('user_id', $user->id);
            }
        ])->get();

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
                'tier' => $user->userLevelTier?->tier_name, // NEW
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
                    'tier' => $report->userLevelTier?->tier_name, // NEW
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
