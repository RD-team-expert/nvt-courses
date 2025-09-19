<?php
// app/Http/Controllers/Admin/UserLevelController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLevel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserLevelController extends Controller
{
    /**
     * Display user levels
     */
    public function index(): Response
    {
        $userLevels = UserLevel::withCount('users')
            ->orderBy('hierarchy_level')
            ->get()
            ->map(function ($level) {
                return [
                    'id' => $level->id,
                    'code' => $level->code,
                    'name' => $level->name,
                    'hierarchy_level' => $level->hierarchy_level,
                    'description' => $level->description,
                    'can_manage_levels' => $level->can_manage_levels,
                    'users_count' => $level->users_count,
                    'is_management_level' => $level->isManagementLevel(),
                ];
            });

        return Inertia::render('Admin/UserLevels/Index', [
            'userLevels' => $userLevels,
            'stats' => [
                'total_levels' => UserLevel::count(),
                'management_levels' => UserLevel::whereNotNull('can_manage_levels')->count(),
                'total_users_assigned' => UserLevel::withCount('users')->get()->sum('users_count'),
            ]
        ]);
    }

    /**
     * Show create user level form
     */
    public function create(): Response
    {
        $existingLevels = UserLevel::orderBy('hierarchy_level')->get(['code', 'name']);

        return Inertia::render('Admin/UserLevels/Create', [
            'existingLevels' => $existingLevels
        ]);
    }

    /**
     * Store new user level
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:user_levels',
            'name' => 'required|string|max:100',
            'hierarchy_level' => 'required|integer|min:1|max:10|unique:user_levels',
            'description' => 'nullable|string',
            'can_manage_levels' => 'nullable|array',
            'can_manage_levels.*' => 'exists:user_levels,code',
        ]);

        UserLevel::create($validated);

        return redirect()
            ->route('admin.user-levels.index')
            ->with('success', 'User level created successfully.');
    }

    /**
     * Show specific user level
     */
    public function show(UserLevel $userLevel): Response
    {
        $userLevel->load('users.department');

        // ✅ Add available users to the response
        $availableUsers = User::where('status', 'active')
            ->whereNull('user_level_id')  // Users without levels
            ->with('department')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'department' => $user->department?->name,
                    'employee_code' => $user->employee_code,
                ];
            });

        return Inertia::render('Admin/UserLevels/Show', [
            'userLevel' => [
                'id' => $userLevel->id,
                'code' => $userLevel->code,
                'name' => $userLevel->name,
                'hierarchy_level' => $userLevel->hierarchy_level,
                'description' => $userLevel->description,
                'can_manage_levels' => $userLevel->can_manage_levels,
                'manageable_levels' => $userLevel->getManageableLevels(),
                'is_management_level' => $userLevel->isManagementLevel(),
            ],
            'users' => $userLevel->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'department' => $user->department?->name,
                    'employee_code' => $user->employee_code,
                    'status' => $user->status,
                ];
            }),
            // ✅ Add available users
            'availableUsers' => $availableUsers
        ]);
    }
    /**
     * Show edit user level form
     */
    public function edit(UserLevel $userLevel): Response
    {
        $existingLevels = UserLevel::where('id', '!=', $userLevel->id)
            ->orderBy('hierarchy_level')
            ->get(['code', 'name']);

        return Inertia::render('Admin/UserLevels/Edit', [
            'userLevel' => $userLevel,
            'existingLevels' => $existingLevels
        ]);
    }

    /**
     * Update user level
     */
    public function update(Request $request, UserLevel $userLevel)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10|unique:user_levels,code,' . $userLevel->id,
            'name' => 'required|string|max:100',
            'hierarchy_level' => 'required|integer|min:1|max:10|unique:user_levels,hierarchy_level,' . $userLevel->id,
            'description' => 'nullable|string',
            'can_manage_levels' => 'nullable|array',
            'can_manage_levels.*' => 'exists:user_levels,code',
        ]);

        $userLevel->update($validated);

        return redirect()
            ->route('admin.user-levels.show', $userLevel)
            ->with('success', 'User level updated successfully.');
    }

    /**
     * Delete user level
     */
    public function destroy(UserLevel $userLevel)
    {
        // Check if level has users assigned
        if ($userLevel->users()->count() > 0) {
            return back()->withErrors(['delete' => 'Cannot delete user level with assigned users.']);
        }

        $userLevel->delete();

        return redirect()
            ->route('admin.user-levels.index')
            ->with('success', 'User level deleted successfully.');
    }

    public function bulkAssign(Request $request)
    {

        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'user_level_id' => 'required|exists:user_levels,id',
        ]);

        User::whereIn('id', $validated['user_ids'])
            ->update(['user_level_id' => $validated['user_level_id']]);

        return redirect()->back()->with('success', 'Users assigned to level successfully.');
    }

}
