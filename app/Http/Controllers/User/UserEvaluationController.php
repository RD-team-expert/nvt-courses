<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Enums\PerformanceLevel;
use App\Models\User;
use App\Models\Evaluation;

class UserEvaluationController extends Controller
{
    /**
     * Display user's evaluation history with performance levels
     */
    public function index()
    {
        $user = Auth::user();

        // Get user's evaluations with course details
        $evaluations = Evaluation::where('user_id', $user->id)
            ->with(['course'])
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(function ($evaluation) {
                // Manually get the evaluator if needed
                $evaluatedBy = null;
                if ($evaluation->created_by) {
                    $evaluatedBy = User::find($evaluation->created_by);
                }

                return [
                    'id' => $evaluation->id,
                    'course' => [
                        'id' => $evaluation->course->id ?? null,
                        'name' => $evaluation->course->name ?? 'Unknown Course',
                    ],
                    'total_score' => $evaluation->total_score,
                    'performance_level' => $evaluation->performance_level,
                    'performance_label' => $evaluation->getPerformanceLevelLabel(),
                    'performance_color' => $evaluation->getPerformanceLevelColor(),
                    'performance_range' => $evaluation->getPerformancePointsRange(),
                    'incentive_amount' => $evaluation->incentive_amount,
                    'notes' => $evaluation->notes,
                    'evaluation_date' => $evaluation->evaluation_date?->toDateString(),
                    'evaluated_by' => $evaluatedBy ? [
                        'id' => $evaluatedBy->id,
                        'name' => $evaluatedBy->name,
                    ] : null,
                    'created_at' => $evaluation->created_at->toISOString(),
                    'categories' => $this->getEvaluationCategories($evaluation->id),
                ];
            });

        // Calculate stats
        $evaluationsQuery = Evaluation::where('user_id', $user->id);
        $stats = [
            'total_evaluations' => $evaluationsQuery->count(),
            'average_score' => round($evaluationsQuery->avg('total_score') ?: 0, 2),
            'total_incentives' => $evaluationsQuery->sum('incentive_amount') ?: 0,
            'best_score' => $evaluationsQuery->max('total_score') ?: 0,
        ];

        // Performance level distribution
        $performanceLevels = PerformanceLevel::getForFrontend();
        $levelCounts = Evaluation::where('user_id', $user->id)
            ->selectRaw('performance_level, count(*) as count')
            ->groupBy('performance_level')
            ->pluck('count', 'performance_level')
            ->toArray();

        return Inertia::render('User/Evaluations/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'evaluations' => $evaluations,
            'stats' => $stats,
            'performanceLevels' => $performanceLevels,
            'levelCounts' => $levelCounts,
        ]);
    }

    /**
     * Show detailed evaluation with performance level data
     */
    public function show($id)
    {
        $user = Auth::user();

        $evaluation = Evaluation::where('user_id', $user->id)
            ->with(['course'])
            ->findOrFail($id);

        // Manually get the evaluator if needed
        $evaluatedBy = null;
        if ($evaluation->created_by) {
            $evaluatedBy = User::find($evaluation->created_by);
        }

        // Get performance level data
        $performanceLevel = $evaluation->performance_level;
        $performanceLevelData = null;
        
        if ($performanceLevel) {
            $performanceLevelData = PerformanceLevel::getById($performanceLevel);
        }

        return Inertia::render('User/Evaluations/Show', [
            'evaluation' => [
                'id' => $evaluation->id,
                'course' => [
                    'id' => $evaluation->course->id ?? null,
                    'name' => $evaluation->course->name ?? 'Unknown Course',
                ],
                'total_score' => $evaluation->total_score,
                'performance_level' => $evaluation->performance_level,
                'performance_label' => $evaluation->getPerformanceLevelLabel(),
                'performance_color' => $evaluation->getPerformanceLevelColor(),
                'performance_range' => $evaluation->getPerformancePointsRange(),
                'performance_description' => $evaluation->getPerformanceLevelDescription(),
                'incentive_amount' => $evaluation->incentive_amount,
                'notes' => $evaluation->notes,
                'evaluation_date' => $evaluation->evaluation_date?->toDateString(),
                'evaluated_by' => $evaluatedBy ? [
                    'id' => $evaluatedBy->id,
                    'name' => $evaluatedBy->name,
                ] : null,
                'created_at' => $evaluation->created_at->toISOString(),
                'categories' => $this->getEvaluationCategories($evaluation->id),
            ],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'performanceLevelData' => $performanceLevelData,
        ]);
    }

    /**
     * Get evaluation categories - HELPER METHOD
     */
    private function getEvaluationCategories($evaluationId)
    {
        // Look for evaluation history records
        try {
            return DB::table('evaluation_histories')
                ->where('evaluation_id', $evaluationId)
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'category_name' => $category->category_name ?? 'Unknown Category',
                        'type_name' => $category->type_name ?? 'Standard',
                        'score' => $category->score ?? 0,
                        'comments' => $category->comments ?? '',
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            // Fallback to evaluation_categories if it exists
            try {
                return DB::table('evaluation_categories')
                    ->where('evaluation_id', $evaluationId)
                    ->get()
                    ->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'category_name' => $category->category_name ?? 'Unknown Category',
                            'evaluation_type' => $category->evaluation_type ?? 'Standard',
                            'score' => $category->score ?? 0,
                            'comments' => $category->comments ?? '',
                        ];
                    })
                    ->toArray();
            } catch (\Exception $e) {
                // Return empty array if neither table exists
                return [];
            }
        }
    }
}
