<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseModule extends Model
{
    use HasFactory;

    protected $table = 'course_modules';

    protected $fillable = [
        'course_online_id',
        'name',
        'description',
        'order_number',
        'estimated_duration',
        'is_required',
        'is_active',
    ];

    protected $casts = [
        'order_number' => 'integer',
        'estimated_duration' => 'integer',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function courseOnline(): BelongsTo
    {
        return $this->belongsTo(CourseOnline::class);
    }

    public function content(): HasMany
    {
        return $this->hasMany(ModuleContent::class, 'module_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ModuleTask::class);
    }

    public function learningSessions(): HasMany
    {
        return $this->hasMany(LearningSession::class, 'content_id');
    }

    // ✅ ENHANCED: Progress Methods
    public function getUserProgress(int $userId): array
    {
        $totalContent = $this->content()->count();
        if ($totalContent === 0) {
            return [
                'total_content' => 0,
                'completed_content' => 0,
                'completion_percentage' => 0,
                'is_unlocked' => $this->isUnlockedForUser($userId),
                'is_completed' => false,
            ];
        }

        // ✅ FIXED: Use 'module_id' instead of 'course_id'
        $completedContent = UserContentProgress::whereHas('content', function($query) {
            $query->where('module_id', $this->id);  // ✅ Fixed: module_id not course_id
        })
            ->where('user_id', $userId)
            ->where('is_completed', true)
            ->count();

        $completionPercentage = ($completedContent / $totalContent) * 100;

        return [
            'total_content' => $totalContent,
            'completed_content' => $completedContent,
            'completion_percentage' => round($completionPercentage, 2),
            'is_unlocked' => $this->isUnlockedForUser($userId),
            'is_completed' => $completionPercentage >= 100,
        ];
    }
    public function isUnlockedForUser(int $userId): bool
    {
        // First module is always unlocked
        if ($this->order_number === 1) {
            return true;
        }

        // Check if user is assigned to this course
        $assignment = CourseOnlineAssignment::where('course_online_id', $this->course_online_id)
            ->where('user_id', $userId)
            ->first();

        if (!$assignment) {
            return false;
        }

        // Get previous module
        $previousModule = CourseModule::where('course_online_id', $this->course_online_id)
            ->where('order_number', $this->order_number - 1)
            ->first();

        if (!$previousModule) {
            return true; // No previous module
        }

        // Check if previous module is completed
        $previousProgress = $previousModule->getUserProgress($userId);

        // If previous module has tasks, check task completion
        if ($previousModule->tasks()->count() > 0) {
            return $this->isPreviousModuleTasksCompleted($userId, $previousModule->id);
        }

        // Otherwise, check content completion
        return $previousProgress['is_completed'];
    }

    private function isPreviousModuleTasksCompleted(int $userId, int $moduleId): bool
    {
        $totalTasks = ModuleTask::where('course_module_id', $moduleId)
            ->where('is_active', true)
            ->count();

        if ($totalTasks === 0) {
            return true; // No tasks to complete
        }

        $completedTasks = UserTaskCompletion::whereHas('task', function($query) use ($moduleId) {
            $query->where('course_module_id', $moduleId);
        })
            ->where('user_id', $userId)
            ->where('is_completed', true)
            ->count();

        return $completedTasks >= $totalTasks;
    }

    public function getNextUnlockedContentForUser(int $userId): ?ModuleContent
    {
        if (!$this->isUnlockedForUser($userId)) {
            return null;
        }

        return $this->content()
            ->whereDoesntHave('userProgress', function($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->where('is_completed', true);
            })
            ->orderBy('order_number')
            ->first();
    }

    public function calculateActualDuration(): int
    {
        $totalSeconds = $this->learningSessions()
            ->whereNotNull('session_end')
            ->sum('total_duration_minutes');

        return $totalSeconds * 60; // Convert to seconds
    }

    // Computed attributes
    public function getContentCountAttribute(): int
    {
        return $this->content()->count();
    }

    public function getVideoCountAttribute(): int
    {
        return $this->content()->where('content_type', 'video')->count();
    }

    public function getPdfCountAttribute(): int
    {
        return $this->content()->where('content_type', 'pdf')->count();
    }

    public function getIsCompletableAttribute(): bool
    {
        return $this->content_count > 0 || $this->tasks()->count() > 0;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeForCourse($query, int $courseId)
    {
        return $query->where('course_online_id', $courseId);
    }
}
