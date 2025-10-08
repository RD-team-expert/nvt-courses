<?php
// app/Http/Controllers/Admin/UserLevelController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserLevel;
use App\Models\UserLevelTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class UserLevelController extends Controller
{
    /**
     * Display user levels
     */
    public function index(): Response
    {
        $userLevels = UserLevel::withCount('users') // Users stay in levels
        ->with(['tiers' => function($query) {
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
                    'description' => $level->description,
                    'can_manage_levels' => $level->can_manage_levels,
                    'users_count' => $level->users_count, // Users in the level
                    'is_management_level' => $level->isManagementLevel(),
                    'tiers' => $level->tiers->map(function($tier) {
                        return [
                            'id' => $tier->id,
                            'tier_name' => $tier->tier_name,
                            'tier_order' => $tier->tier_order,
                            'description' => $tier->description,
                            // No users_count for tiers - they're just for evaluations
                        ];
                    }),
                ];
            });

        $totalTiers = UserLevelTier::count();

        return Inertia::render('Admin/UserLevels/Index', [
            'userLevels' => $userLevels,
            'stats' => [
                'total_levels' => UserLevel::count(),
                'total_tiers' => $totalTiers,
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
            'hierarchy_level' => 'required|integer|min:0|max:10|unique:user_levels',
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
        try {
            // Check if level has users assigned
            if ($userLevel->users()->count() > 0) {
                return back()->withErrors([
                    'delete' => 'Cannot delete user level with assigned users. Please reassign users first.'
                ]);
            }

            $userLevel->delete();

            return redirect()
                ->route('admin.user-levels.index')
                ->with('success', 'User level deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete user level: ' . $e->getMessage());

            return back()->withErrors([
                'delete' => 'Failed to delete user level. Please try again.'
            ]);
        }
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
    public function removeUserFromLevel(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $user = User::findOrFail($validated['user_id']);
            $previousLevel = $user->userLevel?->name ?? 'No Level';

            // ✅ FIXED: Use direct database update instead of model update
            $affected = DB::table('users')
                ->where('id', $validated['user_id'])
                ->update([
                    'user_level_id' => null,
                    'updated_at' => now()
                ]);

            // ✅ Get fresh user data to verify the update
            $updatedUser = User::find($validated['user_id']);

            Log::info('Remove user from level - After DB update', [
                'user_id' => $validated['user_id'],
                'affected_rows' => $affected,
                'previous_level' => $previousLevel,
                'new_level_id' => $updatedUser->user_level_id,
                'verification' => $updatedUser->user_level_id === null ? 'SUCCESS' : 'FAILED'
            ]);

            if ($affected === 0) {
                throw new \Exception('No rows were affected by the update');
            }

            if ($updatedUser->user_level_id !== null) {
                throw new \Exception('User level was not properly removed');
            }

            return redirect()
                ->back()
                ->with('success', "User {$user->name} removed from {$previousLevel} level successfully.");

        } catch (\Exception $e) {
            Log::error('Failed to remove user from level: ' . $e->getMessage(), [
                'user_id' => $validated['user_id'] ?? null,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors([
                'remove_user' => 'Failed to remove user from level. Please try again.'
            ]);
        }
    }}
