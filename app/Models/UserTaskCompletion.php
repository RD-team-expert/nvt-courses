<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTaskCompletion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'task_id',
        'content_id',
        'attempt_number',
        'score',
        'is_passed',
        'submission_data',
        'completed_at',
        'time_spent',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'is_passed' => 'boolean',
        'submission_data' => 'array',
        'completed_at' => 'datetime',
        'time_spent' => 'integer',
        'attempt_number' => 'integer',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(ModuleTask::class, 'task_id');
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(ModuleContent::class, 'content_id');
    }

    // Helper methods
    public function getFormattedTimeSpentAttribute(): string
    {
        if (!$this->time_spent) return '0:00';

        $minutes = floor($this->time_spent / 60);
        $seconds = $this->time_spent % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function getScorePercentageAttribute(): float
    {
        if (!$this->task->passing_score) return 0;
        return ($this->score / $this->task->passing_score) * 100;
    }
}
