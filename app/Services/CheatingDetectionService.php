<?php

namespace App\Services;

use App\Models\LearningSession;
use App\Models\UserContentProgress;
use App\Models\CourseOnline;
use App\Models\User;
use App\Models\ModuleContent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CheatingDetectionService
{
    public function getCheatingDetectionData($request, array $filters): array
    {


        // ✅ DEBUG: Get ALL sessions first to see what we have
        $allSessionsDebug = LearningSession::with(['user', 'courseOnline', 'content'])
            ->whereNotNull('session_end')
            ->orderBy('session_start', 'desc')
            ->get();


        // ✅ Get learning sessions with proper relationships
        $query = LearningSession::with(['user', 'courseOnline', 'content'])
            ->whereNotNull('session_end')
            ->orderBy('session_start', 'desc');

        if (!empty($filters['course_id'])) {
            $query->where('course_online_id', $filters['course_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        $sessions = $query->get();



        if ($sessions->count() === 0) {
            return $this->getEmptyResponse($filters);
        }

        // ✅ Group sessions by user+content combination
        $sessionGroups = $sessions->groupBy(function($session) {
            return $session->user_id . '_' . $session->content_id;
        });


        $suspiciousSessions = collect();
        $allCheatingData = [];
        $processedCount = 0;
        $suspiciousCount = 0;

        // ✅ Process each user+content group
        foreach ($sessionGroups as $userContentKey => $userSessions) {
            $processedCount++;

            // Use the latest session as representative
            $representativeSession = $userSessions->sortByDesc('session_start')->first();



            // ✅ FIXED: Extract real data using proper duration calculation
            $realData = $this->extractRealDataWithProperDuration($userSessions, $representativeSession);



            // ✅ Analyze data for cheating patterns
            $cheatingAnalysis = $this->analyzeRealData($representativeSession, $realData);



            $allCheatingData[] = $cheatingAnalysis;

            // ✅ FIXED: Lower minimum cheating score to show your sessions
            $minCheatingScore = $filters['min_cheating_score'] ?? 0; // ✅ Changed from 50 to 0



            // ✅ FIXED: Include sessions with any cheating score or suspicious activity
            if ($cheatingAnalysis['is_suspicious'] || $cheatingAnalysis['cheating_score'] >= $minCheatingScore) {
                $suspiciousCount++;

                $sessionData = [
                    'id' => $representativeSession->id,
                    'user' => [
                        'id' => $representativeSession->user->id,
                        'name' => $representativeSession->user->name,
                        'email' => $representativeSession->user->email,
                    ],
                    'course' => $representativeSession->courseOnline->name,
                    'content_title' => $representativeSession->content?->title ?? 'Unknown Content',
                    'session_start' => $representativeSession->session_start->format('M d, Y H:i'),
                    'session_end' => $representativeSession->session_end->format('H:i'),
                    'duration' => $realData['formatted_duration'],
                    'duration_minutes' => $realData['total_duration_minutes'],
                    'cheating_score' => $cheatingAnalysis['cheating_score'],
                    'cheating_risk' => $cheatingAnalysis['cheating_risk'],
                    'video_completion' => $realData['completion_percentage'],
                    'video_watch_time' => $realData['total_watch_time'],
                    'video_total_duration' => $realData['expected_duration_seconds'],
                    'skip_count' => $realData['skip_count'],
                    'seek_count' => $realData['seek_count'],
                    'attention_score' => $realData['attention_score'],
                    'is_suspicious' => $cheatingAnalysis['is_suspicious'],
                    'cheating_reasons' => $cheatingAnalysis['reasons'],
                    'sessions_count' => $userSessions->count(),
                ];

                $suspiciousSessions->push($sessionData);

            } else {

            }
        }



        $suspiciousSessions = $suspiciousSessions->sortByDesc('cheating_score');
        $cheatingStats = $this->calculateStatsFromAnalysis($allCheatingData);

        // Pagination
        $page = $request->get('page', 1);
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        $paginatedSessions = $suspiciousSessions->slice($offset, $perPage)->values();

        $pagination = [
            'current_page' => $page,
            'data' => $paginatedSessions,
            'from' => $offset + 1,
            'last_page' => ceil($suspiciousSessions->count() / $perPage),
            'per_page' => $perPage,
            'to' => min($offset + $perPage, $suspiciousSessions->count()),
            'total' => $suspiciousSessions->count(),
        ];



        return [
            'suspiciousSessions' => $pagination,
            'cheatingStats' => $cheatingStats,
            'filters' => $filters,
            'users' => User::where('role', '!=', 'admin')->orderBy('name')->get(['id', 'name']),
            'courses' => CourseOnline::orderBy('name')->get(['id', 'name']),
        ];
    }

    /**
     * ✅ FIXED: Extract real data with proper duration calculation from session_start/session_end
     */
    /**
     * ✅ FIXED: Extract real data with proper duration calculation (no negative values)
     */
    /**
     * ✅ FIXED: Use the EXACT same calculation logic from CourseAssignmentController
     */
    /**
     * ✅ FIXED: Only aggregate sessions for the SPECIFIC user+course combination
     */
    private function extractRealDataWithProperDuration($userSessions, $representativeSession): array
    {


        $totalDurationMinutes = 0;
        $totalWatchTime = 0;
        $totalSkipCount = 0;
        $totalSeekCount = 0;
        $sessionDetails = [];

        // ✅ CORRECT: Only process sessions for THIS specific user+content combination
        foreach ($userSessions as $session) {
            // ✅ VERIFY: Make sure we're only processing the right sessions
            if ($session->user_id !== $representativeSession->user_id ||
                $session->content_id !== $representativeSession->content_id) {

                continue; // Skip this session
            }

            $sessionDurationMinutes = 0;

            if ($session->session_start && $session->session_end) {
                try {
                    $start = \Carbon\Carbon::parse($session->session_start);
                    $end = \Carbon\Carbon::parse($session->session_end);

                    $sessionDurationMinutes = $start->diffInMinutes($end);

                    if ($sessionDurationMinutes < 0) {
                        $sessionDurationMinutes = 0;
                    }

                    $totalDurationMinutes += $sessionDurationMinutes;

                } catch (\Exception $e) {

                    $sessionDurationMinutes = 0;
                }
            }

            // Aggregate other data
            $totalWatchTime += $session->video_watch_time ?? 0;
            $totalSkipCount += $session->video_skip_count ?? 0;
            $totalSeekCount += $session->seek_count ?? 0;

            $sessionDetails[] = [
                'session_id' => $session->id,
                'user_id' => $session->user_id,
                'content_id' => $session->content_id,
                'course_id' => $session->course_online_id,
                'start' => $session->session_start?->toDateTimeString(),
                'end' => $session->session_end?->toDateTimeString(),
                'calculated_minutes' => $sessionDurationMinutes,
            ];


        }

        // ✅ Get completion for THIS specific user+content
        $userProgress = UserContentProgress::where('user_id', $representativeSession->user_id)
            ->where('content_id', $representativeSession->content_id)
            ->first();

        $completionPercentage = $userProgress?->completion_percentage ?? 0;

        // Calculate expected duration
        $content = $representativeSession->content;
        $expectedDurationMinutes = 0;
        $expectedDurationSeconds = 0;

        if ($content) {
            if ($content->content_type === 'video' && $content->video) {
                $expectedDurationSeconds = $content->video->duration ?? 0;
                $expectedDurationMinutes = $expectedDurationSeconds / 60;
            } elseif ($content->content_type === 'pdf' && $content->pdf_page_count) {
                $expectedDurationMinutes = $content->pdf_page_count * 2;
                $expectedDurationSeconds = $expectedDurationMinutes * 60;
            }
        }

        $attentionScore = $this->calculateAttentionScore(
            $totalDurationMinutes,
            $expectedDurationMinutes,
            $completionPercentage
        );

        $formattedTotalDuration = $this->formatTimeSpentForCheatingDetection($totalDurationMinutes);

        $realData = [
            'total_duration_minutes' => $totalDurationMinutes,
            'formatted_duration' => $formattedTotalDuration,
            'expected_duration_minutes' => $expectedDurationMinutes,
            'expected_duration_seconds' => $expectedDurationSeconds,
            'completion_percentage' => $completionPercentage,
            'total_watch_time' => $totalWatchTime,
            'skip_count' => $totalSkipCount,
            'seek_count' => $totalSeekCount,
            'total_sessions' => count($sessionDetails), // Only sessions that match scope
            'attention_score' => $attentionScore,
            'content_type' => $content?->content_type ?? 'unknown',
            'has_expected_duration' => $expectedDurationMinutes > 0,
        ];



        return $realData;
    }

    /**
     * ✅ COPY the exact formatTimeSpent method from CourseAssignmentController
     */
    private function formatTimeSpentForCheatingDetection(?int $minutes): string
    {
        if (!$minutes || $minutes <= 0) {
            return '0 min';
        }

        $days = floor($minutes / 1440);
        $hours = floor(($minutes % 1440) / 60);
        $mins = $minutes % 60;

        $result = [];

        if ($days > 0) {
            $result[] = $days . 'd';
        }
        if ($hours > 0) {
            $result[] = $hours . 'h';
        }
        if ($mins > 0) {
            $result[] = $mins . 'm';
        }

        return empty($result) ? '0 min' : implode(' ', $result);
    }

    /**
     * ✅ Format duration helper
     */
    /**
     * ✅ FIXED: Format duration properly (no 6h 0m for small durations)
     */
    private function formatDuration($minutes): string
    {
        if (!$minutes || $minutes <= 0) {
            return '0m';
        }

        // ✅ FIXED: For durations less than 60 minutes, show as minutes only
        if ($minutes < 60) {
            return round($minutes, 1) . 'm';  // Show one decimal place for precision
        }

        // ✅ FIXED: For 60+ minutes, calculate hours and minutes properly
        $hours = floor($minutes / 60);
        $remainingMinutes = round($minutes % 60);  // ✅ FIXED: Round the remaining minutes

        if ($remainingMinutes > 0) {
            return $hours . 'h ' . $remainingMinutes . 'm';
        } else {
            return $hours . 'h';
        }
    }

    /**
     * Calculate attention score without errors
     */
    private function calculateAttentionScore($totalDuration, $expectedDuration, $completion): float
    {
        $score = 50; // Base score

        // Factor 1: Completion rate
        if ($completion >= 90) {
            $score += 25;
        } elseif ($completion >= 70) {
            $score += 15;
        } elseif ($completion >= 50) {
            $score += 10;
        } elseif ($completion < 20) {
            $score -= 20;
        }

        // Factor 2: Time efficiency (only if we have expected duration)
        if ($expectedDuration > 0 && $totalDuration > 0) {
            $timeRatio = $totalDuration / $expectedDuration;

            if ($timeRatio >= 0.8 && $timeRatio <= 1.5) {
                $score += 20; // Good pacing
            } elseif ($timeRatio >= 0.5 && $timeRatio <= 2.0) {
                $score += 10; // Acceptable pacing
            } elseif ($timeRatio < 0.3) {
                $score -= 25; // Too fast
            } elseif ($timeRatio > 3.0) {
                $score -= 15; // Too slow
            }
        }

        // Factor 3: Consistency bonus
        if ($completion > 0 && $totalDuration > 0) {
            $consistency = min(100, ($completion / 100) * ($totalDuration / max($expectedDuration, 1)) * 100);
            if ($consistency > 80) {
                $score += 15;
            }
        }

        return max(0, min(100, $score));
    }

    /**
     * Analyze real data for cheating patterns
     */
    private function analyzeRealData(LearningSession $session, array $realData): array
    {


        $cheatingScore = 0;
        $reasons = [];
        $isSuspicious = false;

        try {
            // ANALYSIS 1: Duration vs Expected (most important)
            if ($realData['has_expected_duration'] && $realData['expected_duration_minutes'] > 0) {
                $timeEfficiency = $realData['total_duration_minutes'] / $realData['expected_duration_minutes'];



                if ($timeEfficiency < 0.1 && $realData['completion_percentage'] > 80) {
                    $cheatingScore += 70;
                    $reasons[] = "Impossibly fast: {$realData['completion_percentage']}% in {$realData['total_duration_minutes']} min (expected {$realData['expected_duration_minutes']} min)";
                    $isSuspicious = true;

                } elseif ($timeEfficiency < 0.3 && $realData['completion_percentage'] > 70) {
                    $cheatingScore += 50;
                    $reasons[] = "Very fast completion: {$realData['completion_percentage']}% in {$realData['total_duration_minutes']} min";
                    $isSuspicious = true;
                } elseif ($timeEfficiency < 0.5 && $realData['completion_percentage'] > 60) {
                    $cheatingScore += 30;
                    $reasons[] = "Fast completion pattern";
                }
            }

            // ANALYSIS 2: Watch time vs duration ratio
            if ($realData['total_duration_minutes'] > 0) {
                $watchRatio = $realData['total_watch_time'] / $realData['total_duration_minutes'];
                if ($watchRatio < 0.2 && $realData['completion_percentage'] > 50) {
                    $cheatingScore += 35;
                    $reasons[] = "Low actual watch time compared to claimed completion";
                }
            }

            // ANALYSIS 3: Skip analysis
            if ($realData['skip_count'] > 15) {
                $cheatingScore += 30;
                $reasons[] = "Excessive skipping ({$realData['skip_count']} skips across {$realData['total_sessions']} sessions)";
            } elseif ($realData['skip_count'] > 8) {
                $cheatingScore += 15;
                $reasons[] = "High skipping activity";
            }

            // ANALYSIS 4: Attention score analysis
            if ($realData['attention_score'] < 30) {
                $cheatingScore += 25;
                $reasons[] = "Very low attention score ({$realData['attention_score']}%)";
            } elseif ($realData['attention_score'] < 50) {
                $cheatingScore += 15;
                $reasons[] = "Low attention score ({$realData['attention_score']}%)";
            }

            // ANALYSIS 5: Zero duration with high completion
            if ($realData['total_duration_minutes'] == 0 && $realData['completion_percentage'] > 50) {
                $cheatingScore += 80;
                $reasons[] = "High completion ({$realData['completion_percentage']}%) with zero session time";
                $isSuspicious = true;
            }

            // Finalize
            $cheatingScore = max(0, min(100, $cheatingScore));
            $cheatingRisk = $this->calculateRiskLevel($cheatingScore, $isSuspicious);
            $isSuspicious = $isSuspicious || $cheatingScore >= 70;


            return [
                'cheating_score' => $cheatingScore,
                'cheating_risk' => $cheatingRisk,
                'is_suspicious' => $isSuspicious,
                'reasons' => $reasons,
                'data_source' => 'calculated_from_timestamps'
            ];

        } catch (\Exception $e) {


            return [
                'cheating_score' => 0,
                'cheating_risk' => 'Low',
                'is_suspicious' => false,
                'reasons' => ['Analysis error: ' . $e->getMessage()],
                'data_source' => 'error_fallback'
            ];
        }
    }

    // Helper methods
    private function calculateRiskLevel(int $cheatingScore, bool $isSuspicious = false): string
    {
        if ($cheatingScore >= 90) return 'Critical';
        if ($cheatingScore >= 70 || $isSuspicious) return 'High';
        if ($cheatingScore >= 50) return 'Medium';
        if ($cheatingScore >= 30) return 'Low';
        return 'Minimal';
    }

    private function calculateStatsFromAnalysis(array $allAnalyses): array
    {
        if (empty($allAnalyses)) {
            return [
                'total_incidents' => 0,
                'high_risk_sessions' => 0,
                'critical_risk_sessions' => 0,
                'average_cheating_score' => 0,
                'total_sessions_analyzed' => 0,
                'cheating_rate_percentage' => 0,
            ];
        }

        $totalIncidents = count(array_filter($allAnalyses, fn($data) => $data['is_suspicious']));
        $highRiskSessions = count(array_filter($allAnalyses, fn($data) => $data['cheating_score'] >= 70));
        $criticalRiskSessions = count(array_filter($allAnalyses, fn($data) => $data['cheating_risk'] === 'Critical'));

        $totalScore = array_sum(array_column($allAnalyses, 'cheating_score'));
        $avgScore = round($totalScore / count($allAnalyses), 1);
        $cheatingRate = round(($totalIncidents / count($allAnalyses)) * 100, 1);

        return [
            'total_incidents' => $totalIncidents,
            'high_risk_sessions' => $highRiskSessions,
            'critical_risk_sessions' => $criticalRiskSessions,
            'average_cheating_score' => $avgScore,
            'total_sessions_analyzed' => count($allAnalyses),
            'cheating_rate_percentage' => $cheatingRate,
        ];
    }

    private function getEmptyResponse(array $filters): array
    {
        return [
            'suspiciousSessions' => [
                'current_page' => 1,
                'data' => [],
                'from' => 0,
                'last_page' => 1,
                'per_page' => 20,
                'to' => 0,
                'total' => 0,
            ],
            'cheatingStats' => [
                'total_incidents' => 0,
                'high_risk_sessions' => 0,
                'critical_risk_sessions' => 0,
                'average_cheating_score' => 0,
                'total_sessions_analyzed' => 0,
                'cheating_rate_percentage' => 0,
            ],
            'filters' => $filters,
            'users' => User::where('role', '!=', 'admin')->orderBy('name')->get(['id', 'name']),
            'courses' => CourseOnline::orderBy('name')->get(['id', 'name']),
        ];
    }
}
