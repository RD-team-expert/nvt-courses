<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoProgress extends Model
{
    use HasFactory;

    protected $table = 'video_progress';

    protected $fillable = [
        'user_id',
        'video_id',
        'current_time',
        'total_watched_time',
        'is_completed',
        'completion_percentage',
        'playback_speed',
        'last_accessed_at',
    ];

    protected $casts = [
        'current_time' => 'decimal:2',
        'total_watched_time' => 'integer',
        'is_completed' => 'boolean',
        'completion_percentage' => 'decimal:2',
        'playback_speed' => 'decimal:2',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Get the user for this progress
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the video for this progress
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Update progress and calculate completion
     */
    public function updateProgress(float $currentTime, ?int $videoDuration = null): void
    {
        $this->current_time = $currentTime;
        $this->last_accessed_at = now();

        if ($videoDuration && $videoDuration > 0) {
            $this->completion_percentage = min(($currentTime / $videoDuration) * 100, 100);
            $this->is_completed = $this->completion_percentage >= 95; // 95% = completed
        }

        $this->save();
    }

    /**
     * Get formatted current time (HH:MM:SS or MM:SS)
     */
    public function getFormattedCurrentTimeAttribute(): string
    {
        $totalSeconds = (int) $this->current_time;
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        return $hours > 0
            ? sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds)
            : sprintf('%02d:%02d', $minutes, $seconds);
    }
}
