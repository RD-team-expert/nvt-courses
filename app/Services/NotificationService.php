<?php

namespace App\Services;

use App\Models\User;
use App\Models\LearningSession;
use App\Mail\SuspiciousActivityWarning;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function sendWarningEmail(User $user, $request, $currentUser): array
    {
        // Check admin authorization
        if (!$currentUser || !in_array($currentUser->role, ['admin', 'superadmin'])) {
            throw new \Exception('Unauthorized to send warning emails');
        }

        $warningData = $this->generateWarningData($user, $request);

        // Send email
        Mail::to($user->email)->send(new SuspiciousActivityWarning($user, $warningData, $currentUser));




        return [
            'message' => "Warning email sent to {$user->name} successfully!",
            'warning_data' => $warningData,
        ];
    }

    private function generateWarningData(User $user, $request): array
    {
        // If specific session data provided, use it
        if ($request->has('session_data')) {
            $sessionData = $request->get('session_data');
            return [
                'course_name' => $sessionData['course_name'] ?? 'Unknown Course',
                'risk_score' => $sessionData['risk_score'] ?? 75,
                'risk_level' => $sessionData['risk_level'] ?? 'Medium',
                'session_date' => $sessionData['session_date'] ?? now()->format('M d, Y'),
                'duration' => $sessionData['duration'] ?? '30 minutes',
                'reasons' => $sessionData['reasons'] ?? ['Unusual learning pattern detected'],
                'recommendations' => $this->getWarningRecommendations($sessionData['risk_score'] ?? 75),
            ];
        }

        // Otherwise, find the most recent suspicious session
        $recentSession = LearningSession::where('user_id', $user->id)
            ->with('courseOnline')
            ->orderBy('session_start', 'desc')
            ->first();

        if (!$recentSession) {
            // Generate generic warning data
            return [
                'course_name' => 'Multiple Courses',
                'risk_score' => 65,
                'risk_level' => 'Medium',
                'session_date' => now()->format('M d, Y'),
                'duration' => 'Various',
                'reasons' => [
                    'Multiple sessions with unusual patterns detected',
                    'Learning behavior requires review'
                ],
                'recommendations' => $this->getWarningRecommendations(65),
            ];
        }

        // Calculate data for the recent session
        $realDuration = $this->getActualSessionDuration($recentSession->session_start, $recentSession->session_end);
        $simulatedAttention = $this->calculateSimulatedAttentionScore($recentSession->session_start, $recentSession->session_end, $realDuration);
        $cheatingData = $this->calculateSimulatedCheatingData($recentSession, $realDuration, $simulatedAttention);

        return [
            'course_name' => $recentSession->courseOnline->name ?? 'Unknown Course',
            'risk_score' => $cheatingData['cheating_score'],
            'risk_level' => $cheatingData['cheating_risk'],
            'session_date' => $recentSession->session_start->format('M d, Y'),
            'duration' => $this->formatDuration($realDuration),
            'reasons' => $cheatingData['reasons'],
            'recommendations' => $this->getWarningRecommendations($cheatingData['cheating_score']),
        ];
    }

    private function getWarningRecommendations(int $riskScore): array
    {
        if ($riskScore >= 90) {
            return [
                'Schedule a meeting with your learning coordinator',
                'Review course materials at a comfortable pace',
                'Ensure you have a quiet, distraction-free learning environment',
                'Contact support if you experienced technical difficulties',
                'Consider spreading learning sessions over multiple days'
            ];
        } elseif ($riskScore >= 70) {
            return [
                'Take regular breaks during longer learning sessions',
                'Focus on one topic at a time for better retention',
                'Ensure stable internet connection during online sessions',
                'Review any sections you may have skipped',
                'Contact support if you need assistance with course content'
            ];
        } elseif ($riskScore >= 50) {
            return [
                'Try to maintain consistent learning schedules',
                'Engage actively with course materials and exercises',
                'Take notes to improve focus and retention',
                'Reach out if you find any content too challenging',
                'Consider joining study groups or discussions'
            ];
        } else {
            return [
                'Continue your excellent learning progress',
                'Share your successful learning strategies with peers',
                'Consider becoming a mentor for other learners',
                'Provide feedback to help improve course content'
            ];
        }
    }



    // Helper methods
    private function getActualSessionDuration($sessionStart, $sessionEnd)
    {
        if (!$sessionStart || !$sessionEnd) {
            return 0;
        }

        try {
            $start = Carbon::parse($sessionStart);
            $end = Carbon::parse($sessionEnd);
            return max(0, $start->diffInMinutes($end));
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function calculateSimulatedAttentionScore($sessionStart, $sessionEnd, $calculatedDuration)
    {
        if ($calculatedDuration <= 0) {
            return 0;
        }

        try {
            $start = Carbon::parse($sessionStart);
            $score = 70;

            if ($calculatedDuration >= 10 && $calculatedDuration <= 45) {
                $score += 20;
            } elseif ($calculatedDuration >= 5 && $calculatedDuration < 10) {
                $score += 10;
            } elseif ($calculatedDuration > 60) {
                $score -= 15;
            } else {
                $score -= 25;
            }

            $hour = $start->hour;
            if ($hour >= 9 && $hour <= 11) {
                $score += 15;
            } elseif ($hour >= 14 && $hour <= 16) {
                $score += 10;
            } elseif ($hour >= 22 || $hour <= 6) {
                $score -= 20;
            }

            if ($start->isWeekday()) {
                $score += 10;
            } else {
                $score -= 5;
            }

            $score += rand(-8, 8);

            return max(25, min(100, $score));

        } catch (\Exception $e) {
            return 65;
        }
    }

    private function calculateSimulatedCheatingData($session, $realDuration, $simulatedAttention)
    {
        $cheatingScore = 0;
        $reasons = [];
        $isSuspicious = false;

        try {
            $start = Carbon::parse($session->session_start);

            if ($realDuration > 0 && $realDuration < 5) {
                $cheatingScore += 40;
                $reasons[] = "Extremely short session (< 5 minutes)";
                $isSuspicious = true;
            } elseif ($realDuration < 10) {
                $cheatingScore += 20;
                $reasons[] = "Very short session (< 10 minutes)";
            }

            if ($realDuration > 120) {
                $cheatingScore += 25;
                $reasons[] = "Extremely long session (> 2 hours)";
                $isSuspicious = true;
            } elseif ($realDuration > 90) {
                $cheatingScore += 15;
                $reasons[] = "Very long session (> 90 minutes)";
            }

            $hour = $start->hour;
            if ($hour >= 23 || $hour <= 5) {
                $cheatingScore += 15;
                $reasons[] = "Late night/early morning activity ({$hour}:00)";
            }

            if ($start->isWeekend() && $realDuration < 10) {
                $cheatingScore += 10;
                $reasons[] = "Weekend rush pattern";
            }

            if ($simulatedAttention < 30) {
                $cheatingScore += 30;
                $reasons[] = "Very low attention score ({$simulatedAttention}%)";
                $isSuspicious = true;
            } elseif ($simulatedAttention < 50) {
                $cheatingScore += 15;
                $reasons[] = "Low attention score ({$simulatedAttention}%)";
            }

            if ($simulatedAttention < 40 && $realDuration < 15) {
                $cheatingScore += 35;
                $reasons[] = "Low attention with fast completion";
                $isSuspicious = true;
            }

            $cheatingScore = max(0, min(100, $cheatingScore));

            return [
                'cheating_score' => $cheatingScore,
                'cheating_risk' => $this->calculateCheatingRisk($cheatingScore, $isSuspicious),
                'is_suspicious' => $isSuspicious,
                'reasons' => $reasons,
            ];

        } catch (\Exception $e) {
            return [
                'cheating_score' => 25,
                'cheating_risk' => 'Low',
                'is_suspicious' => false,
                'reasons' => ['Error in analysis'],
            ];
        }
    }

    private function calculateCheatingRisk($cheatingScore, $isSuspicious)
    {
        if ($cheatingScore >= 90 || $isSuspicious) return 'Critical';
        if ($cheatingScore >= 70) return 'High';
        if ($cheatingScore >= 50) return 'Medium';
        if ($cheatingScore >= 30) return 'Low';
        return 'Minimal';
    }

    private function formatDuration($minutes)
    {
        if (!$minutes || $minutes == 0) {
            return '0 min';
        }

        if ($minutes < 60) {
            return round($minutes) . ' min';
        }

        $hours = floor($minutes / 60);
        $mins = round($minutes % 60);

        return $mins > 0 ? $hours . 'h ' . $mins . 'm' : $hours . 'h';
    }
}
