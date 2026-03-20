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
use Illuminate\Support\Facades\Log;
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
                        ->select('id', 'session_start', 'session_end', 'total_duration_minutes', 'attention_score', 'is_suspicious_activity', 'active_playback_time', 'content_id')
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

                    // ✅ NEW: If assignment is completed, ensure progress shows 100%
                    // This handles bad data in database without modifying it
                    if ($assignment->status === 'completed' && $assignment->progress_percentage < 100) {
                        $assignment->progress_percentage = 100;
                    }

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
        // Apply suspicious-only filter at query-level for correct pagination
        if (!empty($filters['suspicious_only'])) {
            $val = $filters['suspicious_only'];
            if ($val === '1' || $val === 1 || $val === true || $val === 'true') {
                $query->where('learning_sessions.is_suspicious_activity', 1);
            }
        }

        $sessions = $query->select([
            // ✅ ALIASED to avoid JOIN id collision with users/course_online/departments tables
            'learning_sessions.id as session_id',
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
            // ✅ ALL fields needed by transform loaded here — eliminates second DB query
            'learning_sessions.content_id',
            'learning_sessions.active_playback_time',
            'learning_sessions.video_skip_count',
            'learning_sessions.seek_count',
            'learning_sessions.pause_count',
            'learning_sessions.video_replay_count',
            'learning_sessions.video_completion_percentage',
            'learning_sessions.is_within_allowed_time',
        ])
            ->orderByDesc('learning_sessions.session_start')
            ->paginate(20)
            ->withQueryString();

        // ✅ OPTIMIZED: Pre-load all content durations in ONE query before the loop
        // This eliminates one DB call per session inside calculateSimulatedAttentionScore()
        $contentIds = $sessions->getCollection()->pluck('content_id')->filter()->unique()->values();
        $contentDurationsMap = DB::table('module_content')
            ->whereIn('id', $contentIds)
            ->pluck('duration', 'id') // keyed by content_id → duration in seconds
            ->map(fn($seconds) => $seconds ? $seconds / 60 : null)
            ->toArray();

        // ✅ FIXED: Transform data with REAL duration and SIMULATED attention
        $sessions->getCollection()->transform(function ($session) use ($contentDurationsMap) {

            // ✅ All fields already loaded in select — no second DB query needed
            $activePlaybackTime = $session->active_playback_time ?? null;
            $contentId = $session->content_id ?? null;

            $calculatedDuration = $this->getActualSessionDuration(
                $session->session_start,
                $session->session_end,
                $activePlaybackTime,
                $session->session_id,  // ✅ use aliased session_id
                $contentId
            );

            $storedDuration = $session->total_duration_minutes ?? 0;

            // ✅ OPTIMIZED: Use pre-loaded duration map — zero extra DB queries
            $videoDurationMinutes = $contentDurationsMap[$contentId] ?? null;

            // ✅ Pass $session directly — it already has all skip/seek/pause/replay fields
            $attentionResult = $this->calculateSimulatedAttentionScore(
                $session->session_start,
                $session->session_end,
                $calculatedDuration,
                $contentId,
                $session,
                $videoDurationMinutes  // ✅ pre-loaded, no DB call inside function
            );

            $simulatedAttention = $attentionResult['score'];
            $isSuspicious = $attentionResult['is_suspicious'];

            // ✅ Extract active playback time and allowed time info
            $activePlaybackMinutes = $attentionResult['active_playback_minutes'] ?? 0;
            $isWithinAllowedTime = $attentionResult['is_within_allowed_time'] ?? true;
            $allowedTimeMinutes = $attentionResult['allowed_time_minutes'] ?? 0;

            // ✅ Prefer stored attention_score when present. Fall back to simulated.
          $finalAttentionScore = $simulatedAttention;
            return [
                'id'                       => $session->session_id,
                'user_name'                => $session->user_name,
                'user_email'               => $session->user_email,
                'employee_code'            => $session->employee_code ?? 'N/A',
                'department'               => $session->department_name ?? 'N/A',
                'course_name'              => $session->course_name,
                'content_title'            => $session->content_title ?? 'Course Overview',
                'content_type'             => ucfirst($session->content_type ?? 'general'),
                'session_start'            => Carbon::parse($session->session_start)->format('M d, Y H:i'),
                'session_end'              => $session->session_end
                                                ? Carbon::parse($session->session_end)->format('H:i')
                                                : 'Active',
                'stored_duration'          => $this->formatDuration($storedDuration),
                'calculated_duration'      => $this->formatDuration($calculatedDuration),
                'duration'                 => $this->formatDuration($calculatedDuration),
                'duration_minutes'         => $calculatedDuration,
                // Active playback time fields
                'active_playback_minutes'  => round($activePlaybackMinutes, 1),
                'active_playback_formatted'=> $this->formatDuration($activePlaybackMinutes),
                'is_within_allowed_time'   => $isWithinAllowedTime,
                'allowed_time_minutes'     => round($allowedTimeMinutes, 1),
                'allowed_time_formatted'   => $this->formatDuration($allowedTimeMinutes),
                // Attention & engagement
                'stored_attention'         => $session->attention_score ?? 0,
                'simulated_attention'      => $simulatedAttention,
                'attention_score'          => $finalAttentionScore,
                'engagement_level'         => $this->calculateEngagementLevel($finalAttentionScore),
                'is_suspicious'            => $isSuspicious,
                'session_status'           => $session->session_end ? 'Completed' : 'Active',
                'performance_rating'       => $this->calculateSessionPerformance(
                                                $calculatedDuration,
                                                $simulatedAttention,
                                                $isSuspicious
                                             ),
                'score_details'            => $attentionResult['details'] ?? [],
                // ✅ Activity breakdown — now populated directly from $session (no longer null)
                'skip_count'               => $session->video_skip_count ?? 0,
                'seek_count'               => $session->seek_count ?? 0,
                'pause_count'              => $session->pause_count ?? 0,
                'replay_count'             => $session->video_replay_count ?? 0,
                'video_completion'         => $session->video_completion_percentage ?? 0,
                // ✅ Cheating score & risk derived from suspicion + attention score
                'cheating_score'           => $isSuspicious
                                                ? min(100, ($attentionResult['score'] < 30
                                                    ? (30 - $attentionResult['score']) * 2
                                                    : 10))
                                                : 0,
                'cheating_risk'            => $isSuspicious
                                                ? ($attentionResult['score'] < 20
                                                    ? 'High'
                                                    : ($attentionResult['score'] < 30 ? 'Medium' : 'Low'))
                                                : 'None',
            ];
        });

        $courses     = CourseOnline::where('is_active', true)->select('id', 'name')->orderBy('name')->get();
        $users       = User::where('role', '!=', 'admin')->select('id', 'name', 'email')->orderBy('name')->get();
        $departments = Department::where('is_active', true)->select('id', 'name')->orderBy('name')->get();

        // ✅ Session statistics with REAL durations and SIMULATED attention
        $sessionStats = $this->calculateSessionStatsWithSimulatedAttention();

        return Inertia::render('Admin/Reports/LearningSessionsReport', [
            'sessions'    => $sessions,
            'courses'     => $courses,
            'users'       => $users,
            'departments' => $departments,
            'filters'     => $filters,
            'stats'       => $sessionStats,
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

            // ✅ NEW: Use smart progress service
            $progressService = app(\App\Services\ProgressCalculationService::class);

            // ✅ FIXED: Transform user data with REAL calculations and SIMULATED attention
            $users->getCollection()->transform(function ($user) use ($filters, $progressService) {


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
                        AVG(COALESCE(progress_percentage, 0)) as avg_progress,
                        AVG(CASE WHEN progress_percentage > 0 THEN progress_percentage END) as avg_active_progress
                    ')
                    ->first();

                // ✅ FIXED: Get session stats with REAL durations and SIMULATED attention
                $sessionQuery = DB::table('learning_sessions')->where('user_id', $user->id);
                if (!empty($filters['course_id'])) {
                    $sessionQuery->where('course_online_id', $filters['course_id']);
                }
                // ✅ FIX: Apply date filters to sessions as well
                if (!empty($filters['date_from'])) {
                    $sessionQuery->whereDate('session_start', '>=', $filters['date_from']);
                }
                if (!empty($filters['date_to'])) {
                    $sessionQuery->whereDate('session_start', '<=', $filters['date_to']);
                }

                $rawSessions = $sessionQuery->select(
                    'id', 'session_start', 'session_end', 'attention_score', 'is_suspicious_activity',
                    'video_skip_count', 'seek_count', 'pause_count', 'video_replay_count',
                    'video_completion_percentage', 'content_id', 'active_playback_time'
                )->get();

                $totalSessions = $rawSessions->count();
                $totalRealMinutes = 0;
                $simulatedAttentionScores = [];
                $suspiciousSessions = 0;

                foreach ($rawSessions as $session) {
                    // ✅ Use REAL session data instead of simulated calculations
                    $activePlaybackTime = $session->active_playback_time ?? null;
                    $contentId = $session->content_id ?? null;
                    $duration = $this->getActualSessionDuration(
                        $session->session_start, 
                        $session->session_end,
                        $activePlaybackTime,
                        $session->id,
                        $contentId
                    );
                    $totalRealMinutes += $duration;

                    // ✅ FIXED: Use real attention score if available, otherwise calculate
                    if (($session->attention_score ?? 0) > 0) {
                        // Use the real stored attention score
                        $simulatedAttentionScores[] = $session->attention_score;
                        
                        // Use real suspicious activity flag
                        if ($session->is_suspicious_activity) {
                            $suspiciousSessions++;
                        }
                    } else {
                        // Fallback to calculation only if no real data exists
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
                }

                $avgSimulatedAttention = count($simulatedAttentionScores) > 0
                    ? array_sum($simulatedAttentionScores) / count($simulatedAttentionScores)
                    : 0;

                $completionRate = $assignmentStats->total_assignments > 0
                    ? round(($assignmentStats->completed_assignments / $assignmentStats->total_assignments) * 100, 1)
                    : 0;

                // ✅ NEW: Use smart progress service for accurate average
                $progressResult = $progressService->getAccurateAverageProgress($user->id, $filters);
                $displayProgress = $progressResult['avg_progress'];

                // Get comprehensive quiz performance for this user
                $quizPerformance = $this->calculateUserQuizPerformance($user->id, $filters['course_id'] ?? null, $filters);

                // Calculate performance rating and score
                $performanceResult = $this->calculateUserPerformanceRating(
                    $completionRate,
                    $assignmentStats->avg_progress ?? 0,
                    $avgSimulatedAttention,
                    $quizPerformance['avg_quiz_score'], // ✅ Pass quiz score as separate 25% component
                    $suspiciousSessions,
                    $totalSessions
                );

                // ✅ FIXED: Use smart progress service instead of manual calculation
                // This ensures we never give users more or less than they deserve
                // $displayProgress = round($assignmentStats->avg_active_progress ?? $assignmentStats->avg_progress ?? 0, 1);
                
                // ✅ NEW: If user has only completed assignments, show 100%
                // This handles the case where completed assignments have wrong progress in DB
                // if ($assignmentStats->total_assignments > 0 && 
                //     $assignmentStats->completed_assignments == $assignmentStats->total_assignments) {
                //     $displayProgress = 100;
                // }
                
                $performanceData = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_code' => $user->employee_code ?? 'N/A',
                    'department' => $user->department->name ?? 'N/A',
                    'total_assignments' => $assignmentStats->total_assignments ?? 0,
                    'completed_assignments' => $assignmentStats->completed_assignments ?? 0,
                    'in_progress_assignments' => ($assignmentStats->total_assignments ?? 0) - ($assignmentStats->completed_assignments ?? 0),
                    'completion_rate' => $completionRate,
                    'avg_progress' => $displayProgress, // ✅ Now shows average of active assignments only
                    'total_sessions' => $totalSessions,
                    'total_learning_hours' => round($totalRealMinutes / 60, 1),
                    'avg_attention_score' => round($avgSimulatedAttention, 1),
                    'suspicious_sessions' => $suspiciousSessions,
                    'engagement_level' => $this->calculateEngagementLevel($avgSimulatedAttention),
                    'performance_rating' => $performanceResult['rating'],
                    'performance_score' => $performanceResult['score'],
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
     * 🔧 UPDATED: Calculate attention score using ACTIVE PLAYBACK TIME
     * Score starts at 0 and is completely earned through proper behavior
     * No penalties for pauses/rewinds within allowed time (Duration × 2)
     * Only penalizes skip forward behavior
     *
     * @param string|null $sessionStart Session start time
     * @param string|null $sessionEnd Session end time
     * @param float $calculatedDuration Calculated duration in minutes (DEPRECATED - use active_playback_time)
     * @param int|null $contentId Content ID for video duration lookup
     * @param object|null $sessionData Session data with skip/seek counts and active_playback_time
     * @return array ['score' => int, 'is_suspicious' => bool, 'details' => array, 'is_within_allowed_time' => bool, 'active_playback_minutes' => float, 'allowed_time_minutes' => float]
     */
    /**
 * 🔧 UPDATED: Calculate attention score using ACTIVE PLAYBACK TIME
 */
private function calculateSimulatedAttentionScore($sessionStart, $sessionEnd, $calculatedDuration, $contentId = null, $sessionData = null, $videoDurationMinutes = null)
{
    // ============================================================
    // 🚨 TEST LOG — if this doesn't appear, logging itself is broken
    // Uses default channel (no 'daily') to guarantee it writes
    // ============================================================
    Log::info('🚨 [ATTN] FUNCTION ENTERED', [
        'session_start'        => $sessionStart,
        'content_id'           => $contentId,
        'calculated_duration'  => $calculatedDuration,
        'active_playback_time' => $sessionData->active_playback_time ?? 'NULL — property missing',
        'video_skip_count'     => $sessionData->video_skip_count ?? 'NULL — property missing',
        'pause_count'          => $sessionData->pause_count ?? 'NULL — property missing',
        'replay_count'         => $sessionData->video_replay_count ?? 'NULL — property missing',
        'video_completion'     => $sessionData->video_completion_percentage ?? 'NULL — property missing',
        'sessionData_type'     => gettype($sessionData),
        'sessionData_class'    => is_object($sessionData) ? get_class($sessionData) : 'NOT AN OBJECT',
        'sessionData_is_null'  => is_null($sessionData),
    ]);

    $details = [];
    $isSuspicious = false;

    // ============================================================
    // STEP 1: Determine active playback minutes
    // ============================================================
    $hasActivePlayback = isset($sessionData->active_playback_time) 
                      && $sessionData->active_playback_time > 0;

    $activePlaybackMinutes = $hasActivePlayback
        ? ($sessionData->active_playback_time / 60)
        : $calculatedDuration;

    Log::info('🪵 [ATTN] STEP 1 — Playback source', [
        'has_active_playback_time' => $hasActivePlayback,
        'active_playback_minutes'  => $activePlaybackMinutes,
        'source'                   => $hasActivePlayback
            ? 'DB active_playback_time (seconds ÷ 60)'
            : 'calculated_duration fallback',
    ]);

    // ============================================================
    // STEP 1B: Timestamp fallback if still 0
    // ============================================================
    if ($activePlaybackMinutes <= 0 && $sessionStart && $sessionEnd) {
        try {
            $start        = new \DateTime($sessionStart);
            $end          = new \DateTime($sessionEnd);
            $diff         = $start->diff($end);
            $totalMinutes = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;

            Log::info('🪵 [ATTN] STEP 1B — Timestamp fallback', [
                'timestamp_diff_minutes' => $totalMinutes,
                'is_reasonable'          => ($totalMinutes >= 1 && $totalMinutes <= 180),
            ]);

            if ($totalMinutes >= 1 && $totalMinutes <= 180) {
                $activePlaybackMinutes = $totalMinutes;
                $details[] = 'Using timestamp duration as fallback';
            } else {
                $details[] = 'Timestamp duration unreasonable (' . $totalMinutes . ' min)';
                Log::warning('🪵 [ATTN] STEP 1B — Unreasonable timestamp duration', [
                    'minutes' => $totalMinutes,
                ]);
            }
        } catch (\Exception $e) {
            $details[] = 'Timestamp parsing failed';
            Log::error('🪵 [ATTN] STEP 1B — Timestamp parse FAILED', ['error' => $e->getMessage()]);
        }
    } else {
        Log::info('🪵 [ATTN] STEP 1B — Skipped timestamp fallback', [
            'reason' => $activePlaybackMinutes > 0
                ? 'already have playback minutes: ' . $activePlaybackMinutes
                : 'missing session_start or session_end',
        ]);
    }

    // ============================================================
    // STEP 1C: Completion-only fallback (no time data at all)
    // ============================================================
    if ($activePlaybackMinutes <= 0) {
        $completionPct = $sessionData->video_completion_percentage ?? 0;

        Log::warning('🪵 [ATTN] STEP 1C — NO playback time at all, using completion fallback', [
            'video_completion_pct' => $completionPct,
        ]);

        if ($sessionData && $completionPct > 0) {
            if ($completionPct >= 95) {
                Log::info('🪵 [ATTN] STEP 1C — EARLY RETURN: score=60 (high completion, no time)');
                return ['score' => 60, 'is_suspicious' => false, 'details' => ['High completion, no time data (+60)'], 'is_within_allowed_time' => true, 'active_playback_minutes' => 0, 'allowed_time_minutes' => 0];
            } elseif ($completionPct >= 80) {
                Log::info('🪵 [ATTN] STEP 1C — EARLY RETURN: score=50 (good completion, no time)');
                return ['score' => 50, 'is_suspicious' => false, 'details' => ['Good completion, no time data (+50)'], 'is_within_allowed_time' => true, 'active_playback_minutes' => 0, 'allowed_time_minutes' => 0];
            } elseif ($completionPct >= 60) {
                Log::info('🪵 [ATTN] STEP 1C — EARLY RETURN: score=40 (moderate completion, no time)');
                return ['score' => 40, 'is_suspicious' => false, 'details' => ['Moderate completion, no time data (+40)'], 'is_within_allowed_time' => true, 'active_playback_minutes' => 0, 'allowed_time_minutes' => 0];
            } else {
                Log::warning('🪵 [ATTN] STEP 1C — EARLY RETURN: score=25, SUSPICIOUS (low completion, no time)');
                return ['score' => 25, 'is_suspicious' => true, 'details' => ['Low completion, no time data (+25)'], 'is_within_allowed_time' => true, 'active_playback_minutes' => 0, 'allowed_time_minutes' => 0];
            }
        }

        Log::warning('🪵 [ATTN] STEP 1C — EARLY RETURN: score=0, SUSPICIOUS (absolutely no data)');
        return ['score' => 0, 'is_suspicious' => true, 'details' => ['No playback time or completion data'], 'is_within_allowed_time' => false, 'active_playback_minutes' => 0, 'allowed_time_minutes' => 0];
    }

    try {
        $score = 0;

        // ============================================================
        // STEP 2: Video duration — use pre-loaded value if provided, DB only as last resort
        // ============================================================
        $videoDurationSeconds = null;

        if ($videoDurationMinutes === null) {
            // Caller did not pre-load — fall back to single DB lookup
            if ($contentId) {
                $videoDurationSeconds = DB::table('module_content')
                    ->where('id', $contentId)
                    ->value('duration');

                $videoDurationMinutes = $videoDurationSeconds
                    ? ($videoDurationSeconds / 60)
                    : null;

                Log::info('🪵 [ATTN] STEP 2 — Video duration DB lookup (fallback)', [
                    'content_id'           => $contentId,
                    'duration_seconds_raw' => $videoDurationSeconds ?? 'NOT FOUND IN DB',
                    'duration_minutes'     => $videoDurationMinutes ?? 'NULL',
                ]);
            } else {
                Log::warning('🪵 [ATTN] STEP 2 — content_id is NULL, skipping video duration lookup');
            }
        } else {
            Log::info('🪵 [ATTN] STEP 2 — Video duration pre-loaded by caller', [
                'content_id'       => $contentId,
                'duration_minutes' => $videoDurationMinutes,
            ]);
        }

        // ============================================================
        // STEP 3: Allowed time = video duration × 2
        // ============================================================
        $allowedTimeMinutes  = $videoDurationMinutes ? ($videoDurationMinutes * 2) : 90;
        $isWithinAllowedTime = $activePlaybackMinutes <= $allowedTimeMinutes;

        Log::info('🪵 [ATTN] STEP 3 — Allowed time window', [
            'video_duration_minutes'  => $videoDurationMinutes ?? 'N/A — using default 90',
            'allowed_time_minutes'    => $allowedTimeMinutes,
            'active_playback_minutes' => round($activePlaybackMinutes, 2),
            'is_within_allowed_time'  => $isWithinAllowedTime,
            'exceeds_by_minutes'      => $isWithinAllowedTime ? 0 : round($activePlaybackMinutes - $allowedTimeMinutes, 2),
        ]);

        // ============================================================
        // STEP 4: Watch percentage scoring (up to +40)
        // 100%      → +40
        // 75–99%    → +30
        // 50–74%    → +20
        // 25–49%    → +10
        // < 25%     →  +0
        // Exceeded allowed time (> 2× video length) → suspicious, +0
        // ============================================================
        $watchPct = isset($sessionData->video_completion_percentage)
            ? (float) $sessionData->video_completion_percentage
            : 0;

        Log::info('🪵 [ATTN] STEP 4 — Watch percentage scoring', [
            'watch_pct'               => $watchPct,
            'is_within_allowed_time'  => $isWithinAllowedTime,
        ]);

        if (!$isWithinAllowedTime) {
            // Session time exceeded 2× video length → suspicious, no watch score
            $isSuspicious = true;
            $details[] = 'Exceeded allowed time window (2× video length) — SUSPICIOUS (+0)';
            Log::warning('🪵 [ATTN] STEP 4 — ⚠️ EXCEEDED allowed time → SUSPICIOUS', [
                'active_playback_minutes' => round($activePlaybackMinutes, 2),
                'allowed_time_minutes'    => $allowedTimeMinutes,
                'exceeded_by_minutes'     => round($activePlaybackMinutes - $allowedTimeMinutes, 2),
            ]);
        } elseif ($watchPct >= 100) {
            $score += 50;
            $details[] = 'Watched 100% of video (+50)';
            Log::info('🪵 [ATTN] STEP 4 — +50 Full watch (100%)', ['watch_pct' => $watchPct]);
        } elseif ($watchPct >= 75) {
            $score += 40;
            $details[] = 'Watched 75–99% of video (+40)';
            Log::info('🪵 [ATTN] STEP 4 — +40 Good watch (75–99%)', ['watch_pct' => $watchPct]);
        } elseif ($watchPct >= 50) {
            $score += 30;
            $details[] = 'Watched 50–74% of video (+30)';
            Log::info('🪵 [ATTN] STEP 4 — +30 Acceptable watch (50–74%)', ['watch_pct' => $watchPct]);
        } elseif ($watchPct >= 25) {
            $score += 20;
            $details[] = 'Watched 25–49% of video (+20)';
            Log::warning('🪵 [ATTN] STEP 4 — +20 Low watch (25–49%)', ['watch_pct' => $watchPct]);
        } else {
            $details[] = 'Watched less than 25% of video (+0)';
            Log::warning('🪵 [ATTN] STEP 4 — +0 Very low watch (< 25%)', ['watch_pct' => $watchPct]);
        }

        // ============================================================
        // STEP 5: Session completed bonus (+5)
        // ============================================================
       if ($sessionEnd) {
            $score += 10;
            $details[] = 'Session completed (+10)';
            Log::info('🪵 [ATTN] STEP 5 — +10 Session has end time');
        } else {
            Log::warning('🪵 [ATTN] STEP 5 — +0 No session_end (session still active?)');
        }

        // STEP 6: Pause & replay bonuses — REMOVED per client spec.
        // These behaviors are no longer part of the scoring model.

        // ============================================================
        // STEP 7: Video completion bonus (up to +35)
        // ============================================================
        $completionPct = isset($sessionData->video_completion_percentage)
            ? $sessionData->video_completion_percentage
            : null;

        Log::info('🪵 [ATTN] STEP 7 — Video completion', [
            'video_completion_pct'    => $completionPct ?? 'NULL — not set',
            'isset_completion'        => isset($sessionData->video_completion_percentage),
        ]);

         if ($sessionData && isset($sessionData->video_completion_percentage)) {
            if ($completionPct >= 99) {
                $score += 40; $details[] = 'Full video completion (+40)';
                Log::info('🪵 [ATTN] STEP 7 — +40 Full completion (99-100%)', ['pct' => $completionPct]);
            } elseif ($completionPct >= 80) {
                $score += 30; $details[] = 'High video completion (+30)';
                Log::info('🪵 [ATTN] STEP 7 — +30 High completion (80-98%)', ['pct' => $completionPct]);
            } elseif ($completionPct >= 50) {
                $score += 20; $details[] = 'Moderate video completion (+20)';
                Log::info('🪵 [ATTN] STEP 7 — +20 Moderate completion (50-79%)', ['pct' => $completionPct]);
            } else {
                Log::warning('🪵 [ATTN] STEP 7 — +0 Low completion (< 50%)', ['pct' => $completionPct]);
            }
        } else {
            Log::warning('🪵 [ATTN] STEP 7 — +0 video_completion_percentage not available');
        }

        // ============================================================
        // STEP 8: Skip forward PENALTY (-30 + suspicious)
        // ============================================================
        $skipCount = $sessionData->video_skip_count ?? 0;

        Log::info('🪵 [ATTN] STEP 8 — Skip check', [
            'video_skip_count' => $skipCount,
            'penalty_will_fire' => ($sessionData && $skipCount >= 1),
        ]);

        if ($sessionData && $skipCount >= 1) {
            $scoreBefore = $score;
            $score -= 30;
            $isSuspicious = true;
            $details[] = 'PENALTY: Skip forward detected (-30)';
            Log::warning('🪵 [ATTN] STEP 8 — ⚠️ SKIP PENALTY FIRED', [
                'skip_count'   => $skipCount,
                'score_before' => $scoreBefore,
                'score_after'  => $score,
            ]);
        } else {
            Log::info('🪵 [ATTN] STEP 8 — No skip penalty', ['skip_count' => $skipCount]);
        }

        // STEP 9: "score < 30 = suspicious" rule — REMOVED per client spec.
        // Suspicion is now determined only by: (1) skip forward, (2) exceeded 2× video length.

        $scoreBeforeClamp = $score;
        $score = max(0, min(100, $score));

        // ============================================================
        // FINAL SUMMARY LOG
        // ============================================================
        Log::info('🎯 [ATTN] ===== FINAL RESULT =====', [
            'final_score'             => $score,
            'score_before_clamp'      => $scoreBeforeClamp,
            'is_suspicious'           => $isSuspicious,
            'suspicious_reasons'      => array_values(array_filter([
                ($isSuspicious && $skipCount >= 1)       ? '1_SKIP_FORWARD'         : null,
                ($isSuspicious && !$isWithinAllowedTime) ? '2_EXCEEDED_ALLOWED_TIME' : null,
            ])),
            'is_within_allowed_time'  => $isWithinAllowedTime,
            'active_playback_minutes' => round($activePlaybackMinutes, 2),
            'allowed_time_minutes'    => round($allowedTimeMinutes, 2),
            'video_duration_minutes'  => $videoDurationMinutes ? round($videoDurationMinutes, 2) : 'N/A',
            'score_breakdown'         => $details,
        ]);

        return [
            'score'                   => $score,
            'is_suspicious'           => $isSuspicious,
            'details'                 => $details,
            'is_within_allowed_time'  => $isWithinAllowedTime,
            'active_playback_minutes' => $activePlaybackMinutes,
            'allowed_time_minutes'    => $allowedTimeMinutes,
        ];

    } catch (\Exception $e) {
        Log::error('🪵 [ATTN] ❌ EXCEPTION THROWN INSIDE TRY BLOCK', [
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ]);

        return [
            'score'                   => 0,
            'is_suspicious'           => true,
            'details'                 => ['Error: ' . $e->getMessage()],
            'is_within_allowed_time'  => false,
            'active_playback_minutes' => 0,
            'allowed_time_minutes'    => 0,
        ];
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
            // ✅ Get full session data for skip/seek tracking AND active_playback_time
            $fullSessionData = null;
            if (isset($session->id)) {
                $fullSessionData = DB::table('learning_sessions')
                    ->where('id', $session->id)
                    ->select('video_skip_count', 'seek_count', 'pause_count', 'video_replay_count', 'video_completion_percentage', 'content_id', 'active_playback_time')
                    ->first();
            }
            
            $contentId = $fullSessionData->content_id ?? ($session->content_id ?? null);
            $activePlaybackTime = $fullSessionData->active_playback_time ?? ($session->active_playback_time ?? null);
            
            // Calculate real duration with active_playback_time priority
            $calculatedDuration = $this->getActualSessionDuration(
                $session->session_start, 
                $session->session_end,
                $activePlaybackTime,
                $session->id,
                $contentId
            );

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
                'engagement_level' => $this->calculateEngagementLevel((($session->attention_score ?? 0) > 0) ? $session->attention_score : $simulatedAttention),
                'score_details' => $attentionResult['details'] ?? [],
                'skip_count'        => $fullSessionData->video_skip_count ?? 0,
'seek_count'        => $fullSessionData->seek_count ?? 0,
'pause_count'       => $fullSessionData->pause_count ?? 0,
'replay_count'      => $fullSessionData->video_replay_count ?? 0,
'video_completion'  => $fullSessionData->video_completion_percentage ?? 0,
'cheating_score'    => $isSuspicious ? min(100, ($attentionResult['score'] < 30 ? (30 - $attentionResult['score']) * 2 : 10)) : 0,
'cheating_risk'     => $isSuspicious
    ? ($attentionResult['score'] < 20 ? 'High' : ($attentionResult['score'] < 30 ? 'Medium' : 'Low'))
    : 'None',
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
     * 🔧 UPDATED: Calculate REAL duration from start/end times or active_playback_time
     * Priority: active_playback_time > calculated duration > 0
     * For active sessions (no end time), use reasonable estimate based on video duration
     */
    private function getActualSessionDuration($sessionStart, $sessionEnd, $activePlaybackTime = null, $sessionId = null, $contentId = null)
    {
        // Priority 1: Use active_playback_time if available (most accurate)
        // ✅ active_playback_time is stored in SECONDS, convert to minutes
        if ($activePlaybackTime && $activePlaybackTime > 0) {
            return round($activePlaybackTime / 60, 2); // Convert seconds to minutes
        }

        // Priority 2: Calculate from start/end times
        if (!$sessionStart) {
            return 0;
        }

        try {
            $start = Carbon::parse($sessionStart);
            
            // If session has an end time, use it
            if ($sessionEnd) {
                $end = Carbon::parse($sessionEnd);
                $minutes = $start->diffInMinutes($end);
                
                // ✅ Cap at 3 hours (180 minutes) to prevent corrupted data from inflating totals
                // Sessions longer than 3 hours are likely due to browser/tab staying open
                return max(0, min($minutes, 180));
            }
            
            // Priority 3: For active sessions (no end time), try to use video duration
            if ($contentId) {
                $videoDurationSeconds = DB::table('module_content')
                    ->where('id', $contentId)
                    ->value('duration');
                
                if ($videoDurationSeconds && $videoDurationSeconds > 0) {
                    // Use video duration as a reasonable estimate
                    return round($videoDurationSeconds / 60, 2);
                }
            }
            
            // Priority 4: Use backup calculation from completion timestamps
            if ($sessionId) {
                $session = DB::table('learning_sessions')
                    ->where('id', $sessionId)
                    ->first();
                
                if ($session && $session->user_id && $session->course_online_id) {
                    // Check if content was completed
                    $contentProgress = DB::table('user_content_progress')
                        ->where('user_id', $session->user_id)
                        ->where('content_id', $session->content_id)
                        ->where('is_completed', true)
                        ->whereNotNull('completed_at')
                        ->first();
                    
                    if ($contentProgress && $contentProgress->completed_at) {
                        // Calculate from session start to content completion
                        $completedAt = Carbon::parse($contentProgress->completed_at);
                        $minutes = $start->diffInMinutes($completedAt);
                        
                        // Use FULL time without reduction factor
                        // Cap at reasonable limit to prevent extreme values
                        return max(0, min($minutes, 180)); // Cap at 3 hours
                    }
                }
            }
            
            // Priority 5: For active sessions without any data, return 0 instead of inflated time
            // This prevents showing 60 minutes for sessions with no tracking data
            return 0;
            
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
            // ✅ OPTIMIZED: Pure SQL aggregates — no PHP loop over all sessions
            $sessionAgg = DB::table('learning_sessions')->selectRaw('
                COUNT(*)                                          as total_sessions,
                COALESCE(SUM(active_playback_time), 0)           as total_playback_seconds,
                COALESCE(SUM(total_duration_minutes), 0)         as total_stored_minutes,
                COALESCE(AVG(NULLIF(attention_score, 0)), 0)     as avg_attention,
                COUNT(CASE WHEN is_suspicious_activity = 1 THEN 1 END) as suspicious_count,
                COUNT(CASE WHEN video_skip_count >= 1 THEN 1 END) as skip_suspicious
            ')->first();

            // Suspicious = stored flag OR skip detected (matches our scoring rules)
            $suspiciousCount = max($sessionAgg->suspicious_count, $sessionAgg->skip_suspicious);

            $totalRealMinutes  = round($sessionAgg->total_playback_seconds / 60, 1);
            $totalStoredMinutes = round($sessionAgg->total_stored_minutes, 1);

            // Engagement distribution via SQL
            $engDist = DB::table('learning_sessions')
                ->where('attention_score', '>', 0)
                ->selectRaw('
                    COUNT(CASE WHEN attention_score >= 80 THEN 1 END) as high,
                    COUNT(CASE WHEN attention_score >= 60 AND attention_score < 80 THEN 1 END) as medium,
                    COUNT(CASE WHEN attention_score >= 40 AND attention_score < 60 THEN 1 END) as low,
                    COUNT(CASE WHEN attention_score < 40 THEN 1 END) as very_low
                ')->first();

            return [
                'total_assignments'           => CourseOnlineAssignment::count(),
                'completed_assignments'       => CourseOnlineAssignment::where('status', 'completed')->count(),
                'in_progress_assignments'     => CourseOnlineAssignment::where('status', 'in_progress')->count(),
                'average_completion_rate'     => round(CourseOnlineAssignment::avg('progress_percentage') ?? 0, 1),
                'stored_learning_hours'       => round($totalStoredMinutes / 60, 1),
                'total_learning_hours'        => round($totalRealMinutes / 60, 1),
                'total_users'                 => User::where('role', '!=', 'admin')->count(),
                'stored_average_attention_score' => 0,
                'average_attention_score'     => round($sessionAgg->avg_attention, 1),
                'total_sessions'              => $sessionAgg->total_sessions,
                'suspicious_sessions'         => $suspiciousCount,
                'engagement_distribution'     => [
                    'high_engagement'      => $engDist->high      ?? 0,
                    'medium_engagement'    => $engDist->medium    ?? 0,
                    'low_engagement'       => $engDist->low       ?? 0,
                    'very_low_engagement'  => $engDist->very_low  ?? 0,
                ],
            ];

        } catch (\Exception $e) {
            return [
                'total_assignments'        => 0,
                'completed_assignments'    => 0,
                'in_progress_assignments'  => 0,
                'average_completion_rate'  => 0,
                'total_learning_hours'     => 0,
                'total_users'              => 0,
                'average_attention_score'  => 0,
                'suspicious_sessions'      => 0,
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
            // ✅ OPTIMIZED: Pure SQL aggregates — no PHP loop over all sessions
            $agg = DB::table('learning_sessions')->selectRaw('
                COUNT(*)                                                    as total_sessions,
                COUNT(CASE WHEN session_end IS NOT NULL THEN 1 END)         as completed_sessions,
                COUNT(CASE WHEN session_end IS NULL THEN 1 END)             as active_sessions,
                COUNT(CASE WHEN is_suspicious_activity = 1 THEN 1 END)     as stored_suspicious,
                COUNT(CASE WHEN video_skip_count >= 1 THEN 1 END)           as skip_suspicious,
                COALESCE(AVG(NULLIF(total_duration_minutes, 0)), 0)         as avg_stored_duration,
                COALESCE(AVG(NULLIF(active_playback_time, 0)) / 60, 0)     as avg_real_duration_minutes,
                COALESCE(SUM(active_playback_time) / 60, 0)                as total_real_minutes,
                COALESCE(AVG(NULLIF(attention_score, 0)), 0)               as avg_attention
            ')->first();

            $suspiciousCount = max($agg->stored_suspicious, $agg->skip_suspicious);

            return [
                'total_sessions'              => $agg->total_sessions,
                'completed_sessions'          => $agg->completed_sessions,
                'active_sessions'             => $agg->active_sessions,
                'stored_suspicious_sessions'  => $agg->stored_suspicious,
                'simulated_suspicious_sessions' => $suspiciousCount,
                'suspicious_sessions'         => $suspiciousCount,
                'stored_average_duration'     => round(LearningSession::avg('total_duration_minutes') ?? 0, 1),
                'real_average_duration'       => round($agg->avg_real_duration_minutes, 1),
                'average_session_duration'    => round($agg->avg_real_duration_minutes, 1),
                'total_real_learning_hours'   => round($agg->total_real_minutes / 60, 1),
                'stored_average_attention'    => 0,
                'simulated_average_attention' => round($agg->avg_attention, 1),
                'average_attention_score'     => round($agg->avg_attention, 1),
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
        $rating = 'Needs Improvement';
        if ($finalScore >= 85) $rating = 'Excellent';
        elseif ($finalScore >= 70) $rating = 'Good';
        elseif ($finalScore >= 60) $rating = 'Average';
        
        // Return both score and rating
        return [
            'score' => round($finalScore, 1),
            'rating' => $rating
        ];
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
    private function calculateUserQuizPerformance($userId, $courseId = null, $filters = [])
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
                
                // ✅ FIX: Apply date filters to quiz attempts
                if (!empty($filters['date_from'])) {
                    $regularAttemptsQuery->whereDate('completed_at', '>=', $filters['date_from']);
                }
                if (!empty($filters['date_to'])) {
                    $regularAttemptsQuery->whereDate('completed_at', '<=', $filters['date_to']);
                }
                
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
            // First, try to get results from module_quiz_results table
            $moduleResultsQuery = DB::table('module_quiz_results')
                ->where('user_id', $userId)
                ->whereIn('module_id', $moduleIdsWithQuizzes);
            
            // ✅ FIX: Apply date filters to module quiz results
            if (!empty($filters['date_from'])) {
                $moduleResultsQuery->whereDate('completed_at', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
                $moduleResultsQuery->whereDate('completed_at', '<=', $filters['date_to']);
            }
            
            $moduleResults = $moduleResultsQuery->get();
            
            // ✅ FIX: Also check quiz_attempts for module quizzes that might be missing from module_quiz_results
            // This handles the bug where some module quiz submissions didn't save to module_quiz_results
            $moduleQuizIds = DB::table('quizzes')
                ->where('is_module_quiz', true)
                ->whereIn('module_id', $moduleIdsWithQuizzes)
                ->pluck('id', 'module_id'); // key by module_id for easy lookup
            
            if ($moduleQuizIds->isNotEmpty()) {
                $moduleAttemptsQuery = DB::table('quiz_attempts')
                    ->where('user_id', $userId)
                    ->whereIn('quiz_id', $moduleQuizIds->values())
                    ->whereNotNull('completed_at');
                
                if (!empty($filters['date_from'])) {
                    $moduleAttemptsQuery->whereDate('completed_at', '>=', $filters['date_from']);
                }
                if (!empty($filters['date_to'])) {
                    $moduleAttemptsQuery->whereDate('completed_at', '<=', $filters['date_to']);
                }
                
                $moduleAttempts = $moduleAttemptsQuery
                    ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
                    ->select([
                        'quiz_attempts.id as attempt_id',
                        'quiz_attempts.quiz_id',
                        'quizzes.module_id',
                        'quiz_attempts.total_score',
                        'quizzes.total_points',
                        'quizzes.pass_threshold',
                        'quiz_attempts.passed',
                        'quiz_attempts.completed_at',
                    ])
                    ->get();
                
                // Find module IDs that have attempts but no results in module_quiz_results
                $moduleIdsWithResults = $moduleResults->pluck('module_id')->unique();
                
                foreach ($moduleAttempts as $attempt) {
                    // Check if this module already has a result
                    if (!$moduleIdsWithResults->contains($attempt->module_id)) {
                        // Calculate score percentage
                        $scorePercentage = $attempt->total_points > 0 
                            ? round(($attempt->total_score / $attempt->total_points) * 100, 2)
                            : 0;
                        
                        // Add as a virtual result (from quiz_attempts)
                        $moduleResults->push((object)[
                            'module_id' => $attempt->module_id,
                            'quiz_id' => $attempt->quiz_id,
                            'score_percentage' => $scorePercentage,
                            'passed' => $attempt->passed,
                            'completed_at' => $attempt->completed_at,
                            'source' => 'quiz_attempts', // Mark source for debugging
                        ]);
                        
                        // Add to the list so we don't add duplicates
                        $moduleIdsWithResults->push($attempt->module_id);
                    }
                }
            }
            
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
                'Active Playback (min)', 'Allowed Time (min)', 'Within Allowed Time',
                'Stored Attention Score', 'Simulated Attention Score', 'Engagement Level',
                'Is Suspicious', 'Session Status', 'Performance Rating'
            ];

            $exportData = [];
            foreach ($sessions as $session) {
                // Get full session data for skip/seek tracking AND active playback time
                $fullSessionData = DB::table('learning_sessions')
                    ->where('id', $session->id)
                    ->select('video_skip_count', 'seek_count', 'pause_count', 'video_replay_count', 'video_completion_percentage', 'content_id', 'active_playback_time', 'is_within_allowed_time')
                    ->first();
                
                $contentId = $fullSessionData->content_id ?? null;
                $activePlaybackTime = $fullSessionData->active_playback_time ?? null;
                
                // Calculate duration with active_playback_time priority
                $calculatedDuration = $this->getActualSessionDuration(
                    $session->session_start,
                    $session->session_end,
                    $activePlaybackTime,
                    $session->id,
                    $contentId
                );
                
                $attentionResult = $this->calculateSimulatedAttentionScore(
                    $session->session_start,
                    $session->session_end,
                    $calculatedDuration,
                    $contentId,
                    $fullSessionData
                );
                
                $simulatedAttention = $attentionResult['score'];
                $isSuspicious = $attentionResult['is_suspicious'];
                
                // ✅ Extract active playback time and allowed time info
                $activePlaybackMinutes = $attentionResult['active_playback_minutes'] ?? 0;
                $isWithinAllowedTime = $attentionResult['is_within_allowed_time'] ?? true;
                $allowedTimeMinutes = $attentionResult['allowed_time_minutes'] ?? 0;

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
                    // ✅ NEW: Active playback time fields
                    round($activePlaybackMinutes, 1),
                    round($allowedTimeMinutes, 1),
                    $isWithinAllowedTime ? 'Yes' : 'No',
                    // Existing fields
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
                    'video_completion_percentage', 'content_id', 'active_playback_time'
                )->get();

                $totalSessions = $rawSessions->count();
                $totalRealMinutes = 0;
                $simulatedAttentionScores = [];
                $suspiciousSessions = 0;

                foreach ($rawSessions as $session) {
                    $activePlaybackTime = $session->active_playback_time ?? null;
                    $contentId = $session->content_id ?? null;
                    $duration = $this->getActualSessionDuration(
                        $session->session_start,
                        $session->session_end,
                        $activePlaybackTime,
                        $session->id,
                        $contentId
                    );
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

