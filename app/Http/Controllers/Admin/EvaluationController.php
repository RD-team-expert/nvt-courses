<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluationConfig;
use App\Models\EvaluationType;
use App\Models\Incentive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EvaluationController extends Controller
{
    public function index()
    {
        $configs = EvaluationConfig::with('types')->get()->toArray();

        // Get existing incentives to display in the form
        $incentives = Incentive::orderBy('min_score', 'desc')->get();

        return inertia('Admin/Evaluations/Index', [
            'configs' => $configs,
            'incentives' => $incentives,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'max_score' => 'required|integer|min:1',
        ]);

        $config = EvaluationConfig::create($validated);

        return redirect()->back()->with('success', 'Evaluation category created successfully.');
    }

    public function update(Request $request, EvaluationConfig $evaluationConfig)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'max_score' => 'required|integer|min:1',
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

    public function setIncentives(Request $request)
    {
        // Custom validation rule to compare min_score and max_score
        Validator::extend('greater_than_min', function ($attribute, $value, $parameters, $validator) {
            $data = $validator->getData();
            $index = explode('.', $attribute)[1]; // Get the index of the incentives array
            $minScore = $data['incentives'][$index]['min_score'];
            return $value > $minScore;
        });
        // Validate the incoming data

        $validated = $request ;
        // Clear existing incentives (optional, depending on your needs)
        Incentive::truncate();
        // Save new incentives
        foreach ($validated['incentives'] as $incentive) {
            Incentive::create([
                'min_score' => $incentive['min_score'],
                'max_score' => $incentive['max_score'],
                'incentive_amount' => $incentive['incentive_amount'],
            ]);
        }

        return redirect()->back()->with('success', 'Incentives saved successfully.');
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
