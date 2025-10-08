<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class CourseOnlineAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_online_id',
        'user_id',
        'assigned_by',
        'assigned_at',
        'started_at',
        'completed_at',
        'status',
        'progress_percentage',
        'current_module_id',
        'notification_sent',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'progress_percentage' => 'decimal:2',
        'notification_sent' => 'boolean',
    ];

    // Relationships
    public function courseOnline(): BelongsTo
    {
        return $this->belongsTo(CourseOnline::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function currentModule(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'current_module_id');
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

    public function updateProgress(float $percentage, ?int $currentModuleId = null): void
    {
        $updateData = [
            'progress_percentage' => min(100, max(0, $percentage)),
        ];

        if ($currentModuleId) {
            $updateData['current_module_id'] = $currentModuleId;
        }

        if ($percentage >= 100) {
            $updateData['status'] = 'completed';
            $updateData['completed_at'] = now();
        } elseif ($this->status === 'assigned') {
            $updateData['status'] = 'in_progress';
            $updateData['started_at'] = now();
        }

        $this->update($updateData);
    }

    public function getTimeSpentAttribute(): ?int
    {
        if (!$this->started_at) return null;

        $endTime = $this->completed_at ?? now();
        return $this->started_at->diffInMinutes($endTime);
    }
}
