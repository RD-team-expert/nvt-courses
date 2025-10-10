<?php

namespace App\Services;

use App\Models\LearningSession;
use App\Models\UserContentProgress;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SessionAnalyticsService
{
    public function getSessionDetailsData($sessionId): array
    {
        $session = LearningSession::with(['user', 'courseOnline'])
            ->findOrFail($sessionId);

        $realDuration = $this->getActualSessionDuration($session->session_start, $session->session_end);
        $simulatedAttention = $this->calculateSimulatedAttentionScore($session->session_start, $session->session_end, $realDuration);
        $cheatingData = $this->calculateSimulatedCheatingData($session, $realDuration, $simulatedAttention);

        $userSessions = LearningSession::where('user_id', $session->user_id)
            ->where('id', '!=', $session->id)
            ->orderBy('session_start', 'desc')
            ->limit(10)
            ->get();

        $userPatterns = $this->analyzeUserPatterns($session->user_id, $userSessions);

        $courseProgress = UserContentProgress::where('user_id', $session->user_id)
            ->where('course_online_id', $session->course_online_id)
            ->get();

        $sessionTimeline = $this->generateSessionTimeline($session, $realDuration, $cheatingData);
        $fraudAnalysis = $this->generateDetailedFraudAnalysis($session, $cheatingData, $userPatterns);

        return [
            'session' => [
                'id' => $session->id,
                'user' => [
                    'id' => $session->user->id,
                    'name' => $session->user->name,
                    'email' => $session->user->email,
                    'employee_code' => $session->user->employee_code ?? '',
                ],
                'course' => [
                    'id' => $session->courseOnline->id,
                    'name' => $session->courseOnline->name,
                ],
                'session_start' => $session->session_start,
                'session_end' => $session->session_end,
                'duration_minutes' => $realDuration,
                'formatted_duration' => $this->formatDuration($realDuration),
            ],
            'analytics' => [
                'attention_score' => $simulatedAttention,
                'cheating_score' => $cheatingData['cheating_score'],
                'cheating_risk' => $cheatingData['cheating_risk'],
                'is_suspicious' => $cheatingData['is_suspicious'],
                'reasons' => $cheatingData['reasons'],
                'video_completion' => $cheatingData['video_completion'],
                'video_watch_time' => $cheatingData['video_watch_time'],
                'skip_count' => $cheatingData['skip_count'],
                'seek_count' => $cheatingData['seek_count'],
                'pause_count' => $cheatingData['pause_count'],
                'clicks_count' => $cheatingData['clicks_count'],
            ],
            'userPatterns' => $userPatterns,
            'courseProgress' => $courseProgress->map(function($progress) {
                return [
                    'content_id' => $progress->content_id,
                    'completion_percentage' => $progress->completion_percentage,
                    'is_completed' => $progress->is_completed,
                    'watch_time' => $progress->watch_time,
                ];
            }),
            'sessionTimeline' => $sessionTimeline,
            'fraudAnalysis' => $fraudAnalysis,
            'recentSessions' => $userSessions->map(function($recentSession) {
                $duration = $this->getActualSessionDuration($recentSession->session_start, $recentSession->session_end);
                $attention = $this->calculateSimulatedAttentionScore($recentSession->session_start, $recentSession->session_end, $duration);

                return [
                    'id' => $recentSession->id,
                    'date' => $recentSession->session_start,
                    'duration' => $this->formatDuration($duration),
                    'attention_score' => $attention,
                    'course_name' => $recentSession->courseOnline->name ?? 'Unknown',
                ];
            }),
        ];
    }

    private function generateSessionTimeline($session, $realDuration, $cheatingData): array
    {
        $timeline = [];
        $start = Carbon::parse($session->session_start);
        $intervalMinutes = max(1, $realDuration / 20);

        for ($i = 0; $i < min(20, $realDuration); $i++) {
            $time = $start->copy()->addMinutes($i * $intervalMinutes);
            $attention = rand(40, 90);
            $events = [];

            if ($cheatingData['cheating_score'] > 70 && rand(1, 100) <= 30) {
                $suspiciousEvents = ['Fast Forward', 'Skip Content', 'Multiple Seeks', 'Long Pause'];
                $events[] = $suspiciousEvents[array_rand($suspiciousEvents)];
                $activity = 'Suspicious';
                $attention = rand(20, 50);
            } else {
                $activity = 'Learning';
                if (rand(1, 100) <= 40) {
                    $normalEvents = ['Video Play', 'Pause', 'Note Taking', 'Progress Save'];
                    $events[] = $normalEvents[array_rand($normalEvents)];
                }
            }

            $timeline[] = [
                'time' => $time->format('H:i:s'),
                'minute' => $i * $intervalMinutes,
                'activity' => $activity,
                'attention_score' => $attention,
                'events' => $events,
                'is_suspicious' => $activity === 'Suspicious',
            ];
        }

        return $timeline;
    }

    private function analyzeUserPatterns($userId, $userSessions): array
    {
        $totalSessions = $userSessions->count();
        $shortSessions = 0;
        $lateNightSessions = 0;
        $weekendSessions = 0;
        $totalDuration = 0;

        foreach ($userSessions as $userSession) {
            $duration = $this->getActualSessionDuration($userSession->session_start, $userSession->session_end);
            $start = Carbon::parse($userSession->session_start);
            $totalDuration += $duration;

            if ($duration < 10) $shortSessions++;
            if ($start->hour >= 22 || $start->hour <= 6) $lateNightSessions++;
            if ($start->isWeekend()) $weekendSessions++;
        }

        return [
            'total_sessions' => $totalSessions,
            'short_sessions_percentage' => $totalSessions > 0 ? round(($shortSessions / $totalSessions) * 100, 1) : 0,
            'late_night_percentage' => $totalSessions > 0 ? round(($lateNightSessions / $totalSessions) * 100, 1) : 0,
            'weekend_percentage' => $totalSessions > 0 ? round(($weekendSessions / $totalSessions) * 100, 1) : 0,
            'average_duration' => $totalSessions > 0 ? round($totalDuration / $totalSessions, 1) : 0,
            'risk_indicators' => [
                'frequent_short_sessions' => $shortSessions / $totalSessions > 0.5,
                'night_owl_pattern' => $lateNightSessions / $totalSessions > 0.3,
                'weekend_cramming' => $weekendSessions / $totalSessions > 0.7,
            ],
        ];
    }

    private function generateDetailedFraudAnalysis($session, $cheatingData, $userPatterns): array
    {
        $indicators = [];
        $recommendations = [];
        $riskLevel = 'Low';

        if ($cheatingData['cheating_score'] >= 90) {
            $riskLevel = 'Critical';
            $indicators[] = 'Extremely high cheating score (' . $cheatingData['cheating_score'] . ')';
            $recommendations[] = 'Immediate investigation required - consider course invalidation';
        } elseif ($cheatingData['cheating_score'] >= 70) {
            $riskLevel = 'High';
            $indicators[] = 'High cheating score (' . $cheatingData['cheating_score'] . ')';
            $recommendations[] = 'Send warning to user and schedule follow-up';
        }

        if ($cheatingData['skip_count'] > 10) {
            $indicators[] = 'Excessive content skipping (' . $cheatingData['skip_count'] . ' skips)';
            $recommendations[] = 'Review course structure - content may be too long or irrelevant';
        }

        if ($userPatterns['risk_indicators']['frequent_short_sessions']) {
            $indicators[] = 'Pattern of very short sessions (' . $userPatterns['short_sessions_percentage'] . '% under 10 minutes)';
            $recommendations[] = 'User may need learning time management training';
        }

        if ($userPatterns['risk_indicators']['night_owl_pattern']) {
            $indicators[] = 'Frequent late-night learning (' . $userPatterns['late_night_percentage'] . '% after 10 PM)';
            $recommendations[] = 'Check if user has work-life balance issues affecting learning quality';
        }

        return [
            'overall_risk' => $riskLevel,
            'fraud_indicators' => $indicators,
            'recommendations' => $recommendations,
            'investigation_priority' => $cheatingData['cheating_score'] > 80 ? 'High' : ($cheatingData['cheating_score'] > 60 ? 'Medium' : 'Low'),
            'action_required' => $cheatingData['cheating_score'] > 90,
        ];
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

            $videoCompletion = min(100, max(10, $simulatedAttention + ($realDuration * 2)));
            $videoWatchTime = round($realDuration * 0.7);
            $videoTotalDuration = round($realDuration * 1.2);

            $skipCount = $cheatingScore > 50 ? rand(5, 15) : rand(0, 3);
            $seekCount = $cheatingScore > 60 ? rand(10, 30) : rand(2, 8);
            $pauseCount = $cheatingScore > 40 ? rand(1, 3) : rand(3, 10);
            $clicksCount = $cheatingScore > 70 ? rand(50, 150) : rand(10, 50);

            return [
                'cheating_score' => $cheatingScore,
                'cheating_risk' => $this->calculateCheatingRisk($cheatingScore, $isSuspicious),
                'is_suspicious' => $isSuspicious,
                'reasons' => $reasons,
                'video_completion' => $videoCompletion,
                'video_watch_time' => $videoWatchTime,
                'video_total_duration' => $videoTotalDuration,
                'skip_count' => $skipCount,
                'seek_count' => $seekCount,
                'pause_count' => $pauseCount,
                'clicks_count' => $clicksCount,
            ];

        } catch (\Exception $e) {
            return [
                'cheating_score' => 25,
                'cheating_risk' => 'Low',
                'is_suspicious' => false,
                'reasons' => ['Error in analysis'],
                'video_completion' => 75,
                'video_watch_time' => $realDuration,
                'video_total_duration' => $realDuration,
                'skip_count' => 2,
                'seek_count' => 5,
                'pause_count' => 3,
                'clicks_count' => 20,
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
