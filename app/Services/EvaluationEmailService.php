<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EvaluationEmailService
{
    /**
     * Send evaluation report to managers
     */

    public function sendEvaluationReport(array $managers, $employees, string $subject, string $customMessage = ''): array
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

        // ✅ NEW: Prepare detailed evaluation data with overall average
        $detailedEvaluations = $employees->map(function ($employee) {
            $evaluations = $employee->evaluations->map(function ($evaluation) {
                return [
                    'id' => $evaluation->id,
                    'course' => $evaluation->course ? $evaluation->course->name : 'Unknown Course',
                    'total_score' => $evaluation->total_score,
                    'incentive_amount' => $evaluation->incentive_amount,
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

            // ✅ NEW: Calculate overall average across ALL evaluations
            $allScores = collect($evaluations)->flatMap(function ($eval) {
                return collect($eval['detailed_scores'])->pluck('score');
            });

            $overallAverage = $allScores->isNotEmpty()
                ? round($allScores->average(), 2)
                : 0;

            // ✅ NEW: Also calculate course averages
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
                'overall_average' => $overallAverage,              // ✅ NEW: Overall average
                'course_averages' => $courseAverages->toArray(),  // ✅ NEW: Individual course averages
                'total_evaluations' => count($evaluations),       // ✅ NEW: Total count
                'total_scores_count' => $allScores->count()       // ✅ NEW: Total individual scores
            ];
        })->toArray();

        // Send emails to each manager
        foreach ($allManagers as $manager) {
            try {

                Mail::send('emails.manager-evaluation-report', [
                    'manager' => $manager,
                    'employees' => $employees,
                    'subject' => $subject,
                    'customMessage' => $customMessage,
                    'evaluations' => $employees->flatMap->evaluations,
                    'detailedEvaluations' => $detailedEvaluations  // ✅ Now includes overall_average
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
