<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudioProgress extends Model
{
    use HasFactory;

    protected $table = 'audio_progress';

    protected $fillable = [
        'user_id',
        'audio_id',
        'current_time',
        'total_listened_time',
        'is_completed',
        'completion_percentage',
        'last_accessed_at',
    ];

    protected $casts = [
        'current_time' => 'decimal:2',
        'total_listened_time' => 'integer',
        'is_completed' => 'boolean',
        'completion_percentage' => 'decimal:2',
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
     * Get the audio for this progress
     */
    public function audio(): BelongsTo
    {
        return $this->belongsTo(Audio::class);
    }

    /**
     * Update progress and calculate completion
     */
    public function updateProgress(float $currentTime, ?int $audioDuration = null): void
    {
        $this->current_time = $currentTime;
        $this->last_accessed_at = now();

        if ($audioDuration && $audioDuration > 0) {
            $this->completion_percentage = min(($currentTime / $audioDuration) * 100, 100);
            $this->is_completed = $this->completion_percentage >= 95; // 95% = completed
        }

        $this->save();
    }

    /**
     * Get formatted current time (MM:SS)
     */
    public function getFormattedCurrentTimeAttribute(): string
    {
        $minutes = floor($this->current_time / 60);
        $seconds = floor($this->current_time % 60);

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

}
