<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Enums\PerformanceLevel;

class EvaluationEmailService
{
    /**
     * Send evaluation report to managers
     */

    public function sendEvaluationReport(array $managers, $employees, string $subject, string $customMessage = '', ?string $startDate = null, ?string $endDate = null): array
    {
        $results = [
            'success_count' => 0,
            'failed_count' => 0,
            'sent_to' => [],
            'failed_to' => []
        ];


        // Flatten managers array
        $allManagers = [];
        foreach ($managers as $level => $levelManagers) {
            foreach ($levelManagers as $manager) {
                if ($manager && isset($manager['email'])) {
                    $allManagers[] = [
                        'id' => $manager['id'],
                        'name' => $manager['name'],
                        'email' => $manager['email'],
                        'level' => $level
                    ];
                }
            }
        }


        if (empty($allManagers)) {
            return $results;
        }

        // Use provided date range or default to current month
        $filterStartDate = $startDate ? \Carbon\Carbon::parse($startDate)->startOfDay() : now()->startOfMonth();
        $filterEndDate = $endDate ? \Carbon\Carbon::parse($endDate)->endOfDay() : now()->endOfMonth();
        
        // Format report period for email
        $reportPeriod = $startDate || $endDate 
            ? $filterStartDate->format('M d, Y') . ' - ' . $filterEndDate->format('M d, Y')
            : now()->format('F Y');

        // ✅ NEW: Prepare detailed evaluation data with overall average
        // FILTER: Only include evaluations for courses assigned in the specified date range
        $detailedEvaluations = $employees->map(function ($employee) use ($filterStartDate, $filterEndDate) {
            // Filter evaluations to only include courses assigned in specified date range
            $filteredEvaluations = $employee->evaluations->filter(function ($evaluation) use ($employee, $filterStartDate, $filterEndDate) {
                // Check if this is a regular course or online course
                if ($evaluation->course_type === 'online' && $evaluation->course_online_id) {
                    // Check online course assignment date
                    $assignment = \DB::table('course_online_assignments')
                        ->where('user_id', $employee->id)
                        ->where('course_online_id', $evaluation->course_online_id)
                        ->whereBetween('assigned_at', [$filterStartDate, $filterEndDate])
                        ->first();
                    
                    return $assignment !== null;
                } elseif ($evaluation->course_id) {
                    // Check regular course assignment date
                    $assignment = \DB::table('course_assignments')
                        ->where('user_id', $employee->id)
                        ->where('course_id', $evaluation->course_id)
                        ->whereBetween('assigned_at', [$filterStartDate, $filterEndDate])
                        ->first();
                    
                    return $assignment !== null;
                }
                
                return false; // Exclude if no course assignment found
            });

            $evaluations = $filteredEvaluations->map(function ($evaluation) {
                // Get performance level data if available
                $performanceLevel = $evaluation->performance_level;
                $performanceData = null;
                
                if ($performanceLevel) {
                    $performanceData = [
                        'level' => $performanceLevel,
                        'label' => PerformanceLevel::getLabelByLevel($performanceLevel),
                        'color' => PerformanceLevel::getColorByLevel($performanceLevel),
                        'range' => PerformanceLevel::getRangeByLevel($performanceLevel),
                        'badge_class' => PerformanceLevel::getBadgeClassByLevel($performanceLevel),
                        'description' => PerformanceLevel::getDescriptionByLevel($performanceLevel),
                    ];
                }
                
                // Determine course name based on type
                $courseName = 'Unknown Course';
                if ($evaluation->course_type === 'online' && $evaluation->courseOnline) {
                    $courseName = $evaluation->courseOnline->name;
                } elseif ($evaluation->course) {
                    $courseName = $evaluation->course->name;
                }
                
                return [
                    'id' => $evaluation->id,
                    'course' => $courseName,
                    'course_type' => $evaluation->course_type ?? 'regular',
                    'total_score' => $evaluation->total_score,
                    'incentive_amount' => $evaluation->incentive_amount,
                    'performance_level' => $performanceLevel,
                    'performance_data' => $performanceData,
                    'created_at' => $evaluation->created_at->format('M d, Y'),
                    'detailed_scores' => $evaluation->history->map(function ($history) {
                        return [
                            'category_name' => $history->category_name,
                            'type_name' => $history->type_name,
                            'score' => $history->score,
                            'comments' => $history->comments ?? 'No comments'
                        ];
                    })->toArray()
                ];
            })->toArray();

            // Skip employees with no evaluations for specified date range
            if (empty($evaluations)) {
                return null;
            }

            // ✅ FIXED: Calculate overall average of course total_scores (not detailed scores)
            $courseTotalScores = collect($evaluations)->pluck('total_score');
            
            $overallAverage = $courseTotalScores->isNotEmpty()
                ? round($courseTotalScores->average(), 2)
                : 0;

            // ✅ Calculate individual course averages from detailed scores
            $courseAverages = collect($evaluations)->map(function ($eval) {
                $scores = collect($eval['detailed_scores'])->pluck('score');
                return $scores->isNotEmpty() ? round($scores->average(), 2) : 0;
            });

            return [
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                    'department' => $employee->department ? $employee->department->name : 'No Department',
                    'level' => $employee->userLevel ? $employee->userLevel->name : 'Unknown'
                ],
                'evaluations' => $evaluations,
                'overall_average' => $overallAverage,              // ✅ FIXED: Average of course total_scores
                'course_averages' => $courseAverages->toArray(),  // ✅ Individual course averages
                'total_evaluations' => count($evaluations),       // ✅ FIXED: Number of courses
                'total_courses' => count($evaluations)            // ✅ NEW: Same as total_evaluations for clarity
            ];
        })->filter()->values()->toArray(); // Remove null entries (employees with no evaluations in date range)

        // If no evaluations match the date filter, return early
        if (empty($detailedEvaluations)) {
            Log::warning('No evaluations found for courses assigned in specified date range');
            return $results;
        }

        // Send emails to each manager
        foreach ($allManagers as $manager) {
            try {

                Mail::send('emails.manager-evaluation-report', [
                    'manager' => $manager,
                    'employees' => $employees,
                    'subject' => $subject,
                    'customMessage' => $customMessage,
                    'evaluations' => $employees->flatMap->evaluations,
                    'detailedEvaluations' => $detailedEvaluations,  // ✅ Now includes overall_average and filtered by date range
                    'performanceLevels' => PerformanceLevel::getForFrontend(), // ✅ Include all performance levels for reference
                    'reportPeriod' => $reportPeriod // ✅ Add report period for email context
                ], function ($message) use ($manager, $subject) {
                    $message->to($manager['email'], $manager['name'])
                        ->subject($subject);
                });

                $results['success_count']++;
                $results['sent_to'][] = $manager;


            } catch (\Exception $e) {

                $results['failed_count']++;
                $results['failed_to'][] = [
                    'manager' => $manager,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}
