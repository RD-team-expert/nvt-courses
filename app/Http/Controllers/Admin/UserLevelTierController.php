<?php
// app/Http/Controllers/Admin/UserLevelTierController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserLevel;
use App\Models\UserLevelTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UserLevelTierController extends Controller
{
    /**
     * Display tiers for a specific user level
     */
    public function index(UserLevel $userLevel): Response
    {
        $tiers = $userLevel->tiers()
            ->orderBy('tier_order')
            ->get()
            ->map(function ($tier) {
                return [
                    'id' => $tier->id,
                    'tier_name' => $tier->tier_name,
                    'tier_order' => $tier->tier_order,
                    'description' => $tier->description,
                    'display_name' => $tier->display_name,
                ];
            });

        return Inertia::render('Admin/UserLevels/Tiers/Index', [
            'userLevel' => [
                'id' => $userLevel->id,
                'code' => $userLevel->code,
                'name' => $userLevel->name,
                'hierarchy_level' => $userLevel->hierarchy_level,
            ],
            'tiers' => $tiers,
            'stats' => [
                'total_tiers' => $tiers->count(),
                'user_level_name' => $userLevel->name,
            ]
        ]);
    }

    /**
     * Show create tier form for specific user level
     */
    public function create(UserLevel $userLevel): Response
    {
        $existingTiers = $userLevel->tiers()
            ->orderBy('tier_order')
            ->get(['tier_name', 'tier_order']);

        return Inertia::render('Admin/UserLevels/Tiers/Create', [
            'userLevel' => [
                'id' => $userLevel->id,
                'code' => $userLevel->code,
                'name' => $userLevel->name,
            ],
            'existingTiers' => $existingTiers
        ]);
    }

    /**
     * Store new tier for specific user level
     */
    public function store(Request $request, UserLevel $userLevel)
    {
        $validated = $request->validate([
            'tier_name' => 'required|string|max:50',
            'tier_order' => 'required|integer|min:1|max:10',
            'description' => 'nullable|string|max:500',
        ]);

        // Check if tier_order already exists for this level
        $existingTier = $userLevel->tiers()
            ->where('tier_order', $validated['tier_order'])
            ->exists();

        if ($existingTier) {
            return back()->withErrors([
                'tier_order' => 'Tier order already exists for this level.'
            ]);
        }

        $userLevel->tiers()->create($validated);

        return redirect()
            ->route('admin.user-level-tiers.index', $userLevel)
            ->with('success', 'Tier created successfully for ' . $userLevel->name);
    }

    /**
     * Show specific tier
     */
    public function show(UserLevel $userLevel, UserLevelTier $tier): Response
    {
        // Ensure tier belongs to the user level
        if ($tier->user_level_id !== $userLevel->id) {
            abort(404);
        }

        return Inertia::render('Admin/UserLevels/Tiers/Show', [
            'userLevel' => [
                'id' => $userLevel->id,
                'code' => $userLevel->code,
                'name' => $userLevel->name,
            ],
            'tier' => [
                'id' => $tier->id,
                'tier_name' => $tier->tier_name,
                'tier_order' => $tier->tier_order,
                'description' => $tier->description,
                'display_name' => $tier->display_name,
                'created_at' => $tier->created_at,
                'updated_at' => $tier->updated_at,
            ]
        ]);
    }

    /**
     * Show edit tier form
     */
    public function edit(UserLevel $userLevel, UserLevelTier $tier): Response
    {
        // Ensure tier belongs to the user level
        if ($tier->user_level_id !== $userLevel->id) {
            abort(404);
        }

        $existingTiers = $userLevel->tiers()
            ->where('id', '!=', $tier->id)
            ->orderBy('tier_order')
            ->get(['tier_name', 'tier_order']);

        return Inertia::render('Admin/UserLevels/Tiers/Edit', [
            'userLevel' => [
                'id' => $userLevel->id,
                'code' => $userLevel->code,
                'name' => $userLevel->name,
            ],
            'tier' => $tier,
            'existingTiers' => $existingTiers
        ]);
    }

    /**
     * Update tier
     */
    public function update(Request $request, UserLevel $userLevel, UserLevelTier $tier)
    {
        // Ensure tier belongs to the user level
        if ($tier->user_level_id !== $userLevel->id) {
            abort(404);
        }

        $validated = $request->validate([
            'tier_name' => 'required|string|max:50',
            'tier_order' => 'required|integer|min:1|max:10',
            'description' => 'nullable|string|max:500',
        ]);

        // Check if tier_order already exists for this level (excluding current tier)
        $existingTier = $userLevel->tiers()
            ->where('tier_order', $validated['tier_order'])
            ->where('id', '!=', $tier->id)
            ->exists();

        if ($existingTier) {
            return back()->withErrors([
                'tier_order' => 'Tier order already exists for this level.'
            ]);
        }

        $tier->update($validated);

        return redirect()
            ->route('admin.user-level-tiers.show', [$userLevel, $tier])
            ->with('success', 'Tier updated successfully.');
    }

    /**
     * Delete tier
     */
    public function destroy(UserLevel $userLevel, UserLevelTier $tier)
    {
        try {
            // Ensure tier belongs to the user level
            if ($tier->user_level_id !== $userLevel->id) {
                abort(404);
            }

            // TODO: Later check if tier has incentives assigned
            // if ($tier->incentives()->count() > 0) {
            //     return back()->withErrors([
            //         'delete' => 'Cannot delete tier with incentives assigned. Please remove incentives first.'
            //     ]);
            // }

            $tierName = $tier->tier_name;
            $tier->delete();

            return redirect()
                ->route('admin.user-level-tiers.index', $userLevel)
                ->with('success', "Tier '{$tierName}' deleted successfully.");

        } catch (\Exception $e) {

            return back()->withErrors([
                'delete' => 'Failed to delete tier. Please try again.'
            ]);
        }
    }

    /**
     * Bulk create default tiers for a user level
     */
    public function bulkCreateDefault(Request $request, UserLevel $userLevel)
    {
        try {
            // Check if level already has tiers
            if ($userLevel->tiers()->count() > 0) {
                return back()->withErrors([
                    'bulk_create' => 'This level already has tiers. Delete existing tiers first.'
                ]);
            }

            // Create default 3 tiers
            $defaultTiers = [
                ['tier_name' => 'Tier 1', 'tier_order' => 1, 'description' => 'Basic tier'],
                ['tier_name' => 'Tier 2', 'tier_order' => 2, 'description' => 'Intermediate tier'],
                ['tier_name' => 'Tier 3', 'tier_order' => 3, 'description' => 'Advanced tier'],
            ];

            foreach ($defaultTiers as $tierData) {
                $userLevel->tiers()->create($tierData);
            }

            return redirect()
                ->route('admin.user-level-tiers.index', $userLevel)
                ->with('success', '3 default tiers created successfully for ' . $userLevel->name);

        } catch (\Exception $e) {

            return back()->withErrors([
                'bulk_create' => 'Failed to create default tiers. Please try again.'
            ]);
        }
    }
}
