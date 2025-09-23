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

        Log::info('=== Starting email sending ===');
        Log::info('Managers to email: ' . json_encode($managers));
        Log::info('Employees data: ' . $employees->count());

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

        Log::info('Total managers to email: ' . count($allManagers));

        if (empty($allManagers)) {
            Log::warning('No managers found to send emails to');
            return $results;
        }

        // Send emails to each manager
        foreach ($allManagers as $manager) {
            try {
                Log::info("Sending email to {$manager['name']} ({$manager['email']})");

                Mail::send('emails.manager-evaluation-report', [
                    'manager' => $manager,
                    'employees' => $employees,
                    'subject' => $subject,              // ✅ FIXED: Added this line
                    'customMessage' => $customMessage,
                    'evaluations' => $employees->flatMap->evaluations
                ], function ($message) use ($manager, $subject) {
                    $message->to($manager['email'], $manager['name'])
                        ->subject($subject);
                });

                $results['success_count']++;
                $results['sent_to'][] = $manager;

                Log::info("Email sent successfully to {$manager['email']}");

            } catch (\Exception $e) {
                Log::error("Single manager email failed", [
                    'manager' => $manager,
                    'error' => $e->getMessage()
                ]);

                $results['failed_count']++;
                $results['failed_to'][] = [
                    'manager' => $manager,
                    'error' => $e->getMessage()
                ];
            }
        }

        Log::info('Email sending completed', $results);
        return $results;
    }
}
