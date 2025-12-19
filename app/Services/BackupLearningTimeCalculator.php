<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Backup Learning Time Calculator
 * 
 * This service provides fallback calculations when video tracking data is missing (0 or NULL).
 * It uses completion timestamps and session data to estimate learning time.
 */
class BackupLearningTimeCalculator
{
    /**
     * Calculate learning time using backup methods when primary tracking fails
     * 
     * @param int $userId
     * @param int $courseOnlineId
     * @return array
     */
    public function calculateBackupTime(int $userId, int $courseOnlineId): array
    {
        // Try multiple backup strategies in order of reliability
        $strategies = [
            'session_based' => $this->calculateFromSessions($userId, $courseOnlineId),
            'completion_based' => $this->calculateFromCompletions($userId, $courseOnlineId),
            'content_duration_based' => $this->calculateFromContentDuration($userId, $courseOnlineId),
            'estimated' => $this->estimateFromCourseStructure($userId, $courseOnlineId),
        ];

        // Find the first strategy that returns valid data
        foreach ($strategies as $strategyName => $result) {
            if ($result['total_minutes'] > 0) {
                $result['strategy_used'] = $strategyName;
                $result['is_backup_calculation'] = true;
                return $result;
            }
        }

        // If all strategies fail, return zeros with warning
        return [
            'total_minutes' => 0,
            'total_seconds' => 0,
            'strategy_used' => 'none',
            'is_backup_calculation' => true,
            'warning' => 'No valid data available for calculation'
        ];
    }

    /**
     * Strategy 1: Calculate from session start/end times
     */
    private function calculateFromSessions(int $userId, int $courseOnlineId): array
    {
        $sessions = DB::table('learning_sessions')
            ->where('user_id', $userId)
            ->where('course_online_id', $courseOnlineId)
            ->whereNotNull('session_end')
            ->select('session_start', 'session_end')
            ->get();

        if ($sessions->isEmpty()) {
            return ['total_minutes' => 0, 'total_seconds' => 0];
        }

        $totalSeconds = 0;
        foreach ($sessions as $session) {
            $start = Carbon::parse($session->session_start);
            $end = Carbon::parse($session->session_end);
            $totalSeconds += $start->diffInSeconds($end);
        }

        return [
            'total_minutes' => round($totalSeconds / 60, 2),
            'total_seconds' => $totalSeconds,
            'sessions_count' => $sessions->count(),
            'method' => 'Calculated from session start/end timestamps'
        ];
    }

    /**
     * Strategy 2: Calculate from content completion timestamps
     */
    private function calculateFromCompletions(int $userId, int $courseOnlineId): array
    {
        // Get assignment start time
        $assignment = DB::table('course_online_assignments')
            ->where('user_id', $userId)
            ->where('course_online_id', $courseOnlineId)
            ->first();

        if (!$assignment) {
            return ['total_minutes' => 0, 'total_seconds' => 0];
        }

        $startTime = $assignment->started_at ?? $assignment->assigned_at;
        $completionTime = $assignment->completed_at;

        if (!$completionTime) {
            // Course not completed yet, use current time
            $completionTime = now();
        }

        $start = Carbon::parse($startTime);
        $end = Carbon::parse($completionTime);
        $totalSeconds = $start->diffInSeconds($end);

        // Apply a realistic factor (assume 60% active learning time)
        $estimatedActiveSeconds = $totalSeconds * 0.6;

        return [
            'total_minutes' => round($estimatedActiveSeconds / 60, 2),
            'total_seconds' => round($estimatedActiveSeconds),
            'raw_duration_minutes' => round($totalSeconds / 60, 2),
            'active_time_factor' => 0.6,
            'method' => 'Calculated from assignment start to completion with 60% active time factor'
        ];
    }

