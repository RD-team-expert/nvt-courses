<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserContentProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content_id',
        'course_online_id',
        'module_id',
        'video_id', // For backward compatibility with existing VideoProgress
        'content_type',
        'watch_time', // For videos (seconds)
        'total_duration', // For videos (seconds)
        'pdf_pages_viewed', // For PDFs
        'completion_percentage',
        'is_completed',
        'completed_at',
        'last_accessed_at',
        'playback_position', // For video resume
        'task_completed',
    ];

    protected $casts = [
        'watch_time' => 'integer',
        'total_duration' => 'integer',
        'pdf_pages_viewed' => 'integer',
        'completion_percentage' => 'decimal:2',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'playback_position' => 'decimal:2',
        'task_completed' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(ModuleContent::class, 'content_id');
    }

    public function courseOnline(): BelongsTo
    {
        return $this->belongsTo(CourseOnline::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    // For backward compatibility with existing VideoProgress
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class, 'video_id');
    }

    // Update progress for videos
    public function updateVideoProgress(float $currentTime, ?int $videoDuration = null): void
    {
        $this->watch_time = $currentTime;
        $this->last_accessed_at = now();
        $this->playback_position = $currentTime;

        if ($videoDuration && $videoDuration > 0) {
            $this->total_duration = $videoDuration;
            $this->completion_percentage = min(($currentTime / $videoDuration) * 100, 100);
            $this->is_completed = $this->completion_percentage >= 95; // 95% considered complete
        }

        $this->save();
    }

    // Update progress for PDFs
    public function updatePdfProgress(int $pagesViewed, int $totalPages): void
    {
        $this->pdf_pages_viewed = $pagesViewed;
        $this->last_accessed_at = now();

        if ($totalPages > 0) {
            $this->completion_percentage = min(($pagesViewed / $totalPages) * 100, 100);
            $this->is_completed = $this->completion_percentage >= 95;
        }

        $this->save();
    }

    // Mark task as completed
    public function markTaskCompleted(): void
    {
        $this->update([
            'task_completed' => true,
            'completed_at' => now(),
        ]);
    }

    // Check if user can access next content
    public function canAccessNext(): bool
    {
        return $this->is_completed && $this->task_completed;
    }

    public function getFormattedWatchTimeAttribute(): string
    {
        if (!$this->watch_time) return '0:00';

        $minutes = floor($this->watch_time / 60);
        $seconds = $this->watch_time % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function learningSessions()
    {
        return $this->hasMany(LearningSession::class, 'content_id', 'content_id')
            ->where('user_id', $this->user_id);
    }

    // ✅ ADD: Enhanced session time tracking
    public function addSessionTime(int $seconds): void
    {
        // Use existing watch_time field for video content
        if ($this->content_type === 'video') {
            $this->watch_time += $seconds;
        }
        $this->last_accessed_at = now();
        $this->save();
    }

    // ✅ ADD: Auto-completion check when updating progress
    protected static function booted()
    {
        static::updating(function ($progress) {
            // Auto-complete when reaching 95% threshold
            if ($progress->completion_percentage >= 95 && !$progress->is_completed) {
                $progress->is_completed = true;
                $progress->completed_at = now();
            }
        });

        static::updated(function ($progress) {
            // Trigger course assignment progress update
            if ($progress->is_completed) {
                $progress->updateCourseProgress();
            }
        });
    }

    // ✅ ADD: Course progress calculation
    private function updateCourseProgress(): void
    {
        $assignment = CourseOnlineAssignment::where('course_online_id', $this->course_online_id)
            ->where('user_id', $this->user_id)
            ->first();

        if ($assignment) {
            $assignment->calculateProgress();
        }
    }

    // ✅ ADD: Analytics methods
    public function getEngagementLevel(): string
    {
        $avgAttention = $this->learningSessions()
            ->whereNotNull('attention_score')
            ->avg('attention_score') ?? 0;

        if ($avgAttention >= 80) return 'High';
        if ($avgAttention >= 60) return 'Medium';
        if ($avgAttention >= 40) return 'Low';
        return 'Very Low';
    }

    public function hasSuspiciousActivity(): bool
    {
        return $this->learningSessions()
            ->where('is_suspicious_activity', true)
            ->exists();
    }
}
