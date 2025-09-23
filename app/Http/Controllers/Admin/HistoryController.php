<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluationHistory;
use App\Models\Department;
use App\Models\Incentive;
use App\Services\CsvExportService;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    protected $csvExportService;

    public function __construct(CsvExportService $csvExportService)
    {
        $this->csvExportService = $csvExportService;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['department_id', 'start_date', 'end_date', 'user_id', 'course_id']);

        $query = EvaluationHistory::with('evaluation.user', 'evaluation.course', 'evaluation.department')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (!empty($filters['department_id'])) {
            $query->whereHas('evaluation', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        if (!empty($filters['user_id'])) {
            $query->whereHas('evaluation', function ($q) use ($filters) {
                $q->where('user_id', $filters['user_id']);
            });
        }

        if (!empty($filters['course_id'])) {
            $query->whereHas('evaluation', function ($q) use ($filters) {
                $q->where('course_id', $filters['course_id']);
            });
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        $history = $query->paginate(15)->withQueryString();

        // Get dropdown data for filters
        $departments = Department::select(['id', 'name'])->orderBy('name')->get();
        $users = \App\Models\User::select(['id', 'name'])->orderBy('name')->get();
        $courses = \App\Models\Course::select(['id', 'name'])->orderBy('name')->get();

        // Calculate analytics with dynamic performance distribution
        $analytics = $this->getEvaluationAnalytics($filters);

        return inertia('Admin/History/Index', [
            'history' => $history,
            'departments' => $departments,
            'users' => $users,
            'courses' => $courses,
            'filters' => $filters,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Get evaluation analytics with DYNAMIC performance distribution based on Incentive ranges
     */
    private function getEvaluationAnalytics($filters = [])
    {
        $query = EvaluationHistory::with('evaluation');

        // Apply same filters
        if (!empty($filters['department_id'])) {
            $query->whereHas('evaluation', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        if (!empty($filters['user_id'])) {
            $query->whereHas('evaluation', function ($q) use ($filters) {
                $q->where('user_id', $filters['user_id']);
            });
        }

        if (!empty($filters['course_id'])) {
            $query->whereHas('evaluation', function ($q) use ($filters) {
                $q->where('course_id', $filters['course_id']);
            });
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        // Get unique evaluations from history
        $evaluations = $query->get()
            ->groupBy('evaluation_id')
            ->map(function ($group) {
                return $group->first()->evaluation;
            })
            ->filter();

        $totalEvaluations = $evaluations->count();
        $totalIncentives = $evaluations->sum('incentive_amount');
        $averageScore = $totalEvaluations > 0 ? round($evaluations->avg('total_score'), 2) : 0;

        // DYNAMIC Performance distribution based on actual Incentive ranges
        $performanceDistribution = $this->getDynamicPerformanceDistribution($evaluations);

        // Monthly trends
        $monthlyTrends = EvaluationHistory::query()
            ->when(!empty($filters['department_id']), fn($q) => $q->whereHas('evaluation', fn($subQ) => $subQ->where('department_id', $filters['department_id'])))
            ->when(!empty($filters['user_id']), fn($q) => $q->whereHas('evaluation', fn($subQ) => $subQ->where('user_id', $filters['user_id'])))
            ->when(!empty($filters['course_id']), fn($q) => $q->whereHas('evaluation', fn($subQ) => $subQ->where('course_id', $filters['course_id'])))
            ->when(!empty($filters['start_date']), fn($q) => $q->whereDate('created_at', '>=', $filters['start_date']))
            ->when(!empty($filters['end_date']), fn($q) => $q->whereDate('created_at', '<=', $filters['end_date']))
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(DISTINCT evaluation_id) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->limit(12)
            ->pluck('count', 'month')
            ->toArray();

        // Top performing categories
        $topCategories = EvaluationHistory::query()
            ->when(!empty($filters['department_id']), fn($q) => $q->whereHas('evaluation', fn($subQ) => $subQ->where('department_id', $filters['department_id'])))
            ->when(!empty($filters['user_id']), fn($q) => $q->whereHas('evaluation', fn($subQ) => $subQ->where('user_id', $filters['user_id'])))
            ->when(!empty($filters['course_id']), fn($q) => $q->whereHas('evaluation', fn($subQ) => $subQ->where('course_id', $filters['course_id'])))
            ->when(!empty($filters['start_date']), fn($q) => $q->whereDate('created_at', '>=', $filters['start_date']))
            ->when(!empty($filters['end_date']), fn($q) => $q->whereDate('created_at', '<=', $filters['end_date']))
            ->selectRaw('category_name, AVG(score) as avg_score, COUNT(*) as count')
            ->groupBy('category_name')
            ->orderByDesc('avg_score')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'category_name' => $item->category_name,
                    'avg_score' => round($item->avg_score, 1),
                    'count' => $item->count,
                ];
            });

        return [
            'total_evaluations' => $totalEvaluations,
            'total_incentives' => $totalIncentives,
            'average_score' => $averageScore,
            'performance_distribution' => $performanceDistribution,
            'monthly_trends' => $monthlyTrends,
            'top_categories' => $topCategories,
        ];
    }

    /**
     * DYNAMIC Performance Distribution based on Incentive table ranges
     */
    private function getDynamicPerformanceDistribution($evaluations)
    {
        // Get all incentive ranges from database, ordered by min_score DESC (highest first)
        $incentiveRanges = Incentive::orderBy('min_score', 'desc')->get();

        if ($incentiveRanges->isEmpty()) {
            return [];
        }

        $distribution = [];

        foreach ($incentiveRanges as $incentive) {
            $count = $evaluations->filter(function ($evaluation) use ($incentive) {
                return $evaluation->total_score >= $incentive->min_score &&
                    $evaluation->total_score <= $incentive->max_score;
            })->count();

            // Create a performance tier name
            $tierName = $this->getPerformanceTierName($incentive, $incentiveRanges);

            $distribution[] = [
                'id' => $incentive->id,
                'name' => $tierName,
                'range' => "{$incentive->min_score}-{$incentive->max_score}",
                'min_score' => $incentive->min_score,
                'max_score' => $incentive->max_score,
                'incentive_amount' => $incentive->incentive_amount,
                'count' => $count,
                'color_class' => $this->getTierColorClass($incentive, $incentiveRanges)
            ];
        }

        return $distribution;
    }

    /**
     * Generate tier names based on position in incentive hierarchy
     */
    private function getPerformanceTierName($incentive, $allIncentives)
    {
        // Sort by incentive amount descending (highest incentive = best performance)
        $sortedByAmount = $allIncentives->sortByDesc('incentive_amount');
        $position = $sortedByAmount->search(function($item) use ($incentive) {
            return $item->id === $incentive->id;
        });

        $tierNames = [
            'Exceptional',    // Highest incentive
            'Excellent',
            'Good',
            'Average',
            'Below Average',
            'Poor',
            'Very Poor'      // Lowest incentive
        ];

        // Return tier name based on position, or generate one if more than 7 tiers
        if ($position < count($tierNames)) {
            return $tierNames[$position];
        }

        return "Tier " . ($position + 1);
    }

    /**
     * Get color class for tier based on performance level
     */
    private function getTierColorClass($incentive, $allIncentives)
    {
        $sortedByAmount = $allIncentives->sortByDesc('incentive_amount');
        $position = $sortedByAmount->search(function($item) use ($incentive) {
            return $item->id === $incentive->id;
        });

        $totalTiers = $allIncentives->count();
        $percentile = ($totalTiers - $position) / $totalTiers;

        if ($percentile >= 0.8) return 'emerald'; // Top 20%
        if ($percentile >= 0.6) return 'green';   // Top 40%
        if ($percentile >= 0.4) return 'blue';    // Middle 40%
        if ($percentile >= 0.2) return 'yellow';  // Bottom 40%
        return 'red'; // Bottom 20%
    }

    /**
     * Enhanced export summary with dynamic performance distribution
     */
    public function exportSummary(Request $request)
    {
        $filters = $request->only(['department_id', 'start_date', 'end_date', 'user_id', 'course_id']);
        $analytics = $this->getEvaluationAnalytics($filters);

        // Create dynamic summary data
        $summaryData = [
            ['Metric', 'Value', 'Details'],
            ['Total Evaluations', $analytics['total_evaluations'], ''],
            ['Total Incentives', '$' . number_format($analytics['total_incentives'], 2), ''],
            ['Average Score', $analytics['average_score'], ''],
            ['', '', ''], // Empty row
            ['Performance Distribution', '', ''],
        ];

        // Add dynamic performance tiers
        foreach ($analytics['performance_distribution'] as $tier) {
            $summaryData[] = [
                $tier['name'],
                $tier['count'],
                "Score Range: {$tier['range']} | Incentive: $" . number_format($tier['incentive_amount'], 2)
            ];
        }

        // Add empty row and top categories
        $summaryData[] = ['', '', ''];
        $summaryData[] = ['Top Performing Categories', '', ''];

        foreach ($analytics['top_categories'] as $category) {
            $summaryData[] = [
                $category['category_name'],
                'Avg: ' . $category['avg_score'],
                $category['count'] . ' evaluations'
            ];
        }

        $headers = ['Metric', 'Value', 'Details'];

        return $this->csvExportService->export(
            'evaluation_summary_' . date('Y-m-d') . '.csv',
            $headers,
            $summaryData
        );
    }

    public function export(Request $request)
    {
        $filters = $request->only(['department_id', 'start_date', 'end_date', 'user_id', 'course_id']);

        $query = EvaluationHistory::with('evaluation.user', 'evaluation.course', 'evaluation.department')
            ->orderBy('created_at', 'desc');

        // Apply same filters as index
        if (!empty($filters['department_id'])) {
            $query->whereHas('evaluation', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        if (!empty($filters['user_id'])) {
            $query->whereHas('evaluation', function ($q) use ($filters) {
                $q->where('user_id', $filters['user_id']);
            });
        }

        if (!empty($filters['course_id'])) {
            $query->whereHas('evaluation', function ($q) use ($filters) {
                $q->where('course_id', $filters['course_id']);
            });
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        $history = $query->get();

        $headers = [
            'Evaluation ID',
            'Employee Name',
            'Employee Email',
            'Department',
            'Course',
            'Category',
            'Performance Level',
            'Score',
            'Comments',
            'Total Score',
            'Incentive Amount',
            'Evaluation Date',
            'Created At'
        ];

        $data = $history->map(function($record) {
            return [
                'evaluation_id' => $record->evaluation_id,
                'employee_name' => $record->evaluation->user->name ?? 'N/A',
                'employee_email' => $record->evaluation->user->email ?? 'N/A',
                'department' => $record->evaluation->department->name ?? 'N/A',
                'course' => $record->evaluation->course->name ?? 'N/A',
                'category' => $record->category_name,
                'performance_level' => $record->type_name,
                'score' => $record->score,
                'comments' => $record->comments ?? '',
                'total_score' => $record->evaluation->total_score ?? 0,
                'incentive_amount' => '$' . number_format($record->evaluation->incentive_amount ?? 0, 2),
                'evaluation_date' => $record->evaluation->created_at ? $record->evaluation->created_at->format('Y-m-d') : 'N/A',
                'created_at' => $record->created_at->format('Y-m-d H:i:s')
            ];
        })->toArray();

        return $this->csvExportService->export(
            'evaluation_history_' . date('Y-m-d') . '.csv',
            $headers,
            $data
        );
    }

    public function details($evaluationId)
    {
        $history = EvaluationHistory::with('evaluation.user', 'evaluation.course', 'evaluation.department')
            ->where('evaluation_id', $evaluationId)
            ->get();

        if ($history->isEmpty()) {
            return redirect()->route('admin.evaluations.history')
                ->with('error', 'Evaluation details not found.');
        }

        return inertia('Admin/History/Details', [
            'history' => $history,
        ]);
    }

    /**
     * Get evaluation analytics with filters (following your pattern)
     */
//    private function getEvaluationAnalytics($filters = [])
//    {
//        $query = EvaluationHistory::with('evaluation');
//
//        // Apply same filters
//        if (!empty($filters['department_id'])) {
//            $query->whereHas('evaluation', function ($q) use ($filters) {
//                $q->where('department_id', $filters['department_id']);
//            });
//        }
//
//        if (!empty($filters['user_id'])) {
//            $query->whereHas('evaluation', function ($q) use ($filters) {
//                $q->where('user_id', $filters['user_id']);
//            });
//        }
//
//        if (!empty($filters['course_id'])) {
//            $query->whereHas('evaluation', function ($q) use ($filters) {
//                $q->where('course_id', $filters['course_id']);
//            });
//        }
//
//        if (!empty($filters['start_date'])) {
//            $query->whereDate('created_at', '>=', $filters['start_date']);
//        }
//
//        if (!empty($filters['end_date'])) {
//            $query->whereDate('created_at', '<=', $filters['end_date']);
//        }
//
//        // Get unique evaluations from history
//        $evaluations = $query->get()
//            ->groupBy('evaluation_id')
//            ->map(function ($group) {
//                return $group->first()->evaluation;
//            })
//            ->filter();
//
//        $totalEvaluations = $evaluations->count();
//        $totalIncentives = $evaluations->sum('incentive_amount');
//        $averageScore = $totalEvaluations > 0 ? round($evaluations->avg('total_score'), 2) : 0;
//
//        // Performance distribution
//        $performanceDistribution = [
//            'excellent' => $evaluations->where('total_score', '>=', 20)->count(),
//            'good' => $evaluations->whereBetween('total_score', [15, 19])->count(),
//            'average' => $evaluations->whereBetween('total_score', [10, 14])->count(),
//            'below_average' => $evaluations->whereBetween('total_score', [5, 9])->count(),
//            'poor' => $evaluations->where('total_score', '<', 5)->count(),
//        ];
//
//        // Monthly trends (following your pattern)
//        $monthlyTrends = EvaluationHistory::query()
//            ->when(!empty($filters['department_id']), fn($q) => $q->whereHas('evaluation', fn($subQ) => $subQ->where('department_id', $filters['department_id'])))
//            ->when(!empty($filters['user_id']), fn($q) => $q->whereHas('evaluation', fn($subQ) => $subQ->where('user_id', $filters['user_id'])))
//            ->when(!empty($filters['course_id']), fn($q) => $q->whereHas('evaluation', fn($subQ) => $subQ->where('course_id', $filters['course_id'])))
//            ->when(!empty($filters['start_date']), fn($q) => $q->whereDate('created_at', '>=', $filters['start_date']))
//            ->when(!empty($filters['end_date']), fn($q) => $q->whereDate('created_at', '<=', $filters['end_date']))
//            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(DISTINCT evaluation_id) as count')
//            ->groupBy('month')
//            ->orderBy('month')
//            ->limit(12)
//            ->pluck('count', 'month')
//            ->toArray();
//
//        // Top performing categories
//        $topCategories = EvaluationHistory::query()
//            ->when(!empty($filters['department_id']), fn($q) => $q->whereHas('evaluation', fn($subQ) => $subQ->where('department_id', $filters['department_id'])))
//            ->when(!empty($filters['user_id']), fn($q) => $q->whereHas('evaluation', fn($subQ) => $subQ->where('user_id', $filters['user_id'])))
//            ->when(!empty($filters['course_id']), fn($q) => $q->whereHas('evaluation', fn($subQ) => $subQ->where('course_id', $filters['course_id'])))
//            ->when(!empty($filters['start_date']), fn($q) => $q->whereDate('created_at', '>=', $filters['start_date']))
//            ->when(!empty($filters['end_date']), fn($q) => $q->whereDate('created_at', '<=', $filters['end_date']))
//            ->selectRaw('category_name, AVG(score) as avg_score, COUNT(*) as count')
//            ->groupBy('category_name')
//            ->orderByDesc('avg_score')
//            ->limit(5)
//            ->get()
//            ->map(function ($item) {
//                return [
//                    'category_name' => $item->category_name,
//                    'avg_score' => round($item->avg_score, 1),
//                    'count' => $item->count,
//                ];
//            });
//
//        return [
//            'total_evaluations' => $totalEvaluations,
//            'total_incentives' => $totalIncentives,
//            'average_score' => $averageScore,
//            'performance_distribution' => $performanceDistribution,
//            'monthly_trends' => $monthlyTrends,
//            'top_categories' => $topCategories,
//        ];
//    }
//
//    /**
//     * Export evaluation summary report (additional method following your pattern)
//     */
//    public function exportSummary(Request $request)
//    {
//        $filters = $request->only(['department_id', 'start_date', 'end_date', 'user_id', 'course_id']);
//        $analytics = $this->getEvaluationAnalytics($filters);
//
//        // Create summary data
//        $summaryData = [
//            ['Metric', 'Value'],
//            ['Total Evaluations', $analytics['total_evaluations']],
//            ['Total Incentives', '$' . number_format($analytics['total_incentives'], 2)],
//            ['Average Score', $analytics['average_score']],
//            ['', ''], // Empty row
//            ['Performance Distribution', ''],
//            ['Excellent (20+)', $analytics['performance_distribution']['excellent']],
//            ['Good (15-19)', $analytics['performance_distribution']['good']],
//            ['Average (10-14)', $analytics['performance_distribution']['average']],
//            ['Below Average (5-9)', $analytics['performance_distribution']['below_average']],
//            ['Poor (<5)', $analytics['performance_distribution']['poor']],
//        ];
//
//        $headers = ['Metric', 'Value'];
//
//        return $this->csvExportService->export(
//            'evaluation_summary_' . date('Y-m-d') . '.csv',
//            $headers,
//            $summaryData
//        );
//    }
}