    /**
     * Strategy 3: Calculate from content duration and completion status
     */
    private function calculateFromContentDuration(int $userId, int $courseOnlineId): array
    {
        $contentProgress = DB::table('user_content_progress as ucp')
            ->join('module_content as mc', 'ucp.content_id', '=', 'mc.id')
            ->where('ucp.user_id', $userId)
            ->where('ucp.course_online_id', $courseOnlineId)
            ->where('ucp.is_completed', true)
            ->select(
                'mc.content_type',
                'ucp.total_duration',
                'mc.pdf_page_count',
                'ucp.completed_at',
                'ucp.last_accessed_at'
            )
            ->get();

        if ($contentProgress->isEmpty()) {
            return ['total_minutes' => 0, 'total_seconds' => 0];
        }

        $totalSeconds = 0;
        $contentCount = 0;

        foreach ($contentProgress as $content) {
            if ($content->content_type === 'video' && $content->total_duration > 0) {
                // Use stored video duration
                $totalSeconds += $content->total_duration;
                $contentCount++;
            } elseif ($content->content_type === 'pdf' && $content->pdf_page_count > 0) {
                // Estimate 2 minutes per PDF page
                $totalSeconds += ($content->pdf_page_count * 120);
                $contentCount++;
            }
        }

        return [
            'total_minutes' => round($totalSeconds / 60, 2),
            'total_seconds' => $totalSeconds,
            'completed_content_count' => $contentCount,
            'method' => 'Calculated from content duration (videos + estimated PDF reading time)'
        ];
    }

    /**
     * Strategy 4: Estimate from course structure
     */
    private function estimateFromCourseStructure(int $userId, int $courseOnlineId): array
    {
        // Get all content in the course
        $courseContent = DB::table('module_content as mc')
            ->join('course_modules as cm', 'mc.module_id', '=', 'cm.id')
            ->where('cm.course_online_id', $courseOnlineId)
            ->select('mc.content_type', 'mc.pdf_page_count')
            ->get();

        if ($courseContent->isEmpty()) {
            return ['total_minutes' => 0, 'total_seconds' => 0];
        }

        $estimatedSeconds = 0;
        foreach ($courseContent as $content) {
            if ($content->content_type === 'video') {
                // Estimate 10 minutes per video if no duration available
                $estimatedSeconds += 600;
            } elseif ($content->content_type === 'pdf' && $content->pdf_page_count > 0) {
                // Estimate 2 minutes per page
                $estimatedSeconds += ($content->pdf_page_count * 120);
            }
        }

        return [
            'total_minutes' => round($estimatedSeconds / 60, 2),
            'total_seconds' => $estimatedSeconds,
            'content_count' => $courseContent->count(),
            'method' => 'Estimated from course structure (10 min/video, 2 min/PDF page)',
            'is_estimate' => true
        ];
    }

    /**
     * Get detailed backup calculation report for a user
     */
    public function getDetailedReport(int $userId, int $courseOnlineId): array
    {
        return [
            'user_id' => $userId,
            'course_online_id' => $courseOnlineId,
            'primary_tracking' => $this->checkPrimaryTracking($userId, $courseOnlineId),
            'backup_calculation' => $this->calculateBackupTime($userId, $courseOnlineId),
            'all_strategies' => [
                'session_based' => $this->calculateFromSessions($userId, $courseOnlineId),
                'completion_based' => $this->calculateFromCompletions($userId, $courseOnlineId),
                'content_duration_based' => $this->calculateFromContentDuration($userId, $courseOnlineId),
                'estimated' => $this->estimateFromCourseStructure($userId, $courseOnlineId),
            ],
            'generated_at' => now()->toDateTimeString()
        ];
    }

    /**
     * Check if primary tracking data exists
     */
    private function checkPrimaryTracking(int $userId, int $courseOnlineId): array
    {
        $sessions = DB::table('learning_sessions')
            ->where('user_id', $userId)
            ->where('course_online_id', $courseOnlineId)
            ->get();

        $hasValidDuration = $sessions->where('total_duration_minutes', '>', 0)->count() > 0;
        $hasValidPlayback = $sessions->where('active_playback_time', '>', 0)->count() > 0;
        $hasSessionEnd = $sessions->whereNotNull('session_end')->count() > 0;

        return [
            'has_sessions' => $sessions->count() > 0,
            'sessions_count' => $sessions->count(),
            'has_valid_duration' => $hasValidDuration,
            'has_valid_playback_time' => $hasValidPlayback,
            'has_session_end_times' => $hasSessionEnd,
            'needs_backup' => !$hasValidDuration && !$hasValidPlayback
        ];
    }
}
