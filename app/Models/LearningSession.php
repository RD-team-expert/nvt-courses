<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class LearningSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_online_id',
        'content_id',
        'session_start',
        'session_end',
        'total_duration_minutes',

        // Video-specific tracking
        'video_watch_time',
        'video_total_duration',
        'video_skip_count',
        'video_replay_count',
        'video_completion_percentage',

        // Activity tracking
        'clicks_count',
        'pause_count',
        'seek_count',
        'fullscreen_count',
        'speed_changes',

        // Engagement flags
        'is_suspicious_activity',
        'cheating_score',
        'attention_score',
    ];

    protected $casts = [
        'session_start' => 'datetime',
        'session_end' => 'datetime',
        'total_duration_minutes' => 'integer',
        'video_watch_time' => 'integer',
        'video_total_duration' => 'integer',
        'video_skip_count' => 'integer',
        'video_replay_count' => 'integer',
        'video_completion_percentage' => 'decimal:2',
        'clicks_count' => 'integer',
        'pause_count' => 'integer',
        'seek_count' => 'integer',
        'fullscreen_count' => 'integer',
        'speed_changes' => 'integer',
        'is_suspicious_activity' => 'boolean',
        'cheating_score' => 'integer',
        'attention_score' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function courseOnline(): BelongsTo
    {
        return $this->belongsTo(CourseOnline::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(ModuleContent::class, 'content_id');
    }

    // ✅ FIXED: Session management methods
    public function endSession(): void
    {
        if (!$this->session_end) {
            $this->session_end = now();
            $this->calculateDuration(); // This will now work correctly
            $this->calculateEngagementScores();
            $this->save();
        }
    }

    // ✅ FIXED: Calculate duration correctly (end - start, not start - end)
    public function calculateDuration(): void
    {
        if ($this->session_start && $this->session_end) {
            // ✅ CORRECT: Calculate from start TO end
            $this->total_duration_minutes = max(0, $this->session_end->diffInMinutes($this->session_start));
        }
    }

    public function updateVideoProgress(array $data): void
    {
        $this->video_watch_time = $data['watch_time'] ?? $this->video_watch_time;
        $this->video_total_duration = $data['total_duration'] ?? $this->video_total_duration;
        $this->video_skip_count = $data['skip_count'] ?? $this->video_skip_count;
        $this->video_replay_count = $data['replay_count'] ?? $this->video_replay_count;

        // Calculate completion percentage
        if ($this->video_total_duration > 0) {
            $this->video_completion_percentage = ($this->video_watch_time / $this->video_total_duration) * 100;
        }

        $this->save();
    }

    public function trackActivity(string $activity, int $count = 1): void
    {
        switch ($activity) {
            case 'click':
                $this->clicks_count += $count;
                break;
            case 'pause':
                $this->pause_count += $count;
                break;
            case 'seek':
                $this->seek_count += $count;
                break;
            case 'fullscreen':
                $this->fullscreen_count += $count;
                break;
            case 'speed_change':
                $this->speed_changes += $count;
                break;
        }

        $this->save();
    }

    public function calculateEngagementScores(): void
    {
        $this->attention_score = $this->calculateAttentionScore();
        $this->cheating_score = $this->calculateCheatScore();
        $this->is_suspicious_activity = $this->cheating_score > 70;
    }

    private function calculateAttentionScore(): int
    {
        $score = 0;

        // Base score from video completion
        if ($this->video_completion_percentage) {
            $score += min($this->video_completion_percentage, 100) * 0.5;
        }

        // Session duration score (reasonable pace)
        if ($this->total_duration_minutes && $this->video_total_duration) {
            $expectedMinutes = $this->video_total_duration / 60; // Convert seconds to minutes
            $paceRatio = $this->total_duration_minutes / $expectedMinutes;

            // Optimal pace is 1.0-1.5x (taking time to absorb content)
            if ($paceRatio >= 1.0 && $paceRatio <= 1.5) {
                $score += 30; // Good pace
            } elseif ($paceRatio >= 0.8 && $paceRatio < 2.0) {
                $score += 20; // Acceptable pace
            } else {
                $score += 10; // Too fast or too slow
            }
        }

        // Activity engagement score
        if ($this->pause_count > 0) {
            $score += 10; // Pausing shows engagement
        }
        if ($this->video_replay_count > 0) {
            $score += 10; // Replaying shows attention to detail
        }

        return min(round($score), 100);
    }

    private function calculateCheatScore(): int
    {
        $cheatScore = 0;

        // High skip count is suspicious
        if ($this->video_skip_count > 5) {
            $cheatScore += min($this->video_skip_count * 10, 40);
        }

        // Very high seek count (scrubbing through content)
        if ($this->seek_count > 20) {
            $cheatScore += 30;
        }

        // Very fast completion (less than 50% of video duration)
        if ($this->total_duration_minutes && $this->video_total_duration) {
            $expectedMinutes = $this->video_total_duration / 60;
            if ($this->total_duration_minutes < ($expectedMinutes * 0.5)) {
                $cheatScore += 40;
            }
        }

        // Low video completion but high progress claim
        if ($this->video_completion_percentage < 20 && $this->video_completion_percentage > 0) {
            $cheatScore += 30;
        }

        return min($cheatScore, 100);
    }

    // ✅ FIXED: Ensure duration is always positive
    public function setTotalDurationMinutesAttribute($value)
    {
        $this->attributes['total_duration_minutes'] = max(0, intval($value ?? 0));
    }

    // ✅ FIXED: Ensure attention score is between 0-100
    public function setAttentionScoreAttribute($value)
    {
        $this->attributes['attention_score'] = max(0, min(100, intval($value ?? 0)));
    }

    // Utility methods
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->total_duration_minutes) return '0 min';

        $hours = floor($this->total_duration_minutes / 60);
        $minutes = $this->total_duration_minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes} min";
    }

    public function getEngagementLevelAttribute(): string
    {
        if ($this->attention_score >= 80) return 'High';
        if ($this->attention_score >= 60) return 'Medium';
        if ($this->attention_score >= 40) return 'Low';
        return 'Very Low';
    }

    public function getCheatingRiskAttribute(): string
    {
        if ($this->cheating_score >= 80) return 'Very High';
        if ($this->cheating_score >= 60) return 'High';
        if ($this->cheating_score >= 40) return 'Medium';
        if ($this->cheating_score >= 20) return 'Low';
        return 'None';
    }

    // Scopes
    public function scopeSuspicious($query)
    {
        return $query->where('is_suspicious_activity', true);
    }

    public function scopeHighEngagement($query)
    {
        return $query->where('attention_score', '>=', 70);
    }

    public function scopeForCourse($query, int $courseId)
    {
        return $query->where('course_online_id', $courseId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
