<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Course;
use App\Models\CourseOnline;
use App\Models\User;
use App\Services\CourseProgressService;
use App\Services\LearningScoreCalculator;
use App\Services\ExcelExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserCourseProgressReportController extends Controller
{
    protected CourseProgressService $courseProgressService;
    protected LearningScoreCalculator $learningScoreCalculator;
    protected ExcelExportService $excelExportService;

    public function __construct(
        CourseProgressService $courseProgressService,
        LearningScoreCalculator $learningScoreCalculator,
        ExcelExportService $excelExportService
    ) {
        $this->courseProgressService = $courseProgressService;
        $this->learningScoreCalculator = $learningScoreCalculator;
        $this->excelExportService = $excelExportService;
        
       
    }

    /**
     * Display the user course progress report
     */
    public function index(Request $request): Response
    {
        try {
            // Get and validate filters
            $filters = $request->only([
                'department_id',
                'course_type',
                'date_from',
                'date_to',
                'status',
                'user_id',
                'course_id'
            ]);
            
            // Validate date range
            if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
                if ($filters['date_from'] > $filters['date_to']) {
                    return Inertia::render('Admin/Reports/UserCourseProgress', [
                        'assignments' => collect([]),
                        'filters' => $filters,
                        'filterOptions' => $this->getFilterOptions(),
                        'error' => 'Date from must be before date to',
                    ]);
                }
            }
            
            // Get progress data
            $progressData = $this->courseProgressService->getProgressData($filters);
            
            // Calculate learning scores and enrich data
            $enrichedData = $progressData->map(function ($assignment) {
                try {
                    // Get attention score
                    $attentionScore = $this->learningScoreCalculator->getAttentionScore(
                        $assignment['user_id'],
                        $assignment['course_id'],
                        $assignment['course_type']
                    );
                    
                    // Get quiz score
                    $quizScore = $this->learningScoreCalculator->getQuizScore(
                        $assignment['user_id'],
                        $assignment['course_id']
                    );
                    
                    // Calculate completion rate (0 or 100 based on status)
                    $completionRate = $assignment['status'] === 'completed' ? 100 : 0;
                    
                    // Get suspicious activities count
                    $suspiciousActivities = 0;
                    $totalSessions = 0;
                    
                    if ($assignment['course_type'] === 'online') {
                        $sessionStats = DB::table('learning_sessions')
                            ->where('user_id', $assignment['user_id'])
                            ->where('course_online_id', $assignment['course_id'])
                            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_suspicious_activity = 1 THEN 1 ELSE 0 END) as suspicious')
                            ->first();
                        
                        $totalSessions = $sessionStats->total ?? 0;
                        $suspiciousActivities = $sessionStats->suspicious ?? 0;
                    }
                    
                    // Calculate learning score
                    $learningScore = $this->learningScoreCalculator->calculate(
                        $completionRate,
                        $assignment['progress_percentage'],
                        $attentionScore,
                        $quizScore,
                        $suspiciousActivities,
                        $totalSessions
                    );
                    
                    // Determine score band
                    $scoreBand = $this->courseProgressService->determineScoreBand($learningScore);
                    
                    // Add calculated fields
                    $assignment['learning_score'] = round($learningScore, 1);
                    $assignment['score_band'] = $scoreBand;
                    $assignment['attention_score'] = $attentionScore;
                    $assignment['quiz_score'] = $quizScore;
                    $assignment['completion_rate'] = $completionRate;
                    
                    // Format dates for display
                    $assignment['started_date'] = $assignment['started_at'] 
                        ? $assignment['started_at']->format('m/d/Y') 
                        : null;
                    $assignment['completion_date'] = $assignment['completed_at'] 
                        ? $assignment['completed_at']->format('m/d/Y') 
                        : null;
                    $assignment['assigned_date'] = $assignment['assigned_at']->format('m/d/Y');
                    $assignment['course_beginning_date_formatted'] = $assignment['course_beginning_date'] 
                        ? $assignment['course_beginning_date']->format('m/d/Y') 
                        : null;
                    
                    return $assignment;
                    
                } catch (\Exception $e) {
                    // Log error and use defaults
                    \Log::error('Error calculating learning score for assignment: ' . $e->getMessage());
                    
                    $assignment['learning_score'] = 0;
                    $assignment['score_band'] = 'Needs Attention';
                    $assignment['attention_score'] = 65;
                    $assignment['quiz_score'] = 0;
                    $assignment['completion_rate'] = 0;
                    $assignment['started_date'] = null;
                    $assignment['completion_date'] = null;
                    $assignment['assigned_date'] = $assignment['assigned_at']->format('m/d/Y');
                    $assignment['course_beginning_date_formatted'] = $assignment['course_beginning_date'] 
                        ? $assignment['course_beginning_date']->format('m/d/Y') 
                        : null;
                    
                    return $assignment;
                }
            });
            
            // Paginate results
            $perPage = 15;
            $page = $request->input('page', 1);
            $total = $enrichedData->count();
            
            $paginatedData = $enrichedData->forPage($page, $perPage)->values();
            
            $pagination = [
                'data' => $paginatedData,
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
                'from' => (($page - 1) * $perPage) + 1,
                'to' => min($page * $perPage, $total),
            ];
            
            return Inertia::render('Admin/Reports/UserCourseProgress', [
                'assignments' => $pagination,
                'filters' => $filters,
                'filterOptions' => $this->getFilterOptions(),
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in UserCourseProgressReport index: ' . $e->getMessage());
            
            return Inertia::render('Admin/Reports/UserCourseProgress', [
                'assignments' => collect([]),
                'filters' => $request->only(['department_id', 'course_type', 'date_from', 'date_to', 'status', 'user_id', 'course_id']),
                'filterOptions' => $this->getFilterOptions(),
                'error' => 'An error occurred while loading the report. Please try again.',
            ]);
        }
    }

    /**
     * Get filter options for dropdowns
     * 
     * @return array
     */
    private function getFilterOptions(): array
    {
        return [
            'departments' => Department::where('is_active', true)
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
            'users' => User::where('role', '!=', 'admin')
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get(),
            'courses' => Course::select('id', 'name', DB::raw("'traditional' as type"))
                ->union(
                    CourseOnline::select('id', 'name', DB::raw("'online' as type"))
                        ->where('is_active', true)
                )
                ->orderBy('name')
                ->get(),
            'courseTypes' => [
                ['value' => 'traditional', 'label' => 'Traditional'],
                ['value' => 'online', 'label' => 'Online'],
            ],
            'statuses' => [
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'in_progress', 'label' => 'In Progress'],
                ['value' => 'assigned', 'label' => 'Assigned'],
                ['value' => 'overdue', 'label' => 'Overdue'],
            ],
        ];
    }

    /**
     * Export user course progress data to Excel with 2 sheets
     * Sheet 1: Completed Courses (KPI)
     * Sheet 2: Non-Completed Courses (KPI)
     */
    public function export(Request $request)
    {
        try {
            // Get filters (same as index)
            $filters = $request->only([
                'department_id',
                'course_type',
                'date_from',
                'date_to',
                'status',
                'user_id',
                'course_id'
            ]);
            
            // Get ALL progress data (no pagination for export)
            $progressData = $this->courseProgressService->getProgressData($filters);
            
            // Calculate learning scores and enrich data
            $enrichedData = $progressData->map(function ($assignment) {
                try {
                    // Get attention score
                    $attentionScore = $this->learningScoreCalculator->getAttentionScore(
                        $assignment['user_id'],
                        $assignment['course_id'],
                        $assignment['course_type']
                    );
                    
                    // Get quiz score
                    $quizScore = $this->learningScoreCalculator->getQuizScore(
                        $assignment['user_id'],
                        $assignment['course_id']
                    );
                    
                    // Calculate completion rate
                    $completionRate = $assignment['status'] === 'completed' ? 100 : 0;
                    
                    // Get suspicious activities
                    $suspiciousActivities = 0;
                    $totalSessions = 0;
                    
                    if ($assignment['course_type'] === 'online') {
                        $sessionStats = DB::table('learning_sessions')
                            ->where('user_id', $assignment['user_id'])
                            ->where('course_online_id', $assignment['course_id'])
                            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_suspicious_activity = 1 THEN 1 ELSE 0 END) as suspicious')
                            ->first();
                        
                        $totalSessions = $sessionStats->total ?? 0;
                        $suspiciousActivities = $sessionStats->suspicious ?? 0;
                    }
                    
                    // Calculate learning score
                    $learningScore = $this->learningScoreCalculator->calculate(
                        $completionRate,
                        $assignment['progress_percentage'],
                        $attentionScore,
                        $quizScore,
                        $suspiciousActivities,
                        $totalSessions
                    );
                    
                    // Determine score band
                    $scoreBand = $this->courseProgressService->determineScoreBand($learningScore);
                    
                    // Add calculated fields
                    $assignment['learning_score'] = round($learningScore, 1);
                    $assignment['score_band'] = $scoreBand;
                    
                    // Format dates for export
                    $assignment['started_date'] = $assignment['started_at'] 
                        ? $assignment['started_at']->format('m/d/Y') 
                        : '';
                    $assignment['completion_date'] = $assignment['completed_at'] 
                        ? $assignment['completed_at']->format('m/d/Y') 
                        : '';
                    $courseBeginningDate = $assignment['course_beginning_date'] ?? null;
                    $assignment['course_beginning_date_formatted'] = $courseBeginningDate 
                        ? $courseBeginningDate->format('m/d/Y') 
                        : '';
                    
                    return $assignment;
                    
                } catch (\Exception $e) {
                    \Log::error('Error calculating learning score for export: ' . $e->getMessage());
                    
                    $assignment['learning_score'] = 0;
                    $assignment['score_band'] = 'Needs Attention';
                    $assignment['started_date'] = '';
                    $assignment['completion_date'] = '';
                    $courseBeginningDateCatch = $assignment['course_beginning_date'] ?? null;
                    $assignment['course_beginning_date_formatted'] = $courseBeginningDateCatch 
                        ? $courseBeginningDateCatch->format('m/d/Y') 
                        : '';
                    
                    return $assignment;
                }
            });
            
            // Separate into completed and non-completed for 2 sheets
            $completedData = $enrichedData->filter(function ($assignment) {
                return $assignment['status'] === 'completed';
            })->values();
            
            $nonCompletedData = $enrichedData->filter(function ($assignment) {
                return $assignment['status'] !== 'completed';
            })->values();
            
            // Use Excel export service for multi-sheet export
            return $this->excelExportService->exportCourseProgress($completedData, $nonCompletedData);
            
        } catch (\Exception $e) {
            \Log::error('Error exporting user course progress: ' . $e->getMessage());
            abort(500, 'An error occurred while generating the export. Please try again.');
        }
    }
}
