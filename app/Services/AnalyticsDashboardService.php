<?php

namespace App\Services;

use App\Models\CourseOnline;
use App\Models\CourseOnlineAssignment;
use App\Models\LearningSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsDashboardService
{
    public function getDateFilters($request): array
    {
        return [
            'date_from' => $request->get('date_from', Carbon::now()->subDays(30)->toDateString()),
            'date_to' => $request->get('date_to', Carbon::now()->toDateString()),
        ];
    }

    public function getDashboardAnalytics(array $filters): array
    {
        return [
            'overview' => $this->getOverviewStats($filters['date_from'], $filters['date_to']),
            'coursePerformance' => $this->getCoursePerformanceStats(),
            'userEngagement' => $this->getUserEngagementStats($filters['date_from'], $filters['date_to']),
            'contentAnalytics' => $this->getContentAnalytics(),
            'suspiciousActivity' => $this->getSuspiciousActivityStats($filters['date_from'], $filters['date_to']),
            'timeSeriesData' => $this->getTimeSeriesData($filters['date_from'], $filters['date_to']),
        ];
    }

    private function getOverviewStats(string $dateFrom, string $dateTo): array
    {
        $totalRealMinutes = 0;
        $suspiciousCount = 0;
        $sessions = LearningSession::whereBetween('session_start', [$dateFrom, $dateTo])->get();

        foreach ($sessions as $session) {
            $duration = $this->getActualSessionDuration($session->session_start, $session->session_end);
            $totalRealMinutes += $duration;

            $attention = $this->calculateSimulatedAttentionScore($session->session_start, $session->session_end, $duration);
            $cheatingData = $this->calculateSimulatedCheatingData($session, $duration, $attention);

            if ($cheatingData['is_suspicious']) {
                $suspiciousCount++;
            }
        }

        return [
            'total_courses' => CourseOnline::count(),
            'total_users' => User::where('role', '!=', 'admin')->count(),
            'total_assignments' => CourseOnlineAssignment::count(),
            'active_learners' => CourseOnlineAssignment::where('status', 'in_progress')->distinct('user_id')->count(),
            'completed_assignments' => CourseOnlineAssignment::where('status', 'completed')->count(),
            'total_learning_hours' => round($totalRealMinutes / 60, 1),
            'average_completion_rate' => round(CourseOnlineAssignment::avg('progress_percentage') ?? 0, 1),
            'suspicious_activities' => $suspiciousCount,
        ];
    }

    private function getCoursePerformanceStats(): array
    {
        return CourseOnline::withCount(['assignments'])
            ->get()
            ->map(function($course) {
                $assignments = CourseOnlineAssignment::where('course_online_id', $course->id)->get();
                $completionRate = $assignments->count() > 0
                    ? round(($assignments->where('status', 'completed')->count() / $assignments->count()) * 100, 1)
                    : 0;

                $courseSessions = LearningSession::where('course_online_id', $course->id)->get();
                $totalRealMinutes = 0;
                $suspiciousCount = 0;
                $totalAttention = 0;

                foreach ($courseSessions as $session) {
                    $duration = $this->getActualSessionDuration($session->session_start, $session->session_end);
                    $attention = $this->calculateSimulatedAttentionScore($session->session_start, $session->session_end, $duration);
                    $cheatingData = $this->calculateSimulatedCheatingData($session, $duration, $attention);

                    $totalRealMinutes += $duration;
                    $totalAttention += $attention;

                    if ($cheatingData['is_suspicious']) {
                        $suspiciousCount++;
                    }
                }

                $avgSessionDuration = $courseSessions->count() > 0 ? $totalRealMinutes / $courseSessions->count() : 0;
                $avgAttention = $courseSessions->count() > 0 ? $totalAttention / $courseSessions->count() : 0;

                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'total_enrollments' => $course->assignments_count,
                    'completion_rate' => $completionRate,
                    'average_session_duration' => round($avgSessionDuration, 1),
                    'engagement_score' => round($avgAttention, 1),
                    'cheating_incidents' => $suspiciousCount,
                ];
            })
            ->sortByDesc('completion_rate')
            ->values()
            ->toArray();
    }

    private function getUserEngagementStats(string $dateFrom, string $dateTo): array
    {
        $sessions = LearningSession::whereBetween('session_start', [$dateFrom, $dateTo])->get();

        $engagementLevels = [
            'High' => 0,
            'Medium' => 0,
            'Low' => 0,
            'Very Low' => 0,
        ];

        foreach ($sessions as $session) {
            $duration = $this->getActualSessionDuration($session->session_start, $session->session_end);
            $attention = $this->calculateSimulatedAttentionScore($session->session_start, $session->session_end, $duration);
            $level = $this->calculateEngagementLevel($attention);
            $engagementLevels[$level]++;
        }

        return [
            'daily_active_users' => LearningSession::whereBetween('session_start', [$dateFrom, $dateTo])
                ->groupBy(DB::raw('DATE(session_start)'))
                ->selectRaw('DATE(session_start) as date, COUNT(DISTINCT user_id) as users')
                ->orderBy('date')
                ->get()
                ->map(fn($item) => [
                    'date' => $item->date,
                    'users' => $item->users,
                ])
                ->toArray(),
            'engagement_levels' => collect($engagementLevels)->map(fn($count, $level) => [
                'level' => $level,
                'count' => $count,
            ])->values()->toArray(),
        ];
    }

    private function getContentAnalytics(): array
    {
        return DB::table('course_modules')
            ->join('course_online', 'course_modules.course_online_id', '=', 'course_online.id')
            ->leftJoin('learning_sessions', 'course_online.id', '=', 'learning_sessions.course_online_id')
            ->select([
                'course_modules.id',
                'course_modules.name as title',
                'course_online.name as course_name',
                DB::raw('COUNT(learning_sessions.id) as session_count'),
                DB::raw('COUNT(CASE WHEN learning_sessions.session_end IS NOT NULL THEN 1 END) as completed_sessions'),
            ])
            ->groupBy(['course_modules.id', 'course_modules.name', 'course_online.name'])
            ->orderBy('session_count', 'desc')
            ->limit(20)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title,
                    'content_type' => 'module',
                    'course_name' => $item->course_name,
                    'session_count' => $item->session_count,
                    'avg_attention' => rand(60, 85),
                    'suspicious_count' => max(0, round($item->session_count * 0.1)),
                ];
            })
            ->toArray();
    }

    private function getSuspiciousActivityStats(string $dateFrom, string $dateTo): array
    {
        $sessions = LearningSession::whereBetween('session_start', [$dateFrom, $dateTo])->get();

        $totalIncidents = 0;
        $highRiskSessions = 0;
        $affectedCourses = [];
        $repeatOffenders = [];

        foreach ($sessions as $session) {
            $duration = $this->getActualSessionDuration($session->session_start, $session->session_end);
            $attention = $this->calculateSimulatedAttentionScore($session->session_start, $session->session_end, $duration);
            $cheatingData = $this->calculateSimulatedCheatingData($session, $duration, $attention);

            if ($cheatingData['is_suspicious']) {
                $totalIncidents++;
                $affectedCourses[$session->course_online_id] = true;

                if (!isset($repeatOffenders[$session->user_id])) {
                    $repeatOffenders[$session->user_id] = 0;
                }
                $repeatOffenders[$session->user_id]++;
            }

            if ($cheatingData['cheating_score'] > 80) {
                $highRiskSessions++;
            }
        }

        return [
            'total_incidents' => $totalIncidents,
            'high_risk_sessions' => $highRiskSessions,
            'affected_courses' => count($affectedCourses),
            'repeat_offenders' => count(array_filter($repeatOffenders, fn($count) => $count > 1)),
        ];
    }

    private function getTimeSeriesData(string $dateFrom, string $dateTo): array
    {
        $learningActivity = LearningSession::whereBetween('session_start', [$dateFrom, $dateTo])
            ->groupBy(DB::raw('DATE(session_start)'))
            ->selectRaw('DATE(session_start) as date, COUNT(*) as sessions')
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                $daySessions = LearningSession::whereDate('session_start', $item->date)->get();
                $totalMinutes = 0;

                foreach ($daySessions as $session) {
                    $duration = $this->getActualSessionDuration($session->session_start, $session->session_end);
                    $totalMinutes += $duration;
                }

                return [
                    'date' => $item->date,
                    'sessions' => $item->sessions,
                    'total_minutes' => $totalMinutes,
                ];
            })
            ->toArray();

        return [
            'learning_activity' => $learningActivity,
            'course_completions' => CourseOnlineAssignment::where('status', 'completed')
                ->whereBetween('completed_at', [$dateFrom, $dateTo])
                ->groupBy(DB::raw('DATE(completed_at)'))
                ->selectRaw('DATE(completed_at) as date, COUNT(*) as completions')
                ->orderBy('date')
                ->get()
                ->map(fn($item) => [
                    'date' => $item->date,
                    'completions' => $item->completions,
                ])
                ->toArray(),
        ];
    }

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

    private function calculateEngagementLevel($attentionScore)
    {
        if ($attentionScore >= 80) return 'High';
        if ($attentionScore >= 60) return 'Medium';
        if ($attentionScore >= 40) return 'Low';
        return 'Very Low';
    }
}
