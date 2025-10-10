<?php

namespace App\Services;

use App\Models\CourseOnline;
use App\Models\LearningSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class UserAnalyticsService
{
    public function getUserAnalyticsData($request, array $filters): array
    {
        $dateFrom = $filters['date_from'] ?? Carbon::now()->subDays(30)->toDateString();
        $dateTo = $filters['date_to'] ?? Carbon::now()->toDateString();
        $userId = $filters['user_id'] ?? null;
        $courseId = $filters['course_id'] ?? null;

        $users = User::query()
            ->when($userId, fn($q) => $q->where('id', $userId))
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->limit(200)
            ->get();

        $courses = CourseOnline::select('id', 'name')->orderBy('name')->get();

        $userSummaries = $users->map(function($user) use ($dateFrom, $dateTo, $courseId) {
            $sessionsQuery = LearningSession::where('user_id', $user->id)
                ->whereBetween('session_start', [$dateFrom, $dateTo]);

            if ($courseId) {
                $sessionsQuery->where('course_online_id', $courseId);
            }

            $sessions = $sessionsQuery->get();

            $totalMinutes = 0;
            $totalAttention = 0;
            $sessionCount = $sessions->count();
            $firstSession = null;
            $lastSession = null;

            foreach ($sessions as $session) {
                $duration = $this->getActualSessionDuration($session->session_start, $session->session_end);
                $attention = $this->calculateSimulatedAttentionScore($session->session_start, $session->session_end, $duration);

                $totalMinutes += $duration;
                $totalAttention += $attention;

                $firstSession = $firstSession ? min($firstSession, $session->session_start) : $session->session_start;
                $lastSession = $lastSession ? max($lastSession, $session->session_start) : $session->session_start;
            }

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'total_minutes' => $totalMinutes,
                'average_attention' => $sessionCount > 0 ? round($totalAttention / $sessionCount, 1) : 0,
                'session_count' => $sessionCount,
                'first_activity' => $firstSession ? Carbon::parse($firstSession)->format('M d, Y') : null,
                'last_activity' => $lastSession ? Carbon::parse($lastSession)->format('M d, Y') : null,
                'engagement_level' => $this->calculateEngagementLevel($sessionCount > 0 ? $totalAttention / $sessionCount : 0),
            ];
        })->toArray();

        $timeSeries = LearningSession::whereBetween('session_start', [$dateFrom, $dateTo])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($courseId, fn($q) => $q->where('course_online_id', $courseId))
            ->groupBy(DB::raw('DATE(session_start)'))
            ->selectRaw('DATE(session_start) as date, COUNT(*) as sessions')
            ->orderBy('date')
            ->get()
            ->map(function($item) {
                return [
                    'date' => $item->date,
                    'sessions' => $item->sessions,
                ];
            })
            ->toArray();

        $topUsers = collect($userSummaries)
            ->sortByDesc('total_minutes')
            ->values()
            ->take(20)
            ->toArray();

        return [
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'user_id' => $userId,
                'course_id' => $courseId,
            ],
            'users' => $users,
            'courses' => $courses,
            'summaries' => $userSummaries,
            'timeSeries' => $timeSeries,
            'topUsers' => $topUsers,
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

    private function calculateEngagementLevel($attentionScore)
    {
        if ($attentionScore >= 80) return 'High';
        if ($attentionScore >= 60) return 'Medium';
        if ($attentionScore >= 40) return 'Low';
        return 'Very Low';
    }
}
