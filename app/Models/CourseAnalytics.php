<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_online_id',
        'total_enrollments',
        'active_learners',
        'completed_learners',
        'completion_rate',
        'dropout_rate',
        'average_completion_time_hours',
        'average_session_duration_minutes',
        'total_learning_hours',
        'average_video_completion_rate',
        'most_skipped_content_id',
        'most_replayed_content_id',
        'average_task_score',
        'cheating_incidents_count',
        'last_calculated_at',
    ];

    protected $casts = [
        'completion_rate' => 'decimal:2',
        'dropout_rate' => 'decimal:2',
        'average_video_completion_rate' => 'decimal:2',
        'average_task_score' => 'decimal:2',
        'total_enrollments' => 'integer',
        'active_learners' => 'integer',
        'completed_learners' => 'integer',
        'average_completion_time_hours' => 'integer',
        'average_session_duration_minutes' => 'integer',
        'total_learning_hours' => 'integer',
        'cheating_incidents_count' => 'integer',
        'last_calculated_at' => 'datetime',
    ];

    // Relationships
    public function courseOnline(): BelongsTo
    {
        return $this->belongsTo(CourseOnline::class);
    }

    public function mostSkippedContent(): BelongsTo
    {
        return $this->belongsTo(ModuleContent::class, 'most_skipped_content_id');
    }

    public function mostReplayedContent(): BelongsTo
    {
        return $this->belongsTo(ModuleContent::class, 'most_replayed_content_id');
    }

    // Utility methods
    public function updateAnalytics(): void
    {
        $courseId = $this->course_online_id;

        // Get all assignments for this course
        $assignments = CourseOnlineAssignment::where('course_online_id', $courseId)->get();

        // Calculate basic metrics
        $this->total_enrollments = $assignments->count();
        $this->active_learners = $assignments->where('status', 'in_progress')->count();
        $this->completed_learners = $assignments->where('status', 'completed')->count();

        // Calculate completion rate
        $this->completion_rate = $this->total_enrollments > 0
            ? ($this->completed_learners / $this->total_enrollments) * 100
            : 0;

        // Calculate dropout rate
        $this->dropout_rate = 100 - $this->completion_rate;

        // Calculate average session duration from learning sessions
        $avgSessionDuration = LearningSession::where('course_online_id', $courseId)
            ->whereNotNull('session_end')
            ->avg('total_duration_minutes');
        $this->average_session_duration_minutes = $avgSessionDuration ? round($avgSessionDuration) : 0;

        // Calculate total learning hours
        $totalMinutes = LearningSession::where('course_online_id', $courseId)
            ->sum('total_duration_minutes');
        $this->total_learning_hours = $totalMinutes ? round($totalMinutes / 60) : 0;

        // Calculate average video completion rate
        $avgVideoCompletion = UserContentProgress::whereHas('content', function($query) use ($courseId) {
            $query->whereHas('module', function($subQuery) use ($courseId) {
                $subQuery->where('course_online_id', $courseId);
            });
        })
            ->where('content_type', 'video')
            ->avg('completion_percentage');
        $this->average_video_completion_rate = $avgVideoCompletion ?? 0;

        // Count cheating incidents
        $this->cheating_incidents_count = LearningSession::where('course_online_id', $courseId)
            ->where('is_suspicious_activity', true)
            ->count();

        $this->last_calculated_at = now();
        $this->save();
    }

    public function getEngagementScoreAttribute(): int
    {
        // Calculate engagement score based on various metrics
        $score = 0;

        // Completion rate weight (40%)
        $score += ($this->completion_rate / 100) * 40;

        // Video completion rate weight (30%)
        $score += ($this->average_video_completion_rate / 100) * 30;

        // Session duration weight (20%) - normalize to reasonable session time (60 min)
        $normalizedDuration = min($this->average_session_duration_minutes / 60, 1);
        $score += $normalizedDuration * 20;

        // Low cheating incidents weight (10%)
        $cheatPenalty = min($this->cheating_incidents_count / max($this->total_enrollments, 1), 1);
        $score += (1 - $cheatPenalty) * 10;

        return round($score);
    }
}
