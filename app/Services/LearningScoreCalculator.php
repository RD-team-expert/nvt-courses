<?php

namespace App\Services;

class LearningScoreCalculator
{
    /**
     * Calculate learning score using weighted formula
     * 
     * Traditional Courses: (completion_rate × 0.33) + (progress × 0.33) + (quiz × 0.33)
     * Online Courses: (completion_rate × 0.25) + (progress × 0.25) + (attention × 0.25) + (quiz × 0.25) - suspicious_penalty
     * 
     * @param float $completionRate
     * @param float $progressPercentage
     * @param float $attentionScore
     * @param float $quizScore
     * @param int $suspiciousActivities
     * @param int $totalSessions
     * @param string $courseType 'traditional' or 'online'
     * @return float Score between 0-100
     */
    public function calculate(
        float $completionRate,
        float $progressPercentage,
        float $attentionScore,
        float $quizScore,
        int $suspiciousActivities,
        int $totalSessions,
        string $courseType = 'online'
    ): float {
        // Handle null/missing values with defaults
        $completionRate = $completionRate ?? 0;
        $progressPercentage = $progressPercentage ?? 0;
        $attentionScore = $attentionScore ?? 0;
        $quizScore = $quizScore ?? 0;
        
        // Traditional courses: 3-component formula (no attention score)
        if ($courseType === 'traditional') {
            $completionWeighted = $completionRate * 0.3333;
            $progressWeighted = $progressPercentage * 0.3333;
            $quizWeighted = $quizScore * 0.3334; // Slightly higher to reach 100%
            
            $finalScore = $completionWeighted + $progressWeighted + $quizWeighted;
        }
        // Online courses: 4-component formula with attention score
        else {
            $completionWeighted = $completionRate * 0.25;
            $progressWeighted = $progressPercentage * 0.25;
            $attentionWeighted = $attentionScore * 0.25;
            $quizWeighted = $quizScore * 0.25;
            
            // Calculate suspicious penalty for online courses
            $suspiciousPenalty = 0;
            if ($totalSessions > 0) {
                $suspiciousRatio = $suspiciousActivities / $totalSessions;
                $suspiciousPenalty = $suspiciousRatio * 10;
            }
            
            $finalScore = $completionWeighted + $progressWeighted + $attentionWeighted + $quizWeighted - $suspiciousPenalty;
        }
        
        // Clamp to 0-100 range
        return max(0, min(100, $finalScore));
    }

    /**
     * Get attention score for a user's course
     * 
     * @param int $userId
     * @param int $courseId
     * @param string $courseType 'traditional' or 'online'
     * @return float
     */
    public function getAttentionScore(int $userId, int $courseId, string $courseType): float
    {
        // Traditional courses don't have attention scores
        if ($courseType === 'traditional') {
            return 65.0; // Default
        }
        
        // Get learning sessions for online courses
        $sessions = \DB::table('learning_sessions')
            ->where('user_id', $userId)
            ->where('course_online_id', $courseId)
            ->select('id', 'session_start', 'session_end', 'content_id', 
                    'video_skip_count', 'seek_count', 'pause_count', 
                    'video_replay_count', 'video_completion_percentage', 'active_playback_time')
            ->get();
        
        if ($sessions->isEmpty()) {
            return 65.0; // Default when no sessions
        }
        
        $attentionScores = [];
        
        foreach ($sessions as $session) {
            $duration = $this->getActualSessionDuration($session->session_start, $session->session_end);
            
            // Use simulated attention calculation (same as CourseOnlineReportController)
            $attentionResult = $this->calculateSimulatedAttentionScore(
                $session->session_start,
                $session->session_end,
                $duration,
                $session->content_id,
                $session
            );
            
            $attentionScores[] = $attentionResult['score'];
        }
        
        // Return average attention score
        return count($attentionScores) > 0 
            ? round(array_sum($attentionScores) / count($attentionScores), 1)
            : 65.0;
    }
    
