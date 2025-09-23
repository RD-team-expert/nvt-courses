<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UserEvaluationController extends Controller
{
    /**
     * Display user's evaluation history - FIXED
     */
    public function index()
    {
        $user = Auth::user();

        // Get user's evaluations with course details - FIXED QUERY
        $evaluations = $user->evaluations()
            ->with(['course'])
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(function ($evaluation) {
                // Manually get the evaluator if needed
                $evaluatedBy = null;
                if ($evaluation->created_by) {
                    $evaluatedBy = \App\Models\User::find($evaluation->created_by);
                }

                return [
                    'id' => $evaluation->id,
                    'course' => [
                        'id' => $evaluation->course->id,
                        'name' => $evaluation->course->name,
                    ],
                    'total_score' => $evaluation->total_score,
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
        $stats = [
            'total_evaluations' => $user->evaluations()->count(),
            'average_score' => round($user->evaluations()->avg('total_score') ?: 0, 2),
            'total_incentives' => $user->evaluations()->sum('incentive_amount') ?: 0,
            'best_score' => $user->evaluations()->max('total_score') ?: 0,
        ];

        return Inertia::render('User/Evaluations/Index', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'evaluations' => $evaluations,
            'stats' => $stats,
        ]);
    }

    /**
     * Show detailed evaluation - FIXED
     */
    public function show($id)
    {
        $user = Auth::user();

        $evaluation = $user->evaluations()
            ->with(['course'])
            ->findOrFail($id);

        // Manually get the evaluator if needed
        $evaluatedBy = null;
        if ($evaluation->created_by) {
            $evaluatedBy = \App\Models\User::find($evaluation->created_by);
        }

        return Inertia::render('User/Evaluations/Show', [
            'evaluation' => [
                'id' => $evaluation->id,
                'course' => [
                    'id' => $evaluation->course->id,
                    'name' => $evaluation->course->name,
                ],
                'total_score' => $evaluation->total_score,
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
        ]);
    }

    /**
     * Get evaluation categories - HELPER METHOD
     */
    private function getEvaluationCategories($evaluationId)
    {
        // Adjust this based on your actual table structure
        // This assumes you have an evaluation_categories table
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
            // Return empty array if table doesn't exist
            return [];
        }
    }
}
