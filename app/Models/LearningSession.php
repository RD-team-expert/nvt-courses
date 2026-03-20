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
        'last_heartbeat',  // ✅ FIXED: Add last_heartbeat to fillable
        'total_duration_minutes',
        'api_key_id',  // ✅ This MUST be here!


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

        // Active playback tracking (NEW)
        'active_playback_time',
        'is_within_allowed_time',
        'video_events',
    ];

    protected $casts = [
        'session_start' => 'datetime',
        'session_end' => 'datetime',
        'last_heartbeat' => 'datetime',  // ✅ FIXED: Add last_heartbeat to casts
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
        'active_playback_time' => 'integer',
        'is_within_allowed_time' => 'boolean',
        'video_events' => 'array',
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
    $score        = 0;
    $isSuspicious = false;

    // ----------------------------------------------------------
    // STEP 1: Determine active playback minutes
    // Priority: active_playback_time → timestamps → 0
    // ----------------------------------------------------------
    $activePlaybackMinutes = 0;

    if (!empty($this->active_playback_time) && $this->active_playback_time > 0) {
        $activePlaybackMinutes = $this->active_playback_time / 60;
    } elseif ($this->session_start && $this->session_end) {
        $minutes = $this->session_end->diffInMinutes($this->session_start);
        if ($minutes >= 1 && $minutes <= 180) {
            $activePlaybackMinutes = $minutes;
        }
    }

    // ----------------------------------------------------------
    // STEP 1C: Completion-only fallback (no time data at all)
    // ----------------------------------------------------------
    if ($activePlaybackMinutes <= 0) {
        $completionPct = (float) ($this->video_completion_percentage ?? 0);
        if ($completionPct >= 95) return 60;
        if ($completionPct >= 80) return 50;
        if ($completionPct >= 60) return 40;
        if ($completionPct > 0)   return 25;
        return 0;
    }

    // ----------------------------------------------------------
    // STEP 2: Allowed time window = video duration × 2
    // ----------------------------------------------------------
    $videoDurationMinutes = null;
    if ($this->video_total_duration && $this->video_total_duration > 0) {
        $videoDurationMinutes = $this->video_total_duration / 60;
    }

    $allowedTimeMinutes  = $videoDurationMinutes ? ($videoDurationMinutes * 2) : 90;
    $isWithinAllowedTime = $activePlaybackMinutes <= $allowedTimeMinutes;

    // ----------------------------------------------------------
    // STEP 3: Watch percentage scoring (up to +50)
    // 100%   → +50
    // 75-99% → +40
    // 50-74% → +30
    // 25-49% → +20
    // < 25%  →  +0
    // ----------------------------------------------------------
    $watchPct = (float) ($this->video_completion_percentage ?? 0);

    if (!$isWithinAllowedTime) {
        $isSuspicious = true;
        // No watch score — exceeded allowed time
    } elseif ($watchPct >= 100) {
        $score += 50;
    } elseif ($watchPct >= 75) {
        $score += 40;
    } elseif ($watchPct >= 50) {
        $score += 30;
    } elseif ($watchPct >= 25) {
        $score += 20;
    }

    // ----------------------------------------------------------
    // STEP 4: Session completed bonus (+10)
    // ----------------------------------------------------------
    if ($this->session_end) {
        $score += 10;
    }

    // NOTE: Pause and replay bonuses REMOVED per client spec.

    // ----------------------------------------------------------
    // STEP 5: Video completion bonus (up to +40)
    // 99-100% → +40
    // 80-98%  → +30
    // 50-79%  → +20
    // < 50%   →  +0
    // ----------------------------------------------------------
    $completionPct = (float) ($this->video_completion_percentage ?? 0);

    if ($completionPct >= 99)     $score += 40;
    elseif ($completionPct >= 80) $score += 30;
    elseif ($completionPct >= 50) $score += 20;

    // ----------------------------------------------------------
    // STEP 6: Skip forward PENALTY (-30)
    // ----------------------------------------------------------
    $skipCount = (int) ($this->video_skip_count ?? 0);
    if ($skipCount >= 1) {
        $score       -= 30;
        $isSuspicious = true;
    }

    // NOTE: "score < 30 = suspicious" rule REMOVED per client spec.

    return max(0, min(100, (int) $score));
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
