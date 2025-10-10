<?php

namespace App\Services;

use App\Models\LearningSession;
use App\Models\CourseOnline;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheatingDetectionService
{
    public function getCheatingDetectionData($request, array $filters): array
    {
        Log::info('🚀 === CHEATING DETECTION SERVICE START (REAL DATABASE FIELDS) ===', [
            'filters' => $filters,
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Step 1: Build database query using REAL table structure
        $query = LearningSession::with(['user', 'courseOnline', 'content'])
            ->whereNotNull('session_end') // Only get completed sessions
            ->orderBy('session_start', 'desc');

        Log::info('🗃️ Base database query created', [
            'conditions' => 'session_end IS NOT NULL (completed sessions only)',
            'includes' => ['user', 'courseOnline', 'content'],
            'order' => 'session_start DESC',
        ]);

        // Step 2: Apply user filters to query
        if (!empty($filters['course_id'])) {
            $query->where('course_online_id', $filters['course_id']);
            Log::info('🎯 Applied course filter to query', ['course_id' => $filters['course_id']]);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
            Log::info('👤 Applied user filter to query', ['user_id' => $filters['user_id']]);
        }

        // Step 3: Execute query and get real data
        $sessions = $query->get();

        Log::info('📊 Sessions retrieved from database', [
            'total_sessions_count' => $sessions->count(),
            'sample_session_fields' => $sessions->first() ? [
                'id' => $sessions->first()->id,
                'user_id' => $sessions->first()->user_id,
                // Check which REAL fields have data
                'has_total_duration_minutes' => !is_null($sessions->first()->total_duration_minutes),
                'has_video_watch_time' => !is_null($sessions->first()->video_watch_time),
                'has_video_skip_count' => !is_null($sessions->first()->video_skip_count),
                'has_seek_count' => !is_null($sessions->first()->seek_count),
                'has_attention_score' => !is_null($sessions->first()->attention_score),
                'has_cheating_score' => !is_null($sessions->first()->cheating_score),
                'is_suspicious_activity' => $sessions->first()->is_suspicious_activity,
                // Show actual values for debugging
                'actual_values' => [
                    'total_duration_minutes' => $sessions->first()->total_duration_minutes,
                    'video_watch_time' => $sessions->first()->video_watch_time,
                    'video_skip_count' => $sessions->first()->video_skip_count,
                    'seek_count' => $sessions->first()->seek_count,
                    'attention_score' => $sessions->first()->attention_score,
                    'cheating_score' => $sessions->first()->cheating_score,
                ]
            ] : null,
        ]);

        if ($sessions->count() === 0) {
            Log::warning('⚠️ No completed sessions found in database');
            return $this->getEmptyResponse($filters);
        }

        // Step 4: Process each session using REAL database data
        $suspiciousSessions = collect();
        $allCheatingData = [];
        $processedCount = 0;
        $suspiciousCount = 0;

        foreach ($sessions as $session) {
            $processedCount++;

            Log::info("🔍 Processing session {$processedCount}/{$sessions->count()}", [
                'session_id' => $session->id,
                'user_name' => $session->user->name ?? 'Unknown',
                'course_name' => $session->courseOnline->name ?? 'Unknown',
                'session_dates' => [
                    'start' => $session->session_start?->toDateTimeString(),
                    'end' => $session->session_end?->toDateTimeString(),
                ],
                'stored_database_values' => [
                    'total_duration_minutes' => $session->total_duration_minutes,
                    'video_watch_time' => $session->video_watch_time,
                    'video_total_duration' => $session->video_total_duration,
                    'video_skip_count' => $session->video_skip_count,
                    'seek_count' => $session->seek_count,
                    'pause_count' => $session->pause_count,
                    'clicks_count' => $session->clicks_count,
                    'attention_score' => $session->attention_score,
                    'cheating_score' => $session->cheating_score,
                    'is_suspicious_activity' => $session->is_suspicious_activity,
                ]
            ]);

            // Step 5: Extract REAL data from database fields
            $realSessionData = $this->extractRealDataFromDatabase($session);

            Log::info("📊 Real data extracted from database fields", [
                'session_id' => $session->id,
                'extracted_data' => $realSessionData,
            ]);

            // Step 6: Calculate cheating analysis using REAL data
            $cheatingAnalysis = $this->analyzeRealCheatingData($session, $realSessionData);

            Log::info("🚨 Cheating analysis completed", [
                'session_id' => $session->id,
                'analysis_result' => $cheatingAnalysis,
            ]);

            // Store for statistics
            $allCheatingData[] = $cheatingAnalysis;

            // Step 7: Check if session meets suspicious criteria
            $minCheatingScore = $filters['min_cheating_score'] ?? 50; // Lower threshold for testing

            Log::info("✅ Checking if session meets suspicious criteria", [
                'session_id' => $session->id,
                'calculated_cheating_score' => $cheatingAnalysis['cheating_score'],
                'min_required_score' => $minCheatingScore,
                'is_suspicious_flag' => $cheatingAnalysis['is_suspicious'],
                'database_suspicious_flag' => $session->is_suspicious_activity,
                'will_be_included' => $cheatingAnalysis['is_suspicious'] || $cheatingAnalysis['cheating_score'] >= $minCheatingScore,
            ]);

            if ($cheatingAnalysis['is_suspicious'] || $cheatingAnalysis['cheating_score'] >= $minCheatingScore) {
                $suspiciousCount++;

                // Step 8: Build session data for frontend using REAL values
                $sessionData = [
                    'id' => $session->id,
                    'user' => [
                        'id' => $session->user->id,
                        'name' => $session->user->name,
                        'email' => $session->user->email,
                    ],
                    'course' => $session->courseOnline->name,
                    'content_title' => $session->content->title ?? 'Course Content',
                    'session_start' => $session->session_start->format('M d, Y H:i'),
                    'session_end' => $session->session_end->format('H:i'),
                    'duration' => $this->formatDuration($realSessionData['duration_minutes']),
                    'duration_minutes' => $realSessionData['duration_minutes'],
                    'cheating_score' => $cheatingAnalysis['cheating_score'],
                    'cheating_risk' => $cheatingAnalysis['cheating_risk'],
                    // USE REAL DATABASE VALUES (not calculated)
                    'video_completion' => $realSessionData['video_completion_percentage'],
                    'video_watch_time' => $realSessionData['video_watch_time'],
                    'video_total_duration' => $realSessionData['video_total_duration'],
                    'skip_count' => $realSessionData['video_skip_count'],
                    'seek_count' => $realSessionData['seek_count'],
                    'attention_score' => $realSessionData['attention_score'],
                    'is_suspicious' => $cheatingAnalysis['is_suspicious'],
                    'cheating_reasons' => $cheatingAnalysis['reasons'],
                ];

                $suspiciousSessions->push($sessionData);

                Log::info("🚩 Session marked as suspicious and added to results", [
                    'session_id' => $session->id,
                    'suspicious_count' => $suspiciousCount,
                    'session_data' => $sessionData,
                ]);
            } else {
                Log::info("✅ Session is NOT suspicious - skipped", [
                    'session_id' => $session->id,
                    'cheating_score' => $cheatingAnalysis['cheating_score'],
                    'threshold' => $minCheatingScore,
                ]);
            }
        }

        Log::info("🎯 All sessions processed - Final Summary", [
            'total_sessions_processed' => $processedCount,
            'suspicious_sessions_found' => $suspiciousCount,
            'suspicious_percentage' => round(($suspiciousCount / max($processedCount, 1)) * 100, 1),
        ]);

        // Step 9: Sort, paginate and return results
        $suspiciousSessions = $suspiciousSessions->sortByDesc('cheating_score');
        $cheatingStats = $this->calculateStatsFromAnalysis($allCheatingData);

        // Paginate results
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

        $finalResult = [
            'suspiciousSessions' => $pagination,
            'cheatingStats' => $cheatingStats,
            'filters' => $filters,
            'users' => User::where('role', '!=', 'admin')->orderBy('name')->get(['id', 'name']),
            'courses' => CourseOnline::orderBy('name')->get(['id', 'name']),
        ];

        Log::info('🎉 === CHEATING DETECTION SERVICE COMPLETE ===', [
            'final_summary' => [
                'total_sessions_analyzed' => $sessions->count(),
                'suspicious_sessions_found' => $suspiciousSessions->count(),
                'paginated_sessions_returned' => count($finalResult['suspiciousSessions']['data']),
                'cheating_stats' => $cheatingStats,
            ],
        ]);

        return $finalResult;
    }

    /**
     * Extract REAL data from LearningSession database fields
     * COMMENT: Using your actual database columns from the model
     */
    private function extractRealDataFromDatabase(LearningSession $session): array
    {
        Log::info("📊 Extracting real data from database fields", ['session_id' => $session->id]);

        // STEP 1: Calculate duration from timestamps if total_duration_minutes is 0
        $calculatedDuration = 0;
        if ($session->total_duration_minutes && $session->total_duration_minutes > 0) {
            // Use stored duration if it exists
            $calculatedDuration = $session->total_duration_minutes;
            Log::info("⏱️ Using stored duration from database", [
                'session_id' => $session->id,
                'stored_duration' => $calculatedDuration
            ]);
        } else {
            // Calculate from session_start and session_end timestamps
            if ($session->session_start && $session->session_end) {
                $calculatedDuration = $session->session_start->diffInMinutes($session->session_end);
                Log::info("⏱️ Calculated duration from timestamps", [
                    'session_id' => $session->id,
                    'start' => $session->session_start->toDateTimeString(),
                    'end' => $session->session_end->toDateTimeString(),
                    'calculated_duration' => $calculatedDuration
                ]);
            }
        }

        // STEP 2: Extract REAL video interaction data from database
        $realData = [
            // Duration data
            'duration_minutes' => $calculatedDuration,

            // Video data (using your ACTUAL database fields)
            'video_watch_time' => $session->video_watch_time ?? 0, // From database field
            'video_total_duration' => $session->video_total_duration ?? 0, // From database field
            'video_completion_percentage' => $session->video_completion_percentage ?? 0, // From database field

            // Video interaction data (using your ACTUAL database fields)
            'video_skip_count' => $session->video_skip_count ?? 0, // From database field
            'video_replay_count' => $session->video_replay_count ?? 0, // From database field
            'seek_count' => $session->seek_count ?? 0, // From database field
            'pause_count' => $session->pause_count ?? 0, // From database field
            'clicks_count' => $session->clicks_count ?? 0, // From database field
            'fullscreen_count' => $session->fullscreen_count ?? 0, // From database field
            'speed_changes' => $session->speed_changes ?? 0, // From database field

            // Engagement scores (using your ACTUAL database fields)
            'attention_score' => $session->attention_score ?? 0, // From database field
            'cheating_score' => $session->cheating_score ?? 0, // From database field
            'is_suspicious_activity' => $session->is_suspicious_activity ?? false, // From database field
        ];

        Log::info("📊 Real data extraction complete", [
            'session_id' => $session->id,
            'extracted_data' => $realData,
            'data_sources' => [
                'duration_source' => $session->total_duration_minutes > 0 ? 'database_field' : 'calculated_from_timestamps',
                'video_data_source' => 'database_fields',
                'engagement_data_source' => 'database_fields',
            ]
        ]);

        return $realData;
    }

    /**
     * Analyze cheating using REAL database data
     * COMMENT: This uses the actual values stored in your database, not simulated ones
     */
    private function analyzeRealCheatingData(LearningSession $session, array $realData): array
    {
        Log::info("🕵️ Starting cheating analysis using REAL database data", [
            'session_id' => $session->id,
            'real_data_input' => $realData,
        ]);

        // STEP 1: If database already has cheating analysis, use it
        if ($realData['cheating_score'] > 0 || $realData['is_suspicious_activity'] === true) {
            Log::info("✅ Using existing cheating analysis from database", [
                'session_id' => $session->id,
                'database_cheating_score' => $realData['cheating_score'],
                'database_suspicious_flag' => $realData['is_suspicious_activity'],
            ]);

            return [
                'cheating_score' => $realData['cheating_score'],
                'cheating_risk' => $this->calculateRiskLevel($realData['cheating_score']),
                'is_suspicious' => $realData['is_suspicious_activity'],
                'reasons' => $this->generateReasonsFromScore($realData['cheating_score'], $realData),
                'data_source' => 'database_stored_analysis'
            ];
        }

        // STEP 2: Calculate new analysis using REAL data
        $cheatingScore = 0;
        $reasons = [];
        $isSuspicious = false;

        Log::info("🔍 Calculating new cheating analysis", ['session_id' => $session->id]);

        try {
            $sessionStart = Carbon::parse($session->session_start);

            // ANALYSIS 1: Duration-based detection
            Log::info("⏱️ Analyzing session duration", [
                'session_id' => $session->id,
                'duration_minutes' => $realData['duration_minutes']
            ]);

            if ($realData['duration_minutes'] > 0 && $realData['duration_minutes'] < 2) {
                $cheatingScore += 60; // High penalty for extremely short sessions
                $reasons[] = "Extremely short session ({$realData['duration_minutes']} minutes)";
                $isSuspicious = true;
                Log::warning("🚨 EXTREMELY SHORT SESSION DETECTED", [
                    'session_id' => $session->id,
                    'duration' => $realData['duration_minutes'],
                    'penalty' => 60
                ]);
            } elseif ($realData['duration_minutes'] < 5) {
                $cheatingScore += 30; // Medium penalty for very short sessions
                $reasons[] = "Very short session ({$realData['duration_minutes']} minutes)";
                Log::info("⚠️ Short session detected", [
                    'session_id' => $session->id,
                    'duration' => $realData['duration_minutes'],
                    'penalty' => 30
                ]);
            }

            if ($realData['duration_minutes'] > 180) { // 3+ hours
                $cheatingScore += 40;
                $reasons[] = "Extremely long session ({$realData['duration_minutes']} minutes)";
                $isSuspicious = true;
                Log::warning("🚨 EXTREMELY LONG SESSION DETECTED", [
                    'session_id' => $session->id,
                    'duration' => $realData['duration_minutes'],
                    'penalty' => 40
                ]);
            }

            // ANALYSIS 2: Video behavior analysis (using REAL database values)
            Log::info("🎥 Analyzing video behavior using REAL database values", [
                'session_id' => $session->id,
                'video_skip_count' => $realData['video_skip_count'],
                'seek_count' => $realData['seek_count'],
                'video_completion_percentage' => $realData['video_completion_percentage']
            ]);

            if ($realData['video_skip_count'] > 15) { // High skip count is suspicious
                $cheatingScore += 35;
                $reasons[] = "Excessive video skipping ({$realData['video_skip_count']} skips)";
                Log::warning("🚨 EXCESSIVE VIDEO SKIPPING", [
                    'session_id' => $session->id,
                    'skip_count' => $realData['video_skip_count'],
                    'penalty' => 35
                ]);
            } elseif ($realData['video_skip_count'] > 8) {
                $cheatingScore += 20;
                $reasons[] = "High video skipping ({$realData['video_skip_count']} skips)";
                Log::info("⚠️ High video skipping detected", [
                    'session_id' => $session->id,
                    'skip_count' => $realData['video_skip_count'],
                    'penalty' => 20
                ]);
            }

            if ($realData['seek_count'] > 25) { // Excessive seeking through content
                $cheatingScore += 30;
                $reasons[] = "Excessive video seeking ({$realData['seek_count']} seeks)";
                Log::warning("🚨 EXCESSIVE VIDEO SEEKING", [
                    'session_id' => $session->id,
                    'seek_count' => $realData['seek_count'],
                    'penalty' => 30
                ]);
            }

            if ($realData['video_completion_percentage'] < 20 && $realData['video_completion_percentage'] > 0) {
                $cheatingScore += 25;
                $reasons[] = "Very low video completion ({$realData['video_completion_percentage']}%)";
                Log::warning("🚨 VERY LOW VIDEO COMPLETION", [
                    'session_id' => $session->id,
                    'completion' => $realData['video_completion_percentage'],
                    'penalty' => 25
                ]);
            }

            // ANALYSIS 3: Attention score analysis (using REAL database value)
            if ($realData['attention_score'] > 0) {
                Log::info("🧠 Analyzing attention score from database", [
                    'session_id' => $session->id,
                    'attention_score' => $realData['attention_score']
                ]);

                if ($realData['attention_score'] < 30) {
                    $cheatingScore += 35;
                    $reasons[] = "Very low attention score ({$realData['attention_score']}%)";
                    $isSuspicious = true;
                    Log::warning("🚨 VERY LOW ATTENTION SCORE", [
                        'session_id' => $session->id,
                        'attention_score' => $realData['attention_score'],
                        'penalty' => 35
                    ]);
                } elseif ($realData['attention_score'] < 50) {
                    $cheatingScore += 20;
                    $reasons[] = "Low attention score ({$realData['attention_score']}%)";
                    Log::info("⚠️ Low attention score detected", [
                        'session_id' => $session->id,
                        'attention_score' => $realData['attention_score'],
                        'penalty' => 20
                    ]);
                }
            }

            // ANALYSIS 4: Time pattern analysis
            $hour = $sessionStart->hour;
            if ($hour >= 2 && $hour <= 5) { // 2 AM - 5 AM is suspicious
                $cheatingScore += 15;
                $reasons[] = "Very late night activity ({$hour}:00)";
                Log::info("🌙 Late night activity detected", [
                    'session_id' => $session->id,
                    'hour' => $hour,
                    'penalty' => 15
                ]);
            }

            // ANALYSIS 5: Combination patterns
            if ($realData['attention_score'] < 40 && $realData['duration_minutes'] < 5) {
                $cheatingScore += 25;
                $reasons[] = "Low attention with very fast completion";
                Log::warning("🚨 SUSPICIOUS COMBINATION: Low attention + Fast completion", [
                    'session_id' => $session->id,
                    'attention' => $realData['attention_score'],
                    'duration' => $realData['duration_minutes'],
                    'penalty' => 25
                ]);
            }

            // STEP 3: Finalize analysis
            $cheatingScore = max(0, min(100, $cheatingScore)); // Ensure score is between 0-100
            $cheatingRisk = $this->calculateRiskLevel($cheatingScore);
            $isSuspicious = $isSuspicious || $cheatingScore >= 70; // Mark suspicious if score is high

            Log::info('✅ Cheating analysis complete', [
                'session_id' => $session->id,
                'final_results' => [
                    'cheating_score' => $cheatingScore,
                    'is_suspicious' => $isSuspicious,
                    'risk_level' => $cheatingRisk,
                    'reasons_count' => count($reasons),
                    'reasons' => $reasons,
                    'data_source' => 'calculated_from_real_data'
                ]
            ]);

            return [
                'cheating_score' => $cheatingScore,
                'cheating_risk' => $cheatingRisk,
                'is_suspicious' => $isSuspicious,
                'reasons' => $reasons,
                'data_source' => 'calculated_from_real_data'
            ];

        } catch (\Exception $e) {
            Log::error('❌ Error in cheating analysis', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString(),
            ]);

            return [
                'cheating_score' => 10,
                'cheating_risk' => 'Low',
                'is_suspicious' => false,
                'reasons' => ['Error in analysis: ' . $e->getMessage()],
                'data_source' => 'error_fallback'
            ];
        }
    }

    /**
     * Generate reasons based on cheating score
     */
    private function generateReasonsFromScore(int $score, array $realData): array
    {
        $reasons = [];

        if ($realData['duration_minutes'] < 5) {
            $reasons[] = "Short session duration ({$realData['duration_minutes']} minutes)";
        }
        if ($realData['attention_score'] < 50 && $realData['attention_score'] > 0) {
            $reasons[] = "Low attention score ({$realData['attention_score']}%)";
        }
        if ($realData['video_skip_count'] > 10) {
            $reasons[] = "High video skipping ({$realData['video_skip_count']} skips)";
        }
        if ($realData['seek_count'] > 20) {
            $reasons[] = "Excessive video seeking ({$realData['seek_count']} seeks)";
        }
        if ($score >= 70) {
            $reasons[] = "High overall suspicious behavior score";
        }

        return $reasons;
    }

    /**
     * Calculate risk level from cheating score
     */
    private function calculateRiskLevel(int $cheatingScore): string
    {
        if ($cheatingScore >= 90) return 'Critical';
        if ($cheatingScore >= 70) return 'High';
        if ($cheatingScore >= 50) return 'Medium';
        if ($cheatingScore >= 30) return 'Low';
        return 'Minimal';
    }

    /**
     * Calculate statistics from all cheating analyses
     */
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
