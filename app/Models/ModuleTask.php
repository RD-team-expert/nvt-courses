<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_id',
        'task_title',
        'task_description',
        'task_type',
        'task_data',
        'passing_score',
        'max_attempts',
        'is_required',
    ];

    protected $casts = [
        'task_data' => 'array',
        'passing_score' => 'decimal:2',
        'max_attempts' => 'integer',
        'is_required' => 'boolean',
    ];

    // Relationships
    public function content(): BelongsTo
    {
        return $this->belongsTo(ModuleContent::class, 'content_id');
    }

    public function completions(): HasMany
    {
        return $this->hasMany(UserTaskCompletion::class, 'task_id');
    }

    // Helper methods
    public function getUserCompletion(int $userId): ?UserTaskCompletion
    {
        return $this->completions()
            ->where('user_id', $userId)
            ->orderBy('completed_at', 'desc')
            ->first();
    }

    public function isCompletedByUser(int $userId): bool
    {
        $completion = $this->getUserCompletion($userId);
        return $completion && $completion->is_passed;
    }

    public function getRemainingAttempts(int $userId): int
    {
        $attempts = $this->completions()
            ->where('user_id', $userId)
            ->count();

        return max(0, $this->max_attempts - $attempts);
    }
}
