<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseOnline;
use App\Models\CourseOnlineAssignment;
use App\Models\User;
use App\Models\LearningSession;
use App\Models\UserContentProgress;
use App\Models\Department;
use App\Services\CsvExportService;
use App\Services\DepartmentPerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Carbon\Carbon;

class CourseOnlineReportController extends Controller
{
    protected $csvExportService;
    protected $departmentPerformanceService;

    public function __construct(CsvExportService $csvExportService, DepartmentPerformanceService $departmentPerformanceService)
    {
        $this->csvExportService = $csvExportService;
        $this->departmentPerformanceService = $departmentPerformanceService;
    }

    /**
     * 🔍 COMPLETE FIXED: Progress Report with REAL duration and simulated attention
     */
    public function progressReport(Request $request)
    {


        try {
            $filters = $request->only(['course_id', 'status', 'date_from', 'date_to', 'user_id', 'department_id']);

            // ✅ Check database tables
            $this->debugDatabaseTables();

            // Get basic assignments
            $query = CourseOnlineAssignment::query()
                ->with(['courseOnline', 'user.department', 'assignedBy'])
                ->join('users', 'course_online_assignments.user_id', '=', 'users.id')
                ->join('course_online', 'course_online_assignments.course_online_id', '=', 'course_online.id')
                ->leftJoin('departments', 'users.department_id', '=', 'departments.id');

            // Apply filters
            if (!empty($filters['course_id'])) {
                $query->where('course_online_assignments.course_online_id', $filters['course_id']);
            }
            if (!empty($filters['status'])) {
                $query->where('course_online_assignments.status', $filters['status']);
            }
            if (!empty($filters['user_id'])) {
                $query->where('course_online_assignments.user_id', $filters['user_id']);
            }
            if (!empty($filters['date_from'])) {
                $query->whereDate('course_online_assignments.assigned_at', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->whereDate('course_online_assignments.assigned_at', '<=', $filters['date_to']);
            }
            if (!empty($filters['department_id'])) {
                $query->where('users.department_id', $filters['department_id']);
            }

            $query->select([
                'course_online_assignments.id',
                'course_online_assignments.user_id',
                'course_online_assignments.course_online_id',
                'users.name as user_name',
                'users.email as user_email',
                'users.employee_code',
                'departments.name as department_name',
                'course_online.name as course_name',
                'course_online.difficulty_level',
                'course_online_assignments.status',
                'course_online_assignments.progress_percentage',
                'course_online_assignments.assigned_at',
                'course_online_assignments.started_at',
                'course_online_assignments.completed_at',
            ]);

            $assignments = $query->orderByDesc('course_online_assignments.assigned_at')
                ->paginate(15)
                ->withQueryString();


            // ✅ COMPLETE FIXED: Process each assignment with REAL duration and SIMULATED attention
            $assignments->getCollection()->transform(function ($assignment, $index) {


                try {
                    // ✅ Get sessions with detailed logging
                    $sessions = DB::table('learning_sessions')
                        ->where('user_id', $assignment->user_id)
                        ->where('course_online_id', $assignment->course_online_id)
                        ->select('id', 'session_start', 'session_end', 'total_duration_minutes', 'attention_score', 'is_suspicious_activity')
                        ->get();



                    // ✅ COMPLETE FIXED: Process with simulated attention
                    $sessionData = $this->processSessionsWithSimulatedAttention($sessions, $assignment->id);

                    // Get progress data
                    $progressData = DB::table('user_content_progress')
                        ->where('user_id', $assignment->user_id)
                        ->where('course_online_id', $assignment->course_online_id)
                        ->selectRaw('
                            COUNT(*) as total_content_accessed,
                            COUNT(CASE WHEN is_completed = 1 THEN 1 END) as completed_content,
                            COALESCE(SUM(CASE WHEN watch_time > 0 THEN watch_time ELSE 0 END), 0) as total_watch_time
                        ')
                        ->first();



                    // ✅ Set calculated values using REAL durations and SIMULATED attention
                    $assignment->total_sessions = $sessionData['total_sessions'];
                    $assignment->total_time_spent = $sessionData['total_time_spent']; // ✅ REAL duration
                    $assignment->avg_attention_score = $sessionData['avg_attention_score']; // ✅ SIMULATED attention
                    $assignment->suspicious_sessions = $sessionData['suspicious_sessions'];
                    $assignment->total_content_accessed = $progressData->total_content_accessed ?? 0;
                    $assignment->completed_content = $progressData->completed_content ?? 0;
                    $assignment->total_watch_time = $progressData->total_watch_time ?? 0;

                    // ✅ FIXED: Calculate metrics with REAL data and SIMULATED attention
                    $assignment->engagement_level = $this->calculateEngagementLevel($assignment->avg_attention_score);
                    $assignment->performance_rating = $this->calculatePerformanceRating(
                        $assignment->progress_percentage ?? 0,
                        $assignment->avg_attention_score,
                        $assignment->suspicious_sessions,
                        $assignment->total_sessions
                    );
                    $assignment->formatted_time_spent = $this->formatDuration($assignment->total_time_spent);



                } catch (\Exception $e) {

                    // Set reasonable defaults on error
                    $assignment->total_sessions = 0;
                    $assignment->total_time_spent = 0;
                    $assignment->avg_attention_score = 65; // Reasonable default
                    $assignment->suspicious_sessions = 0;
                    $assignment->total_content_accessed = 0;
                    $assignment->completed_content = 0;
                    $assignment->total_watch_time = 0;
                    $assignment->engagement_level = 'Medium';
                    $assignment->performance_rating = 'Average';
                    $assignment->formatted_time_spent = '0 min';
                }

                return $assignment;
            });

            // Get filter options
            $courses = CourseOnline::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
            $users = User::where('role', '!=', 'admin')->select('id', 'name', 'email')->orderBy('name')->get();
            $departments = Department::where('is_active', true)->select('id', 'name')->orderBy('name')->get();



            // ✅ FIXED: Calculate stats with REAL durations and SIMULATED attention
            $stats = $this->calculateRealStatsWithSimulatedAttention();



            return Inertia::render('Admin/Reports/CourseOnlineProgress', [
                'assignments' => $assignments,
                'courses' => $courses,
                'users' => $users,
                'departments' => $departments,
                'filters' => $filters,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {


            throw $e;
        }
    }

    /**
     * 🔍 FIXED: Learning Sessions Report with simulated attention
     */
    public function learningSessionsReport(Request $request)
    {

        try {
            $filters = $request->only(['course_id', 'user_id', 'date_from', 'date_to', 'suspicious_only', 'department_id']);

            $query = LearningSession::query()
                ->join('users', 'learning_sessions.user_id', '=', 'users.id')
                ->join('course_online', 'learning_sessions.course_online_id', '=', 'course_online.id')
                ->leftJoin('module_content', 'learning_sessions.content_id', '=', 'module_content.id')
                ->leftJoin('departments', 'users.department_id', '=', 'departments.id');

            // Apply filters
            if (!empty($filters['course_id'])) {
                $query->where('learning_sessions.course_online_id', $filters['course_id']);
            }
            if (!empty($filters['user_id'])) {
                $query->where('learning_sessions.user_id', $filters['user_id']);
            }
            if (!empty($filters['date_from'])) {
                $query->whereDate('learning_sessions.session_start', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->whereDate('learning_sessions.session_start', '<=', $filters['date_to']);
            }
            if (!empty($filters['department_id'])) {
                $query->where('users.department_id', $filters['department_id']);
            }

            $sessions = $query->select([
                'learning_sessions.id',
                'users.name as user_name',
                'users.email as user_email',
                'users.employee_code',
                'departments.name as department_name',
                'course_online.name as course_name',
                'module_content.title as content_title',
                'module_content.content_type',
                'learning_sessions.session_start',
                'learning_sessions.session_end',
                'learning_sessions.total_duration_minutes',
                'learning_sessions.attention_score',
                'learning_sessions.is_suspicious_activity',
            ])
                ->orderByDesc('learning_sessions.session_start')
                ->paginate(20)
                ->withQueryString();



            // ✅ FIXED: Transform data with REAL duration and SIMULATED attention
            $sessions->getCollection()->transform(function ($session) {
                $calculatedDuration = $this->getActualSessionDuration($session->session_start, $session->session_end);
                $storedDuration = $session->total_duration_minutes ?? 0;

                // ✅ Get full session data for skip/seek tracking
                $fullSessionData = DB::table('learning_sessions')
                    ->where('id', $session->id)
                    ->select('video_skip_count', 'seek_count', 'pause_count', 'video_replay_count', 'video_completion_percentage', 'content_id')
                    ->first();
                
                $contentId = $fullSessionData->content_id ?? null;

                // ✅ Generate simulated attention with video duration and skip/seek data
                $attentionResult = $this->calculateSimulatedAttentionScore(
                    $session->session_start,
                    $session->session_end,
                    $calculatedDuration,
                    $contentId,
                    $fullSessionData
                );
                
                $simulatedAttention = $attentionResult['score'];
                $isSuspicious = $attentionResult['is_suspicious'];

                return [
                    'id' => $session->id,
                    'user_name' => $session->user_name,
                    'user_email' => $session->user_email,
                    'employee_code' => $session->employee_code ?? 'N/A',
                    'department' => $session->department_name ?? 'N/A',
                    'course_name' => $session->course_name,
                    'content_title' => $session->content_title ?? 'Course Overview',
                    'content_type' => ucfirst($session->content_type ?? 'general'),
                    'session_start' => Carbon::parse($session->session_start)->format('M d, Y H:i'),
                    'session_end' => $session->session_end ? Carbon::parse($session->session_end)->format('H:i') : 'Active',
                    'stored_duration' => $this->formatDuration($storedDuration),
                    'calculated_duration' => $this->formatDuration($calculatedDuration),
                    'duration' => $this->formatDuration($calculatedDuration),
                    'duration_minutes' => $calculatedDuration,
                    'stored_attention' => $session->attention_score ?? 0,
                    'simulated_attention' => $simulatedAttention,
                    'attention_score' => $simulatedAttention,
                    'engagement_level' => $this->calculateEngagementLevel($simulatedAttention),
                    'is_suspicious' => $isSuspicious,
                    'session_status' => $session->session_end ? 'Completed' : 'Active',
                    'performance_rating' => $this->calculateSessionPerformance($calculatedDuration, $simulatedAttention, $isSuspicious),
                    'score_details' => $attentionResult['details'] ?? [],
                ];
            });

            // Apply suspicious filter after transformation if needed
            if (!empty($filters['suspicious_only']) && $filters['suspicious_only'] === '1') {
                $sessions->getCollection()->filter(function ($session) {
                    return $session['is_suspicious'] === true;
                });
            }

            $courses = CourseOnline::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
            $users = User::where('role', '!=', 'admin')->select('id', 'name', 'email')->orderBy('name')->get();
            $departments = Department::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

            // ✅ FIXED: Session statistics with REAL durations and SIMULATED attention
            $sessionStats = $this->calculateSessionStatsWithSimulatedAttention();


            return Inertia::render('Admin/Reports/LearningSessionsReport', [
                'sessions' => $sessions,
                'courses' => $courses,
                'users' => $users,
                'departments' => $departments,
                'filters' => $filters,
                'stats' => $sessionStats,
            ]);

        } catch (\Exception $e) {

            throw $e;
        }
    }

    /**
     * 🔍 FIXED: User Performance Report with simulated attention
     */
    public function userPerformanceReport(Request $request)
    {

        try {
            $filters = $request->only(['user_id', 'course_id', 'date_from', 'date_to', 'department_id']);

            $query = User::query()
                ->where('role', '!=', 'admin')
                ->with(['department']);

            if (!empty($filters['user_id'])) {
                $query->where('id', $filters['user_id']);
            }

            if (!empty($filters['department_id'])) {
                $query->where('department_id', $filters['department_id']);
            }

            $users = $query->paginate(15)->withQueryString();



            // ✅ FIXED: Transform user data with REAL calculations and SIMULATED attention
            $users->getCollection()->transform(function ($user) use ($filters) {


                // Get assignments with filters
                $assignmentQuery = DB::table('course_online_assignments')->where('user_id', $user->id);
                if (!empty($filters['course_id'])) {
                    $assignmentQuery->where('course_online_id', $filters['course_id']);
                }
                if (!empty($filters['date_from'])) {
                    $assignmentQuery->whereDate('assigned_at', '>=', $filters['date_from']);
                }
                if (!empty($filters['date_to'])) {
                    $assignmentQuery->whereDate('assigned_at', '<=', $filters['date_to']);
                }

                $assignmentStats = $assignmentQuery
                    ->selectRaw('
                        COUNT(*) as total_assignments,
                        COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_assignments,
                        AVG(COALESCE(progress_percentage, 0)) as avg_progress
                    ')
                    ->first();

                // ✅ FIXED: Get session stats with REAL durations and SIMULATED attention
                $sessionQuery = DB::table('learning_sessions')->where('user_id', $user->id);
                if (!empty($filters['course_id'])) {
                    $sessionQuery->where('course_online_id', $filters['course_id']);
                }

                $rawSessions = $sessionQuery->select(
                    'id', 'session_start', 'session_end', 'attention_score', 'is_suspicious_activity',
                    'video_skip_count', 'seek_count', 'pause_count', 'video_replay_count',
                    'video_completion_percentage', 'content_id'
                )->get();

                $totalSessions = $rawSessions->count();
                $totalRealMinutes = 0;
                $simulatedAttentionScores = [];
                $suspiciousSessions = 0;

                foreach ($rawSessions as $session) {
                    $duration = $this->getActualSessionDuration($session->session_start, $session->session_end);
                    $totalRealMinutes += $duration;

                    // ✅ Generate simulated attention with video duration and skip/seek data
                    $attentionResult = $this->calculateSimulatedAttentionScore(
                        $session->session_start,
                        $session->session_end,
                        $duration,
                        $session->content_id,
                        $session
                    );
                    $simulatedAttentionScores[] = $attentionResult['score'];

                    // Count as suspicious based on new calculation
                    if ($attentionResult['is_suspicious']) {
                        $suspiciousSessions++;
                    }
                }

                $avgSimulatedAttention = count($simulatedAttentionScores) > 0
                    ? array_sum($simulatedAttentionScores) / count($simulatedAttentionScores)
                    : 0;

                $completionRate = $assignmentStats->total_assignments > 0
                    ? round(($assignmentStats->completed_assignments / $assignmentStats->total_assignments) * 100, 1)
                    : 0;

                // ✅ Get comprehensive quiz performance for this user
                $quizPerformance = $this->calculateUserQuizPerformance($user->id, $filters['course_id'] ?? null);

                $performanceData = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_code' => $user->employee_code ?? 'N/A',
                    'department' => $user->department->name ?? 'N/A',
                    'total_assignments' => $assignmentStats->total_assignments ?? 0,
                    'completed_assignments' => $assignmentStats->completed_assignments ?? 0,
                    'completion_rate' => $completionRate,
                    'avg_progress' => round($assignmentStats->avg_progress ?? 0, 1),
                    'total_sessions' => $totalSessions,
                    'total_learning_hours' => round($totalRealMinutes / 60, 1),
                    'avg_attention_score' => round($avgSimulatedAttention, 1),
                    'suspicious_sessions' => $suspiciousSessions,
                    'engagement_level' => $this->calculateEngagementLevel($avgSimulatedAttention),
                    'performance_rating' => $this->calculateUserPerformanceRating(
                        $completionRate,
                        $assignmentStats->avg_progress ?? 0,
                        $avgSimulatedAttention,
                        $quizPerformance['avg_quiz_score'], // ✅ Pass quiz score as separate 25% component
                        $suspiciousSessions,
                        $totalSessions
                    ),
                    'risk_level' => $this->calculateRiskLevel($suspiciousSessions, $totalSessions),
                    // ✅ New comprehensive quiz performance data
                    'quiz_performance' => $quizPerformance,
                ];



                return $performanceData;
            });

            $courses = CourseOnline::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
            $allUsers = User::where('role', '!=', 'admin')->select('id', 'name', 'email')->orderBy('name')->get();
            $departments = Department::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

            return Inertia::render('Admin/Reports/UserPerformanceReport', [
                'users' => $users,
                'courses' => $courses,
                'allUsers' => $allUsers,
                'departments' => $departments,
                'filters' => $filters,
            ]);

        } catch (\Exception $e) {

            throw $e;
        }
    }

    // =====================================
    // 🔧 NEW HELPER METHODS
    // =====================================

    /**
     * 🔧 UPDATED: Calculate attention score based on session behavior
     * Score starts at 0 and is completely earned through proper behavior
     *
     * @param string|null $sessionStart Session start time
     * @param string|null $sessionEnd Session end time
     * @param float $calculatedDuration Calculated duration in minutes
     * @param int|null $contentId Content ID for video duration lookup
     * @param object|null $sessionData Session data with skip/seek counts
     * @return array ['score' => int, 'is_suspicious' => bool, 'details' => array]
     */
    private function calculateSimulatedAttentionScore($sessionStart, $sessionEnd, $calculatedDuration, $contentId = null, $sessionData = null)
    {
        $details = [];
        $isSuspicious = false;
        
        if ($calculatedDuration <= 0) {
            return ['score' => 0, 'is_suspicious' => true, 'details' => ['No duration recorded']];
        }

        try {
            // ✅ Base score starts at 0 (completely earned)
            $score = 0;
            
            // ✅ Get video duration for this content
            $videoDurationMinutes = null;
            if ($contentId) {
                $videoDurationSeconds = DB::table('module_content')
                    ->where('id', $contentId)
                    ->value('duration');
                
                if ($videoDurationSeconds) {
                    $videoDurationMinutes = $videoDurationSeconds / 60;
                }
            }
            
            // ✅ Video duration matching (up to 30 points)
            if ($videoDurationMinutes && $videoDurationMinutes > 0) {
                $durationDiff = abs($calculatedDuration - $videoDurationMinutes);
                
                if ($durationDiff <= 2) {
                    // Perfect match (within 2 minutes)
                    $score += 30;
                    $details[] = 'Perfect duration match (+30)';
                } elseif ($calculatedDuration >= $videoDurationMinutes * 0.8 && $calculatedDuration <= $videoDurationMinutes * 1.5) {
                    // Good match (80%-150% of video duration)
                    $score += 20;
                    $details[] = 'Good duration match (+20)';
                } elseif ($calculatedDuration >= $videoDurationMinutes * 0.5) {
                    // Acceptable (at least 50% of video)
                    $score += 10;
                    $details[] = 'Acceptable duration (+10)';
                } else {
                    $details[] = 'Duration too short (no bonus)';
                }
                
                // ✅ Suspicious activity detection based on video duration
                if ($calculatedDuration < ($videoDurationMinutes * 0.3)) {
                    $isSuspicious = true;
                    $details[] = 'SUSPICIOUS: Duration less than 30% of video';
                }
                if ($calculatedDuration > ($videoDurationMinutes * 3)) {
                    $isSuspicious = true;
                    $details[] = 'SUSPICIOUS: Duration more than 3x video length';
                }
            } else {
                // Fallback: Duration-based scoring without video reference (up to 20 points)
                if ($calculatedDuration >= 10 && $calculatedDuration <= 45) {
                    $score += 20;
                    $details[] = 'Good session duration (+20)';
                } elseif ($calculatedDuration >= 5 && $calculatedDuration < 10) {
                    $score += 15;
                    $details[] = 'Short focused session (+15)';
                } elseif ($calculatedDuration > 45 && $calculatedDuration <= 60) {
                    $score += 10;
                    $details[] = 'Long session (+10)';
                } elseif ($calculatedDuration > 60) {
                    $score += 5;
                    $details[] = 'Very long session (+5)';
                } else {
                    $details[] = 'Very short session (no duration bonus)';
                }
            }
            
            // ✅ Session completion bonus (5 points)
            if ($sessionEnd) {
                $score += 5;
                $details[] = 'Session completed (+5)';
            }
            
            // ✅ Engagement bonuses (up to 20 points)
            if ($sessionData) {
                // Pause count indicates engagement
                $pauseCount = $sessionData->pause_count ?? 0;
                if ($pauseCount > 0 && $pauseCount <= 10) {
                    $score += 10;
                    $details[] = 'Good pause behavior (+10)';
                } elseif ($pauseCount > 10) {
                    $score += 5;
                    $details[] = 'Excessive pausing (+5)';
                }
                
                // Replay count indicates attention to detail
                $replayCount = $sessionData->video_replay_count ?? 0;
                if ($replayCount > 0 && $replayCount <= 5) {
                    $score += 10;
                    $details[] = 'Replay behavior (+10)';
                } elseif ($replayCount > 5) {
                    $score += 5;
                    $details[] = 'Excessive replays (+5)';
                }
            }
            
            // ✅ STRICT Skip/Seek penalties
            if ($sessionData) {
                $skipCount = $sessionData->video_skip_count ?? 0;
                $seekCount = $sessionData->seek_count ?? 0;
                
                // ONE skip forward = immediate major penalty
                if ($skipCount >= 1) {
                    $score -= 50;
                    $isSuspicious = true;
                    $details[] = "PENALTY: Skip detected (-50, suspicious)";
                }
                
                // 3+ seeks = attention penalty
                if ($seekCount >= 3) {
                    $score -= 20;
                    $details[] = "PENALTY: Excessive seeking (-20)";
                }
                
                // Very high seek count = suspicious
                if ($seekCount >= 10) {
                    $isSuspicious = true;
                    $details[] = "SUSPICIOUS: Very high seek count";
                }
            }
            
            // ✅ Video completion percentage bonus (up to 35 points)
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
            
            // ✅ Final suspicious check based on score
            if ($score < 30) {
                $isSuspicious = true;
            }

            // Ensure score is in valid range (0-100)
            $score = max(0, min(100, $score));

            return ['score' => $score, 'is_suspicious' => $isSuspicious, 'details' => $details];

        } catch (\Exception $e) {
            return ['score' => 0, 'is_suspicious' => true, 'details' => ['Error calculating score: ' . $e->getMessage()]];
        }
    }

    /**
     * 🔧 UPDATED: Process sessions with simulated attention scores
     * Now includes video duration matching and skip/seek penalties
     */
    private function processSessionsWithSimulatedAttention($sessions, $assignmentId)
    {
        $totalSessions = $sessions->count();
        $totalCalculatedMinutes = 0;
        $simulatedAttentionScores = [];
        $suspiciousSessions = 0;
        $sessionDetails = [];

        foreach ($sessions as $session) {
            // Calculate real duration
            $calculatedDuration = $this->getActualSessionDuration($session->session_start, $session->session_end);

            // ✅ Get full session data for skip/seek tracking
            $fullSessionData = null;
            if (isset($session->id)) {
                $fullSessionData = DB::table('learning_sessions')
                    ->where('id', $session->id)
                    ->select('video_skip_count', 'seek_count', 'pause_count', 'video_replay_count', 'video_completion_percentage', 'content_id')
                    ->first();
            }
            
            $contentId = $fullSessionData->content_id ?? ($session->content_id ?? null);

            // ✅ Generate attention score with video duration and skip/seek data
            $attentionResult = $this->calculateSimulatedAttentionScore(
                $session->session_start,
                $session->session_end,
                $calculatedDuration,
                $contentId,
                $fullSessionData
            );
            
            $simulatedAttention = $attentionResult['score'];
            $isSuspicious = $attentionResult['is_suspicious'];

            if ($calculatedDuration > 0) {
                $totalCalculatedMinutes += $calculatedDuration;
                $simulatedAttentionScores[] = $simulatedAttention;
            }

            if ($isSuspicious) {
                $suspiciousSessions++;
            }

            $sessionDetails[] = [
                'id' => $session->id,
                'start' => $session->session_start,
                'end' => $session->session_end,
                'stored_duration' => $session->total_duration_minutes ?? 0,
                'calculated_duration' => $calculatedDuration,
                'stored_attention' => $session->attention_score ?? 0,
                'simulated_attention' => $simulatedAttention,
                'is_suspicious' => $isSuspicious,
                'engagement_level' => $this->calculateEngagementLevel($simulatedAttention),
                'score_details' => $attentionResult['details'] ?? [],
            ];
        }

        $averageSimulatedAttention = count($simulatedAttentionScores) > 0
            ? array_sum($simulatedAttentionScores) / count($simulatedAttentionScores)
            : 0;

        return [
            'total_sessions' => $totalSessions,
            'total_time_spent' => $totalCalculatedMinutes,
            'avg_attention_score' => round($averageSimulatedAttention, 1),
            'suspicious_sessions' => $suspiciousSessions,
            'session_details' => $sessionDetails,
        ];
    }

    /**
     * 🔧 EXISTING: Calculate REAL duration from start/end times
     */
    private function getActualSessionDuration($sessionStart, $sessionEnd)
    {
        if (!$sessionStart || !$sessionEnd) {
            return 0;
        }

        try {
            $start = Carbon::parse($sessionStart);
            $end = Carbon::parse($sessionEnd);
            $minutes = $start->diffInMinutes($end);

            return max(0, $minutes); // Ensure non-negative
        } catch (\Exception $e) {

            return 0;
        }
    }

    /**
     * 🔧 UPDATED: Calculate real statistics with simulated attention
     * Now includes video duration matching and skip/seek penalties
     */
    private function calculateRealStatsWithSimulatedAttention()
    {
        try {
            // Calculate REAL total learning time and SIMULATED attention
            $totalRealMinutes = 0;
            $totalStoredMinutes = 0;
            $simulatedAttentionScores = [];
            $suspiciousCount = 0;

            $allSessions = DB::table('learning_sessions')
                ->select(
                    'id', 'session_start', 'session_end', 'total_duration_minutes', 'content_id',
                    'video_skip_count', 'seek_count', 'pause_count', 'video_replay_count', 'video_completion_percentage'
                )
                ->get();

            foreach ($allSessions as $session) {
                $realDuration = $this->getActualSessionDuration($session->session_start, $session->session_end);
                $storedDuration = $session->total_duration_minutes ?? 0;

                $totalRealMinutes += $realDuration;
                $totalStoredMinutes += $storedDuration;

                // Generate simulated attention with video duration and skip/seek data
                if ($realDuration > 0) {
                    $attentionResult = $this->calculateSimulatedAttentionScore(
                        $session->session_start,
                        $session->session_end,
                        $realDuration,
                        $session->content_id,
                        $session
                    );
                    $simulatedAttentionScores[] = $attentionResult['score'];
                    
                    if ($attentionResult['is_suspicious']) {
                        $suspiciousCount++;
                    }
                }
            }

            $avgSimulatedAttention = count($simulatedAttentionScores) > 0
                ? array_sum($simulatedAttentionScores) / count($simulatedAttentionScores)
                : 0;

            $stats = [
                'total_assignments' => CourseOnlineAssignment::count(),
                'completed_assignments' => CourseOnlineAssignment::where('status', 'completed')->count(),
                'in_progress_assignments' => CourseOnlineAssignment::where('status', 'in_progress')->count(),
                'average_completion_rate' => round(CourseOnlineAssignment::avg('progress_percentage') ?? 0, 1),
                'stored_learning_hours' => round($totalStoredMinutes / 60, 1),
                'total_learning_hours' => round($totalRealMinutes / 60, 1),
                'total_users' => User::where('role', '!=', 'admin')->count(),
                'stored_average_attention_score' => 0,
                'average_attention_score' => round($avgSimulatedAttention, 1),
                'total_sessions' => LearningSession::count(),
                'suspicious_sessions' => $suspiciousCount,
                'engagement_distribution' => [
                    'high_engagement' => count(array_filter($simulatedAttentionScores, fn($score) => $score >= 80)),
                    'medium_engagement' => count(array_filter($simulatedAttentionScores, fn($score) => $score >= 60 && $score < 80)),
                    'low_engagement' => count(array_filter($simulatedAttentionScores, fn($score) => $score >= 40 && $score < 60)),
                    'very_low_engagement' => count(array_filter($simulatedAttentionScores, fn($score) => $score < 40)),
                ],
            ];

            return $stats;

        } catch (\Exception $e) {
            return [
                'total_assignments' => 0,
                'completed_assignments' => 0,
                'in_progress_assignments' => 0,
                'average_completion_rate' => 0,
                'total_learning_hours' => 0,
                'total_users' => 0,
                'average_attention_score' => 0,
                'suspicious_sessions' => 0,
            ];
        }
    }

    /**
     * 🔧 UPDATED: Calculate session statistics with simulated attention
     * Now includes video duration matching and skip/seek penalties
     */
    private function calculateSessionStatsWithSimulatedAttention()
    {
        try {
            $totalRealMinutes = 0;
            $simulatedAttentionScores = [];
            $suspiciousCount = 0;

            $allSessions = DB::table('learning_sessions')
                ->select(
                    'id', 'session_start', 'session_end', 'total_duration_minutes', 'content_id',
                    'video_skip_count', 'seek_count', 'pause_count', 'video_replay_count', 'video_completion_percentage'
                )
                ->get();

            foreach ($allSessions as $session) {
                $duration = $this->getActualSessionDuration($session->session_start, $session->session_end);
                $totalRealMinutes += $duration;

                if ($duration > 0) {
                    $attentionResult = $this->calculateSimulatedAttentionScore(
                        $session->session_start,
                        $session->session_end,
                        $duration,
                        $session->content_id,
                        $session
                    );
                    $simulatedAttentionScores[] = $attentionResult['score'];

                    if ($attentionResult['is_suspicious']) {
                        $suspiciousCount++;
                    }
                }
            }

            $avgRealDuration = $allSessions->count() > 0 ? $totalRealMinutes / $allSessions->count() : 0;
            $avgSimulatedAttention = count($simulatedAttentionScores) > 0
                ? array_sum($simulatedAttentionScores) / count($simulatedAttentionScores)
                : 0;

            return [
                'total_sessions' => LearningSession::count(),
                'completed_sessions' => LearningSession::whereNotNull('session_end')->count(),
                'active_sessions' => LearningSession::whereNull('session_end')->count(),
                'stored_suspicious_sessions' => LearningSession::where('is_suspicious_activity', true)->count(),
                'simulated_suspicious_sessions' => $suspiciousCount,
                'suspicious_sessions' => $suspiciousCount,
                'stored_average_duration' => round(LearningSession::avg('total_duration_minutes') ?? 0, 1),
                'real_average_duration' => round($avgRealDuration, 1),
                'total_real_learning_hours' => round($totalRealMinutes / 60, 1),
                'stored_average_attention' => 0,
                'simulated_average_attention' => round($avgSimulatedAttention, 1),
                'average_attention_score' => round($avgSimulatedAttention, 1),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * 🔧 NEW: Get user quiz performance data
     */
    private function getUserQuizPerformance($userId, $courseId = null)
    {
        $query = DB::table('quiz_attempts')
            ->where('user_id', $userId);
        
        if ($courseId) {
            // Get quizzes associated with this course
            $quizIds = DB::table('quizzes')
                ->where('course_online_id', $courseId)
                ->pluck('id');
            
            if ($quizIds->isNotEmpty()) {
                $query->whereIn('quiz_id', $quizIds);
            }
        }
        
        return $query->selectRaw('MAX(total_score) as highest_score, COUNT(*) as attempts')
            ->first();
    }

    /**
     * 🔧 NEW: Calculate session performance with simulated data
     */
    private function calculateSessionPerformance($duration, $simulatedAttention, $isSuspicious)
    {
        $score = 0;

        // Duration score (optimal range: 10-45 minutes)
        if ($duration >= 10 && $duration <= 45) {
            $score += 40;
        } elseif ($duration >= 5 && $duration < 10) {
            $score += 30;
        } elseif ($duration > 45 && $duration <= 60) {
            $score += 25;
        } else {
            $score += 10;
        }

        // Attention score (40% weight)
        $score += ($simulatedAttention * 0.4);

        // Penalty for suspicious activity
        if ($isSuspicious) {
            $score -= 30;
        }

        $score = max(0, min(100, $score));

        if ($score >= 80) return 'Excellent';
        if ($score >= 65) return 'Good';
        if ($score >= 50) return 'Average';
        if ($score >= 35) return 'Below Average';
        return 'Poor';
    }

    /**
     * 🔧 Debug database tables
     */
    private function debugDatabaseTables()
    {
        try {
            $tables = ['course_online_assignments', 'learning_sessions', 'user_content_progress'];

            foreach ($tables as $table) {
                $count = DB::table($table)->count();

                if ($table === 'learning_sessions' && $count > 0) {
                    $sample = DB::table($table)
                        ->select('id', 'session_start', 'session_end', 'total_duration_minutes', 'attention_score')
                        ->limit(2)
                        ->get()
                        ->toArray();
                }
            }
        } catch (\Exception $e) {
        }
    }

    // =====================================
    // 🔧 EXISTING HELPER METHODS (unchanged)
    // =====================================

    /**
     * Format duration
     */
    private function formatDuration($minutes)
    {
        if (!$minutes || $minutes <= 0) return '0 min';

        if ($minutes < 60) {
            return round($minutes) . ' min';
        }

        $hours = floor($minutes / 60);
        $mins = round($minutes % 60);

        return $mins > 0 ? $hours . 'h ' . $mins . 'm' : $hours . 'h';
    }

    /**
     * Calculate engagement level
     */
    private function calculateEngagementLevel($attentionScore)
    {
        if ($attentionScore >= 80) return 'High';
        if ($attentionScore >= 60) return 'Medium';
        if ($attentionScore >= 40) return 'Low';
        return 'Very Low';
    }

    /**
     * Calculate performance rating
     */
    private function calculatePerformanceRating($progressPercentage, $attentionScore, $suspiciousActivities, $totalSessions)
    {
        $score = 0;
        $score += ($progressPercentage * 0.4);
        $score += ($attentionScore * 0.4);

        if ($totalSessions > 0) {
            $suspiciousRatio = $suspiciousActivities / $totalSessions;
            $score -= ($suspiciousRatio * 20);
        }

        $score = max(0, min(100, $score));

        if ($score >= 85) return 'Excellent';
        if ($score >= 70) return 'Good';
        if ($score >= 60) return 'Average';
        if ($score >= 40) return 'Below Average';
        return 'Poor';
    }

    /**
     * Calculate user performance rating (UPDATED: Equal 25% weights)
     * 
     * Formula:
     * final_score = (completion_rate × 0.25) + (progress_rate × 0.25) + 
     *               (attention_score × 0.25) + (quiz_score × 0.25) - suspicious_penalty
     */
    private function calculateUserPerformanceRating($completionRate, $avgProgress, $attentionScore, $quizScore, $suspiciousActivities, $totalSessions)
    {
        // Calculate weighted components (25% each)
        $completionWeighted = $completionRate * 0.25;
        $progressWeighted = $avgProgress * 0.25;
        $attentionWeighted = $attentionScore * 0.25;
        $quizWeighted = $quizScore * 0.25;

        // Calculate suspicious penalty
        $suspiciousPenalty = 0;
        if ($totalSessions > 0) {
            $suspiciousRatio = $suspiciousActivities / $totalSessions;
            $suspiciousPenalty = $suspiciousRatio * 10;
        }

        // Calculate final score
        $finalScore = $completionWeighted + $progressWeighted + $attentionWeighted + $quizWeighted - $suspiciousPenalty;
        $finalScore = max(0, min(100, $finalScore));

        // Determine rating level
        if ($finalScore >= 85) return 'Excellent';
        if ($finalScore >= 70) return 'Good';
        if ($finalScore >= 60) return 'Average';
        return 'Needs Improvement';
    }

    /**
     * Calculate risk level
     */
    private function calculateRiskLevel($suspiciousActivities, $totalSessions)
    {
        if ($totalSessions <= 0) return 'Unknown';

        $riskRatio = $suspiciousActivities / $totalSessions;

        if ($riskRatio >= 0.5) return 'High Risk';
        if ($riskRatio >= 0.25) return 'Medium Risk';
        if ($riskRatio > 0) return 'Low Risk';
        return 'No Risk';
    }

    /**
     * 📊 Calculate comprehensive quiz performance for a user (BOTH Regular & Module Quizzes)
     * 
     * Includes:
     * - Regular Quizzes: Assigned via quiz_assignments table
     * - Module Quizzes: Part of course modules, tracked in module_quiz_results table
     * 
     * Components (weighted):
     * - Completion Rate: 40% (total completed / total assigned quizzes)
     * - Passing Rate: 40% (total passed / total completed quizzes)  
     * - Average Score: 20% (average of regular quiz avg + module quiz avg)
     * 
     * Standards (Dynamic):
     * - Meets Standards: Completion ≥90%, Passing ≥ avgPassThreshold%, Avg Score ≥ avgPassThreshold%
     * - avgPassThreshold is calculated from the average pass_threshold of ALL assigned quizzes (regular + module)
     */
    private function calculateUserQuizPerformance($userId, $courseId = null)
    {
        $regularAssignedCount = 0;
        $regularCompletedCount = 0;
        $regularPassedCount = 0;
        $regularAvgScore = 0;
        $regularAssignedData = collect();
        
        $moduleAssignedCount = 0;
        $moduleCompletedCount = 0;
        $modulePassedCount = 0;
        $moduleAvgScore = 0;
        $moduleResults = collect();
        $moduleIdsWithQuizzes = collect();
        
        // ===== CHECK IF COURSE HAS REGULAR QUIZZES =====
        $hasRegularQuizzes = DB::table('quizzes')
            ->where('is_module_quiz', false)
            ->when($courseId, function ($q) use ($courseId) {
                return $q->where('course_online_id', $courseId);
            })
            ->exists();
        
        // ===== CALCULATE REGULAR QUIZZES (only if they exist) =====
        if ($hasRegularQuizzes) {
            $regularAssignedQuery = DB::table('quiz_assignments')
                ->join('quizzes', 'quiz_assignments.quiz_id', '=', 'quizzes.id')
                ->where('quiz_assignments.user_id', $userId)
                ->where('quizzes.is_module_quiz', false)
                ->select('quizzes.pass_threshold', 'quizzes.id as quiz_id');
            
            if ($courseId) {
                $regularAssignedQuery->where('quizzes.course_online_id', $courseId);
            }
            
            $regularAssignedData = $regularAssignedQuery->get();
            $regularAssignedCount = $regularAssignedData->count();
            
            // Get completed regular quiz attempts
            if ($regularAssignedCount > 0) {
                $regularAttemptsQuery = DB::table('quiz_attempts')
                    ->where('user_id', $userId)
                    ->whereNotNull('completed_at');
                
                if ($courseId) {
                    $regularQuizIds = DB::table('quizzes')
                        ->where('course_online_id', $courseId)
                        ->where('is_module_quiz', false)
                        ->pluck('id');
                    if ($regularQuizIds->isNotEmpty()) {
                        $regularAttemptsQuery->whereIn('quiz_id', $regularQuizIds);
                    }
                } else {
                    $regularQuizIds = DB::table('quizzes')
                        ->where('is_module_quiz', false)
                        ->pluck('id');
                    if ($regularQuizIds->isNotEmpty()) {
                        $regularAttemptsQuery->whereIn('quiz_id', $regularQuizIds);
                    }
                }
                
                $regularCompletedAttempts = $regularAttemptsQuery
                    ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
                    ->select([
                        'quiz_attempts.quiz_id',
                        DB::raw('MAX(quiz_attempts.total_score) as best_score'),
                        DB::raw('MAX(quizzes.total_points) as total_points'),
                        DB::raw('MAX(quizzes.pass_threshold) as pass_threshold'),
                        DB::raw('MAX(CASE WHEN quiz_attempts.passed = 1 THEN 1 ELSE 0 END) as has_passed'),
                    ])
                    ->groupBy('quiz_attempts.quiz_id')
                    ->get();
                
                $regularCompletedCount = $regularCompletedAttempts->count();
                $regularPassedCount = $regularCompletedAttempts->where('has_passed', 1)->count();
                
                // Calculate regular quiz scores
                $regularScorePercentages = $regularCompletedAttempts->map(function ($attempt) {
                    if ($attempt->total_points > 0) {
                        return ($attempt->best_score / $attempt->total_points) * 100;
                    }
                    return 0;
                });
                
                $regularAvgScore = $regularScorePercentages->count() > 0 
                    ? round($regularScorePercentages->avg(), 1)
                    : 0;
            }
        }
        
        // ===== CHECK IF USER IS ASSIGNED TO COURSES WITH MODULE QUIZZES =====
        // Get course IDs the user is assigned to
        $userAssignedCourseIds = DB::table('course_online_assignments')
            ->where('user_id', $userId)
            ->when($courseId, function ($q) use ($courseId) {
                return $q->where('course_online_id', $courseId);
            })
            ->pluck('course_online_id');
        
        // Only count modules that actually have quizzes (has_quiz = true) AND user is assigned to the course
        $hasModuleQuizzes = false;
        $moduleIdsWithQuizzes = collect();
        
        if ($userAssignedCourseIds->isNotEmpty()) {
            $moduleIdsWithQuizzes = DB::table('course_modules')
                ->where('has_quiz', true)
                ->whereIn('course_online_id', $userAssignedCourseIds)
                ->pluck('id');
            
            $hasModuleQuizzes = $moduleIdsWithQuizzes->isNotEmpty();
        }
        
        // ===== CALCULATE MODULE QUIZZES (only if they exist) =====
        if ($hasModuleQuizzes) {
            $moduleResultsQuery = DB::table('module_quiz_results')
                ->where('user_id', $userId)
                ->whereIn('module_id', $moduleIdsWithQuizzes);
            
            $moduleResults = $moduleResultsQuery->get();
            
            // Only count modules that have quizzes AND user is assigned to the course
            $moduleAssignedCount = $moduleIdsWithQuizzes->count();
            
            // Count unique modules completed (user may have multiple attempts per module)
            $moduleCompletedCount = $moduleResults->unique('module_id')->count();
            
            // Count modules where user has passed (at least one passed attempt per module)
            $modulePassedCount = $moduleResults->where('passed', true)->unique('module_id')->count();
            
            // Calculate average score from best attempts per module
            $bestScoresPerModule = $moduleResults->groupBy('module_id')->map(function ($attempts) {
                return $attempts->max('score_percentage');
            });
            
            $moduleAvgScore = $bestScoresPerModule->count() > 0 
                ? round($bestScoresPerModule->avg(), 1)
                : 0;
        }
        
        // ===== COMBINED CALCULATIONS =====
        $totalAssignedQuizzes = $regularAssignedCount + $moduleAssignedCount;
        $totalCompletedQuizzes = $regularCompletedCount + $moduleCompletedCount;
        $totalPassedQuizzes = $regularPassedCount + $modulePassedCount;
        
        // Calculate average pass threshold from both regular and module quizzes (only if they exist)
        $allThresholds = collect();
        if ($regularAssignedData->isNotEmpty()) {
            $allThresholds = $allThresholds->merge($regularAssignedData->pluck('pass_threshold'));
        }
        
        // Get module quiz thresholds from all assigned module quizzes (not just completed ones)
        if ($moduleIdsWithQuizzes->isNotEmpty()) {
            $moduleQuizThresholds = DB::table('quizzes')
                ->where('is_module_quiz', true)
                ->whereIn('module_id', $moduleIdsWithQuizzes)
                ->pluck('pass_threshold');
            $allThresholds = $allThresholds->merge($moduleQuizThresholds);
        }
        
        $avgPassThreshold = $allThresholds->count() > 0 
            ? round($allThresholds->avg(), 1)
            : 80; // Default to 80 if no quizzes
        
        // Calculate combined rates
        $completionRate = $totalAssignedQuizzes > 0 
            ? round(($totalCompletedQuizzes / $totalAssignedQuizzes) * 100, 1) 
            : 0;
        
        $passingRate = $totalCompletedQuizzes > 0 
            ? round(($totalPassedQuizzes / $totalCompletedQuizzes) * 100, 1) 
            : 0;
        
        // Calculate combined average score (average of regular and module quiz averages)
        $combinedAvgScore = 0;
        if ($regularAvgScore > 0 && $moduleAvgScore > 0) {
            $combinedAvgScore = round(($regularAvgScore + $moduleAvgScore) / 2, 1);
        } elseif ($regularAvgScore > 0) {
            $combinedAvgScore = $regularAvgScore;
        } elseif ($moduleAvgScore > 0) {
            $combinedAvgScore = $moduleAvgScore;
        }
        
        // Calculate weighted quiz performance score
        // Completion Rate: 40%, Passing Rate: 40%, Avg Score: 20%
        $quizPerformanceScore = ($completionRate * 0.4) + ($passingRate * 0.4) + ($combinedAvgScore * 0.2);
        $quizPerformanceScore = round(min(100, max(0, $quizPerformanceScore)), 1);
        
        // Determine if meets standards using average pass threshold
        $meetsStandards = $completionRate >= 90 && $passingRate >= $avgPassThreshold && $combinedAvgScore >= $avgPassThreshold;
        
        // Determine status label
        $statusLabel = $this->getQuizStatusLabel($quizPerformanceScore);
        
        return [
            'assigned_quizzes' => $totalAssignedQuizzes,
            'completed_quizzes' => $totalCompletedQuizzes,
            'passed_quizzes' => $totalPassedQuizzes,
            'completion_rate' => $completionRate,
            'passing_rate' => $passingRate,
            'avg_quiz_score' => $combinedAvgScore,
            'quiz_performance_score' => $quizPerformanceScore,
            'meets_standards' => $meetsStandards,
            'avg_pass_threshold' => $avgPassThreshold,
            'status_label' => $statusLabel,
            // Breakdown for transparency
            'regular_quizzes' => [
                'assigned' => $regularAssignedCount,
                'completed' => $regularCompletedCount,
                'passed' => $regularPassedCount,
                'avg_score' => $regularAvgScore,
            ],
            'module_quizzes' => [
                'assigned' => $moduleAssignedCount,
                'completed' => $moduleCompletedCount,
                'passed' => $modulePassedCount,
                'avg_score' => $moduleAvgScore,
            ],
        ];
    }

    /**
     * Get quiz status label based on performance score
     */
    private function getQuizStatusLabel($score)
    {
        if ($score >= 85) return 'Strong';
        if ($score >= 70) return 'Average';
        return 'Needs Improvement';
    }

    /**
     * 📊 Export Learning Sessions Report
     */
    public function exportLearningSessionsReport(Request $request)
    {


        try {
            $filters = $request->only(['course_id', 'user_id', 'date_from', 'date_to', 'suspicious_only', 'department_id']);

            $query = LearningSession::query()
                ->join('users', 'learning_sessions.user_id', '=', 'users.id')
                ->join('course_online', 'learning_sessions.course_online_id', '=', 'course_online.id')
                ->leftJoin('module_content', 'learning_sessions.content_id', '=', 'module_content.id')
                ->leftJoin('departments', 'users.department_id', '=', 'departments.id');

            // Apply filters
            if (!empty($filters['course_id'])) {
                $query->where('learning_sessions.course_online_id', $filters['course_id']);
            }
            if (!empty($filters['user_id'])) {
                $query->where('learning_sessions.user_id', $filters['user_id']);
            }
            if (!empty($filters['date_from'])) {
                $query->whereDate('learning_sessions.session_start', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->whereDate('learning_sessions.session_start', '<=', $filters['date_to']);
            }
            if (!empty($filters['department_id'])) {
                $query->where('users.department_id', $filters['department_id']);
            }

            $sessions = $query->select([
                'learning_sessions.id',
                'users.name as user_name',
                'users.email as user_email',
                'users.employee_code',
                'departments.name as department_name',
                'course_online.name as course_name',
                'module_content.title as content_title',
                'module_content.content_type',
                'learning_sessions.session_start',
                'learning_sessions.session_end',
                'learning_sessions.total_duration_minutes',
                'learning_sessions.attention_score',
                'learning_sessions.is_suspicious_activity',
            ])
                ->orderByDesc('learning_sessions.session_start')
                ->get();

            // ✅ FIXED: Process data for export (convert to indexed arrays)
            $headers = [
                'Session ID', 'User Name', 'User Email', 'Employee Code', 'Department',
                'Course Name', 'Content Title', 'Content Type', 'Session Date', 'Session Time',
                'Session End', 'Stored Duration (min)', 'Calculated Duration (min)', 'Duration',
                'Stored Attention Score', 'Simulated Attention Score', 'Engagement Level',
                'Is Suspicious', 'Session Status', 'Performance Rating'
            ];

            $exportData = [];
            foreach ($sessions as $session) {
                $calculatedDuration = $this->getActualSessionDuration($session->session_start, $session->session_end);
                
                // Get full session data for skip/seek tracking
                $fullSessionData = DB::table('learning_sessions')
                    ->where('id', $session->id)
                    ->select('video_skip_count', 'seek_count', 'pause_count', 'video_replay_count', 'video_completion_percentage', 'content_id')
                    ->first();
                
                $contentId = $fullSessionData->content_id ?? null;
                
                $attentionResult = $this->calculateSimulatedAttentionScore(
                    $session->session_start,
                    $session->session_end,
                    $calculatedDuration,
                    $contentId,
                    $fullSessionData
                );
                
                $simulatedAttention = $attentionResult['score'];
                $isSuspicious = $attentionResult['is_suspicious'];

                // Apply suspicious filter if needed
                if (!empty($filters['suspicious_only']) && $filters['suspicious_only'] === '1' && !$isSuspicious) {
                    continue;
                }

                $exportData[] = [
                    $session->id,
                    $session->user_name,
                    $session->user_email,
                    $session->employee_code ?? '',
                    $session->department_name ?? '',
                    $session->course_name,
                    $session->content_title ?? 'Course Overview',
                    ucfirst($session->content_type ?? 'general'),
                    Carbon::parse($session->session_start)->format('Y-m-d'),
                    Carbon::parse($session->session_start)->format('H:i'),
                    $session->session_end ? Carbon::parse($session->session_end)->format('H:i') : 'N/A',
                    $session->total_duration_minutes ?? 0,
                    $calculatedDuration,
                    $this->formatDuration($calculatedDuration),
                    $session->attention_score ?? 0,
                    $simulatedAttention,
                    $this->calculateEngagementLevel($simulatedAttention),
                    $isSuspicious ? 'Yes' : 'No',
                    $session->session_end ? 'Completed' : 'Active',
                    $this->calculateSessionPerformance($calculatedDuration, $simulatedAttention, $isSuspicious),
                ];
            }

            // Generate CSV using your existing service
            $filename = 'learning_sessions_report_' . now()->format('Y-m-d_H-i-s') . '.csv';



            return $this->csvExportService->export($filename, $headers, $exportData);

        } catch (\Exception $e) {

            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * 📊 Export User Performance Report
     */
    public function exportUserPerformanceReport(Request $request)
    {


        try {
            $filters = $request->only(['user_id', 'course_id', 'date_from', 'date_to', 'department_id']);

            $query = User::query()
                ->where('role', '!=', 'admin')
                ->with(['department']);

            if (!empty($filters['user_id'])) {
                $query->where('id', $filters['user_id']);
            }
            
            if (!empty($filters['department_id'])) {
                $query->where('department_id', $filters['department_id']);
            }

            $users = $query->get();

            // ✅ FIXED: Headers for your existing service with quiz performance
            $headers = [
                'User ID', 'User Name', 'Email', 'Employee Code', 'Department',
                'Total Assignments', 'Completed Assignments', 'Completion Rate %', 'Average Progress %',
                'Total Sessions', 'Total Learning Hours', 'Average Attention Score', 'Suspicious Sessions',
                'Engagement Level', 'Performance Rating', 'Risk Level',
                // Quiz Performance Columns
                'Quiz Assigned', 'Quiz Completed', 'Quiz Passed', 
                'Quiz Completion Rate %', 'Quiz Passing Rate %', 'Avg Quiz Score %',
                'Quiz Performance Score', 'Quiz Meets Standards'
            ];

            // Process user performance data
            $exportData = [];
            foreach ($users as $user) {
                // Get assignments with filters
                $assignmentQuery = DB::table('course_online_assignments')->where('user_id', $user->id);
                if (!empty($filters['course_id'])) {
                    $assignmentQuery->where('course_online_id', $filters['course_id']);
                }
                if (!empty($filters['date_from'])) {
                    $assignmentQuery->whereDate('assigned_at', '>=', $filters['date_from']);
                }
                if (!empty($filters['date_to'])) {
                    $assignmentQuery->whereDate('assigned_at', '<=', $filters['date_to']);
                }

                $assignmentStats = $assignmentQuery
                    ->selectRaw('
                    COUNT(*) as total_assignments,
                    COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_assignments,
                    AVG(COALESCE(progress_percentage, 0)) as avg_progress
                ')
                    ->first();

                // Get session stats with real durations and simulated attention
                $sessionQuery = DB::table('learning_sessions')->where('user_id', $user->id);
                if (!empty($filters['course_id'])) {
                    $sessionQuery->where('course_online_id', $filters['course_id']);
                }

                $rawSessions = $sessionQuery->select(
                    'id', 'session_start', 'session_end', 'attention_score', 'is_suspicious_activity',
                    'video_skip_count', 'seek_count', 'pause_count', 'video_replay_count',
                    'video_completion_percentage', 'content_id'
                )->get();

                $totalSessions = $rawSessions->count();
                $totalRealMinutes = 0;
                $simulatedAttentionScores = [];
                $suspiciousSessions = 0;

                foreach ($rawSessions as $session) {
                    $duration = $this->getActualSessionDuration($session->session_start, $session->session_end);
                    $totalRealMinutes += $duration;

                    $attentionResult = $this->calculateSimulatedAttentionScore(
                        $session->session_start,
                        $session->session_end,
                        $duration,
                        $session->content_id,
                        $session
                    );
                    $simulatedAttentionScores[] = $attentionResult['score'];

                    if ($attentionResult['is_suspicious']) {
                        $suspiciousSessions++;
                    }
                }

                $avgSimulatedAttention = count($simulatedAttentionScores) > 0
                    ? array_sum($simulatedAttentionScores) / count($simulatedAttentionScores)
                    : 0;

                $completionRate = $assignmentStats->total_assignments > 0
                    ? round(($assignmentStats->completed_assignments / $assignmentStats->total_assignments) * 100, 1)
                    : 0;

                // ✅ Get comprehensive quiz performance for export
                $quizPerformance = $this->calculateUserQuizPerformance($user->id, $filters['course_id'] ?? null);

                $exportData[] = [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->employee_code ?? '',
                    $user->department->name ?? '',
                    $assignmentStats->total_assignments ?? 0,
                    $assignmentStats->completed_assignments ?? 0,
                    $completionRate,
                    round($assignmentStats->avg_progress ?? 0, 1),
                    $totalSessions,
                    round($totalRealMinutes / 60, 1),
                    round($avgSimulatedAttention, 1),
                    $suspiciousSessions,
                    $this->calculateEngagementLevel($avgSimulatedAttention),
                    $this->calculateUserPerformanceRating(
                        $completionRate,
                        $assignmentStats->avg_progress ?? 0,
                        $avgSimulatedAttention,
                        $quizPerformance['avg_quiz_score'],
                        $suspiciousSessions,
                        $totalSessions
                    ),
                    $this->calculateRiskLevel($suspiciousSessions, $totalSessions),
                    // Quiz Performance Data
                    $quizPerformance['assigned_quizzes'],
                    $quizPerformance['completed_quizzes'],
                    $quizPerformance['passed_quizzes'],
                    $quizPerformance['completion_rate'],
                    $quizPerformance['passing_rate'],
                    $quizPerformance['avg_quiz_score'],
                    $quizPerformance['quiz_performance_score'],
                    $quizPerformance['meets_standards'] ? 'Yes' : 'No',
                ];
            }

            // Generate CSV using your existing service
            $filename = 'user_performance_report_' . now()->format('Y-m-d_H-i-s') . '.csv';


            return $this->csvExportService->export($filename, $headers, $exportData);

        } catch (\Exception $e) {


            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * 📊 Export Progress Report (THE MISSING METHOD!)
     */
    public function exportProgressReport(Request $request)
    {


        try {
            $filters = $request->only(['course_id', 'status', 'date_from', 'date_to', 'user_id', 'department_id']);

            // Get basic assignments
            $query = CourseOnlineAssignment::query()
                ->with(['courseOnline', 'user.department', 'assignedBy'])
                ->join('users', 'course_online_assignments.user_id', '=', 'users.id')
                ->join('course_online', 'course_online_assignments.course_online_id', '=', 'course_online.id')
                ->leftJoin('departments', 'users.department_id', '=', 'departments.id');

            // Apply filters
            if (!empty($filters['course_id'])) {
                $query->where('course_online_assignments.course_online_id', $filters['course_id']);
            }
            if (!empty($filters['status'])) {
                $query->where('course_online_assignments.status', $filters['status']);
            }
            if (!empty($filters['user_id'])) {
                $query->where('course_online_assignments.user_id', $filters['user_id']);
            }
            if (!empty($filters['date_from'])) {
                $query->whereDate('course_online_assignments.assigned_at', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $query->whereDate('course_online_assignments.assigned_at', '<=', $filters['date_to']);
            }
            if (!empty($filters['department_id'])) {
                $query->where('users.department_id', $filters['department_id']);
            }

            $query->select([
                'course_online_assignments.id',
                'course_online_assignments.user_id',
                'course_online_assignments.course_online_id',
                'users.name as user_name',
                'users.email as user_email',
                'users.employee_code',
                'departments.name as department_name',
                'course_online.name as course_name',
                'course_online.difficulty_level',
                'course_online_assignments.status',
                'course_online_assignments.progress_percentage',
                'course_online_assignments.assigned_at',
                'course_online_assignments.started_at',
                'course_online_assignments.completed_at',
            ]);

            $assignments = $query->orderByDesc('course_online_assignments.assigned_at')->get();

            // ✅ FIXED: Headers for your existing service
            $headers = [
                'Assignment ID', 'User Name', 'User Email', 'Employee Code', 'Department',
                'Course Name', 'Difficulty Level', 'Status', 'Progress Percentage', 'Assigned Date',
                'Started Date', 'Completed Date', 'Total Sessions', 'Total Time Spent', 'Time Spent (Minutes)',
                'Average Attention Score', 'Engagement Level', 'Suspicious Sessions', 'Total Content Accessed',
                'Completed Content', 'Total Watch Time (sec)', 'Performance Rating'
            ];

            // Process each assignment for export
            $exportData = [];
            foreach ($assignments as $assignment) {
                // Get sessions
                $sessions = DB::table('learning_sessions')
                    ->where('user_id', $assignment->user_id)
                    ->where('course_online_id', $assignment->course_online_id)
                    ->select('id', 'session_start', 'session_end', 'total_duration_minutes', 'attention_score', 'is_suspicious_activity')
                    ->get();

                // Process sessions with simulated attention
                $sessionData = $this->processSessionsWithSimulatedAttention($sessions, $assignment->id);

                // Get progress data
                $progressData = DB::table('user_content_progress')
                    ->where('user_id', $assignment->user_id)
                    ->where('course_online_id', $assignment->course_online_id)
                    ->selectRaw('
                    COUNT(*) as total_content_accessed,
                    COUNT(CASE WHEN is_completed = 1 THEN 1 END) as completed_content,
                    COALESCE(SUM(CASE WHEN watch_time > 0 THEN watch_time ELSE 0 END), 0) as total_watch_time
                ')
                    ->first();

                $exportData[] = [
                    $assignment->id,
                    $assignment->user_name,
                    $assignment->user_email,
                    $assignment->employee_code ?? '',
                    $assignment->department_name ?? '',
                    $assignment->course_name,
                    ucfirst($assignment->difficulty_level ?? ''),
                    ucfirst($assignment->status),
                    $assignment->progress_percentage ?? 0,
                    Carbon::parse($assignment->assigned_at)->format('Y-m-d'),
                    $assignment->started_at ? Carbon::parse($assignment->started_at)->format('Y-m-d') : '',
                    $assignment->completed_at ? Carbon::parse($assignment->completed_at)->format('Y-m-d') : '',
                    $sessionData['total_sessions'],
                    $this->formatDuration($sessionData['total_time_spent']),
                    $sessionData['total_time_spent'],
                    $sessionData['avg_attention_score'],
                    $this->calculateEngagementLevel($sessionData['avg_attention_score']),
                    $sessionData['suspicious_sessions'],
                    $progressData->total_content_accessed ?? 0,
                    $progressData->completed_content ?? 0,
                    $progressData->total_watch_time ?? 0,
                    $this->calculatePerformanceRating(
                        $assignment->progress_percentage ?? 0,
                        $sessionData['avg_attention_score'],
                        $sessionData['suspicious_sessions'],
                        $sessionData['total_sessions']
                    ),
                ];
            }

            // Generate CSV using your existing service
            $filename = 'progress_report_' . now()->format('Y-m-d_H-i-s') . '.csv';



            return $this->csvExportService->export($filename, $headers, $exportData);

        } catch (\Exception $e) {


            return back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * 📊 NEW: Department Performance Report
     */
    public function departmentPerformanceReport(Request $request)
    {
        $filters = $request->only(['department_id', 'date_from', 'date_to']);
        
        $data = $this->departmentPerformanceService->getDepartmentPerformance($filters);
        $departments = Department::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Reports/DepartmentPerformance', [
            'departments' => $departments, // For the filter dropdown
            'reportData' => $data['departments'], // The actual report data
            'stats' => $data['stats'],
            'filters' => $filters
        ]);
    }

    /**
     * 📤 NEW: Export Department Performance Report
     */
    public function exportDepartmentPerformanceReport(Request $request)
    {
        $filters = $request->only(['department_id', 'date_from', 'date_to']);
        
        $data = $this->departmentPerformanceService->getDepartmentPerformance($filters);

        $csvData = [];
        foreach ($data['departments'] as $dept) {
            // Add Top Performers
            foreach ($dept['top_performers'] as $user) {
                $csvData[] = [
                    'Department' => $dept['name'],
                    'Rank Type' => 'Top Performer',
                    'Rank' => $user->rank,
                    'Employee Code' => $user->employee_code,
                    'Name' => $user->name,
                    'Email' => $user->email,
                    'Total Evaluations' => $user->total_evaluations,
                    'Average Score' => $user->avg_score,
                    'Regular Courses' => $user->regular_courses,
                    'Online Courses' => $user->online_courses,
                ];
            }

            // Add Bottom Performers
            foreach ($dept['bottom_performers'] as $user) {
                $csvData[] = [
                    'Department' => $dept['name'],
                    'Rank Type' => 'Needs Support',
                    'Rank' => $user->rank, // Note: rank might need adjustment if we want 1,2,3 from bottom
                    'Employee Code' => $user->employee_code,
                    'Name' => $user->name,
                    'Email' => $user->email,
                    'Total Evaluations' => $user->total_evaluations,
                    'Average Score' => $user->avg_score,
                    'Regular Courses' => $user->regular_courses,
                    'Online Courses' => $user->online_courses,
                ];
            }
        }

        $filename = 'department-performance-report-' . now()->format('Y-m-d') . '.csv';
        $headers = [
            'Department',
            'Rank Type',
            'Rank',
            'Employee Code',
            'Name',
            'Email',
            'Total Evaluations',
            'Average Score',
            'Regular Courses',
            'Online Courses',
        ];

        return $this->csvExportService->export($filename, $headers, $csvData);
    }

}
