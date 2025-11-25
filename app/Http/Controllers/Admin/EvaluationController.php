<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluationConfig;
use App\Models\EvaluationType;
use App\Models\Incentive;
use App\Models\UserLevel;
use App\Models\UserLevelTier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EvaluationController extends Controller
{
    public function index()
    {
        // NEW: Filter configs based on applies_to (show all by default)
        $configs = EvaluationConfig::with('types')->get()->toArray();

        // Get existing incentives with their level and tier relationships
        $incentives = Incentive::with(['userLevel', 'userLevelTier'])
            ->orderBy('user_level_id')
            ->orderBy('user_level_tier_id')
            ->orderBy('min_score')
            ->get();

        // Get all user levels with their tiers for the incentive form
        $userLevels = UserLevel::with(['tiers' => function($query) {
            $query->orderBy('tier_order');
        }])->orderBy('hierarchy_level')->get();

        return inertia('Admin/Evaluations/Index', [
            'configs' => $configs,
            'incentives' => $incentives,
            'userLevels' => $userLevels,
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'max_score' => 'required|integer|min:1',
            'applies_to' => 'required|in:regular,online,both', // NEW

        ]);

        $config = EvaluationConfig::create($validated);

        return redirect()->back()->with('success', 'Evaluation category created successfully.');
    }

    public function update(Request $request, EvaluationConfig $evaluationConfig)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'max_score' => 'required|integer|min:1',
            'applies_to' => 'required|in:regular,online,both',
        ]);

        $evaluationConfig->update($validated);

        return redirect()->back()->with('success', 'Evaluation category updated successfully.');
    }

    public function destroy(EvaluationConfig $evaluationConfig)
    {
        $evaluationConfig->types()->delete();
        $evaluationConfig->delete();

        return redirect()->back()->with('success', 'Evaluation category deleted successfully.');
    }

    public function configureTypes(Request $request, EvaluationConfig $evaluationConfig)
    {
        $validated = $request->validate([
            'type_name' => 'required|string|max:255',
            'score_value' => 'required|integer|min:0|max:' . $evaluationConfig->max_score,
        ]);

        $evaluationConfig->types()->create($validated);

        return redirect()->back()->with('success', 'Evaluation type added successfully.');
    }

    public function setTotalScore(Request $request)
    {
        $validated = $request->validate([
            'total_score' => 'required|integer|min:1',
            'config_scores' => 'required|array',
        ]);

        $total = array_sum($validated['config_scores']);
        if ($total !== $validated['total_score']) {
            return redirect()->back()->withErrors(['error' => 'Sum of config scores must equal total score.']);
        }

        foreach ($validated['config_scores'] as $id => $score) {
            EvaluationConfig::find($id)->update(['max_score' => $score]);
        }

        return redirect()->back()->with('success', 'Total score distributed successfully.');
    }

    /**
     * NEW: Enhanced setIncentives method for Level + Tier based system
     */
    public function setIncentives(Request $request)
    {
        $validated = $request->validate([
            'incentives' => 'required|array',
            'incentives.*.user_level_id' => 'required|exists:user_levels,id',
            'incentives.*.user_level_tier_id' => 'required|exists:user_level_tiers,id',
            'incentives.*.performance_level' => 'required|integer|between:1,4',
        ]);

        foreach ($validated['incentives'] as $incentive) {
            $tier = UserLevelTier::find($incentive['user_level_tier_id']);
            if ($tier->user_level_id != $incentive['user_level_id']) {
                return redirect()->back()->withErrors([
                    'error' => 'Tier must belong to the selected level.'
                ]);
            }
        }

        // Save new incentives (now using performance_level)
        foreach ($validated['incentives'] as $incentive) {
            $levelMeta = \App\Enums\PerformanceLevel::getById($incentive['performance_level']);
            Incentive::updateOrCreate([
                'user_level_id' => $incentive['user_level_id'],
                'user_level_tier_id' => $incentive['user_level_tier_id'],
                'min_score' => $levelMeta['min_score'],
                'max_score' => $levelMeta['max_score'],
            ], [
                'performance_level' => $incentive['performance_level'],
            ]);
        }

        return redirect()->back()->with('success', 'Performance level incentives saved successfully.');
    }

    /**
     * Map a total score to a performance level
     */
    public function mapScoreToPerformanceLevel($score)
    {
        return \App\Enums\PerformanceLevel::getLevelByScore($score);
    }

    /**
     * NEW: Get incentive for specific user based on their level, tier, and score
     */
    public function getIncentiveForUser($userId, $evaluationScore)
    {
        $user = \App\Models\User::with(['userLevel', 'userLevelTier'])->find($userId);

        if (!$user || !$user->userLevel || !$user->userLevelTier) {
            return null;
        }

        $incentive = Incentive::where('user_level_id', $user->userLevel->id)
            ->where('user_level_tier_id', $user->userLevelTier->id)
            ->where('min_score', '<=', $evaluationScore)
            ->where('max_score', '>=', $evaluationScore)
            ->first();

        return $incentive ? $incentive->incentive_amount : 0;
    }

    /**
     * NEW: Get all incentives for a specific level and tier
     */
    public function getIncentivesForLevelTier(Request $request)
    {
        $validated = $request->validate([
            'user_level_id' => 'required|exists:user_levels,id',
            'user_level_tier_id' => 'required|exists:user_level_tiers,id',
        ]);

        $incentives = Incentive::where('user_level_id', $validated['user_level_id'])
            ->where('user_level_tier_id', $validated['user_level_tier_id'])
            ->orderBy('min_score')
            ->get();

        return response()->json($incentives);
    }

    public function destroyType($evaluationTypeId)
    {
        try {
            $evaluationType = EvaluationType::findOrFail($evaluationTypeId);
            $evaluationType->delete();

            return redirect()->back()->with('success', 'Evaluation type deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete evaluation type.');
        }
    }
}
