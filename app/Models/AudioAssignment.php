<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudioAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'audio_id',
        'user_id',
        'assigned_by',
        'assigned_at',
        'started_at',
        'completed_at',
        'status',
        'progress_percentage',
        'notification_sent',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress_percentage' => 'decimal:2',
        'notification_sent' => 'boolean',
    ];

    /**
     * Boot method to add model events
     */
    protected static function boot()
    {
        parent::boot();
        
        // Ensure completed assignments always have 100% progress
        static::saving(function ($assignment) {
            if ($assignment->status === 'completed' && $assignment->progress_percentage < 100) {
                $assignment->progress_percentage = 100;
            }
        });
    }

    // Relationships
    public function audio(): BelongsTo
    {
        return $this->belongsTo(Audio::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Helper methods
    public function markAsStarted(): void
    {
        if ($this->status === 'assigned') {
            $this->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }
    }

    public function updateProgress(float $percentage): void
    {
        $updateData = [
            'progress_percentage' => min(100, max(0, $percentage)),
        ];

        if ($percentage >= 100) {
            $updateData['status'] = 'completed';
            $updateData['completed_at'] = now();
            $updateData['progress_percentage'] = 100;
        } elseif ($this->status === 'assigned') {
            $updateData['status'] = 'in_progress';
            $updateData['started_at'] = now();
        }

        $this->update($updateData);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'progress_percentage' => 100,
        ]);
    }

    public function getTimeSpentAttribute(): ?int
    {
        if (!$this->started_at) return null;

        if ($this->status === 'completed' && $this->completed_at) {
            $endTime = $this->completed_at;
        } else {
            $endTime = now();
        }

        return $this->started_at->diffInMinutes($endTime);
    }
}