    /**
     * Calculate actual session duration from start/end times
     * 
     * @param string|null $sessionStart
     * @param string|null $sessionEnd
     * @return float Duration in minutes
     */
    private function getActualSessionDuration($sessionStart, $sessionEnd): float
    {
        if (!$sessionStart || !$sessionEnd) {
            return 0;
        }
        
        try {
            $start = \Carbon\Carbon::parse($sessionStart);
            $end = \Carbon\Carbon::parse($sessionEnd);
            return max(0, $start->diffInMinutes($end));
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Calculate simulated attention score (reused from CourseOnlineReportController)
     * 
     * @param string|null $sessionStart
     * @param string|null $sessionEnd
     * @param float $calculatedDuration
     * @param int|null $contentId
     * @param object|null $sessionData
     * @return array
     */
    private function calculateSimulatedAttentionScore($sessionStart, $sessionEnd, $calculatedDuration, $contentId = null, $sessionData = null): array
    {
        $details = [];
        $isSuspicious = false;
        
        // Use ACTIVE playback time if available
        $activePlaybackMinutes = isset($sessionData->active_playback_time) && $sessionData->active_playback_time > 0
            ? ($sessionData->active_playback_time / 60)
            : $calculatedDuration;
        
        if ($activePlaybackMinutes <= 0) {
            return [
                'score' => 0,
                'is_suspicious' => true,
                'details' => ['No active playback time recorded'],
                'is_within_allowed_time' => false,
                'active_playback_minutes' => 0,
                'allowed_time_minutes' => 0,
            ];
        }
        
        try {
            $score = 0;
            
            // Get video duration
            $videoDurationMinutes = null;
            if ($contentId) {
                $videoDurationSeconds = \DB::table('module_content')
                    ->where('id', $contentId)
                    ->value('duration');
                
                if ($videoDurationSeconds) {
                    $videoDurationMinutes = $videoDurationSeconds / 60;
                }
            }
            
            // Calculate allowed time window (Duration × 2)
            $allowedTimeMinutes = $videoDurationMinutes ? ($videoDurationMinutes * 2) : 90;
            $isWithinAllowedTime = $activePlaybackMinutes <= $allowedTimeMinutes;
            
            // Active playback time matching (up to 30 points)
            if ($videoDurationMinutes && $videoDurationMinutes > 0) {
                if ($isWithinAllowedTime) {
                    if ($activePlaybackMinutes >= $videoDurationMinutes * 0.8) {
                        $score += 30;
                        $details[] = 'Good active playback time (+30)';
                    } elseif ($activePlaybackMinutes >= $videoDurationMinutes * 0.5) {
                        $score += 20;
                        $details[] = 'Acceptable active playback time (+20)';
                    } else {
                        $score += 10;
                        $details[] = 'Short active playback time (+10)';
                    }
                } else {
                    $score += 5;
                    $details[] = 'Exceeded allowed time window (+5)';
                    $isSuspicious = true;
                }
            } else {
                // Fallback: Duration-based scoring
                if ($activePlaybackMinutes >= 10 && $activePlaybackMinutes <= 45) {
                    $score += 20;
                    $details[] = 'Good session duration (+20)';
                } elseif ($activePlaybackMinutes >= 5 && $activePlaybackMinutes < 10) {
                    $score += 15;
                    $details[] = 'Short focused session (+15)';
                } elseif ($activePlaybackMinutes > 45 && $activePlaybackMinutes <= 60) {
                    $score += 10;
                    $details[] = 'Long session (+10)';
                } elseif ($activePlaybackMinutes > 60) {
                    $score += 5;
                    $details[] = 'Very long session (+5)';
                }
            }
            
            // Session completion bonus (5 points)
            if ($sessionEnd) {
                $score += 5;
                $details[] = 'Session completed (+5)';
            }
            
            // Pauses and rewinds bonus if within allowed time
            if ($sessionData && $isWithinAllowedTime) {
                $pauseCount = $sessionData->pause_count ?? 0;
                $replayCount = $sessionData->video_replay_count ?? 0;
                
                if ($pauseCount > 0 && $pauseCount <= 20) {
                    $score += 10;
                    $details[] = 'Normal pause behavior (+10)';
                }
                
                if ($replayCount > 0 && $replayCount <= 10) {
                    $score += 10;
                    $details[] = 'Replay behavior shows engagement (+10)';
                }
            }
            
            // Video completion bonus (up to 35 points)
            if ($sessionData && isset($sessionData->video_completion_percentage)) {
                $completionPct = $sessionData->video_completion_percentage;
                if ($completionPct >= 95) {
                    $score += 35;
                    $details[] = 'Full video completion (+35)';
                } elseif ($completionPct >= 80) {
                    $score += 25;
                    $details[] = 'High video completion (+25)';
                } elseif ($completionPct >= 60) {
                    $score += 15;
                    $details[] = 'Moderate video completion (+15)';
                } elseif ($completionPct >= 40) {
                    $score += 5;
                    $details[] = 'Low video completion (+5)';
                }
            }
            
            // Skip penalty
            if ($sessionData) {
                $skipCount = $sessionData->video_skip_count ?? 0;
                if ($skipCount >= 1) {
                    $score -= 30;
                    $isSuspicious = true;
                    $details[] = "PENALTY: Skip forward detected (-30)";
                }
            }
            
            // Final suspicious check
            if ($score < 30) {
                $isSuspicious = true;
            }
            
            $score = max(0, min(100, $score));
            
            return [
                'score' => $score,
                'is_suspicious' => $isSuspicious,
                'details' => $details,
                'is_within_allowed_time' => $isWithinAllowedTime,
                'active_playback_minutes' => $activePlaybackMinutes,
                'allowed_time_minutes' => $allowedTimeMinutes,
            ];
            
        } catch (\Exception $e) {
            return [
                'score' => 0,
                'is_suspicious' => true,
                'details' => ['Error calculating score: ' . $e->getMessage()],
                'is_within_allowed_time' => false,
                'active_playback_minutes' => 0,
                'allowed_time_minutes' => 0,
            ];
        }
    }

    /**
     * Get quiz score for a user
     * Aggregates both regular quiz attempts and module quiz results
     * 
     * @param int $userId
     * @param int|null $courseId
     * @return float
     */
    public function getQuizScore(int $userId, ?int $courseId = null): float
    {
        $regularAvgScore = 0;
        $moduleAvgScore = 0;
        
        // Get regular quiz scores
        $regularAttemptsQuery = \DB::table('quiz_attempts')
            ->where('user_id', $userId)
            ->whereNotNull('completed_at');
        
        if ($courseId) {
            $regularQuizIds = \DB::table('quizzes')
                ->where('course_online_id', $courseId)
                ->where('is_module_quiz', false)
                ->pluck('id');
            
            if ($regularQuizIds->isNotEmpty()) {
                $regularAttemptsQuery->whereIn('quiz_id', $regularQuizIds);
            }
        }
        
        $regularAttempts = $regularAttemptsQuery
            ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
            ->select([
                'quiz_attempts.quiz_id',
                \DB::raw('MAX(quiz_attempts.total_score) as best_score'),
                \DB::raw('MAX(quizzes.total_points) as total_points'),
            ])
            ->groupBy('quiz_attempts.quiz_id')
            ->get();
        
        if ($regularAttempts->isNotEmpty()) {
            $regularScorePercentages = $regularAttempts->map(function ($attempt) {
                if ($attempt->total_points > 0) {
                    return ($attempt->best_score / $attempt->total_points) * 100;
                }
                return 0;
            });
            
            $regularAvgScore = $regularScorePercentages->avg();
        }
        
        // Get module quiz scores
        $moduleResultsQuery = \DB::table('module_quiz_results')
            ->where('user_id', $userId);
        
        if ($courseId) {
            $moduleIds = \DB::table('course_modules')
                ->where('course_online_id', $courseId)
                ->where('has_quiz', true)
                ->pluck('id');
            
            if ($moduleIds->isNotEmpty()) {
                $moduleResultsQuery->whereIn('module_id', $moduleIds);
            }
        }
        
        $moduleResults = $moduleResultsQuery->get();
        
        if ($moduleResults->isNotEmpty()) {
            // Get best score per module
            $bestScoresPerModule = $moduleResults->groupBy('module_id')->map(function ($attempts) {
                return $attempts->max('score_percentage');
            });
            
            $moduleAvgScore = $bestScoresPerModule->avg();
        }
        
        // Calculate combined average
        if ($regularAvgScore > 0 && $moduleAvgScore > 0) {
            return round(($regularAvgScore + $moduleAvgScore) / 2, 1);
        } elseif ($regularAvgScore > 0) {
            return round($regularAvgScore, 1);
        } elseif ($moduleAvgScore > 0) {
            return round($moduleAvgScore, 1);
        }
        
        return 0.0;
    }
}
