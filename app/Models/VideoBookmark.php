<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoBookmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'video_id',
        'timestamp',
        'note',
    ];

    protected $casts = [
        'timestamp' => 'decimal:2',
    ];

    /**
     * Get the user for this bookmark
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the video for this bookmark
     */
    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    /**
     * Get formatted timestamp (HH:MM:SS or MM:SS)
     */
    public function getFormattedTimestampAttribute(): string
    {
        $totalSeconds = (int) $this->timestamp;
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        return $hours > 0
            ? sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds)
            : sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * Scope to order by timestamp
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('timestamp');
    }
}
