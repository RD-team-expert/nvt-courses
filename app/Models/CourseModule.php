<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        // NEW: Quiz fields
        'has_quiz',
        'quiz_required',
    ];

    protected $casts = [
        'order_number' => 'integer',
        'estimated_duration' => 'integer',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        // NEW: Quiz casts
        'has_quiz' => 'boolean',
        'quiz_required' => 'boolean',
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

    public function tasks(): HasManyThrough
    {
        // Tasks belong to module content, not directly to modules
        // Get tasks through the content relationship
        return $this->hasManyThrough(
            ModuleTask::class,
            ModuleContent::class,
            'module_id',  // Foreign key on module_content table
            'content_id', // Foreign key on module_tasks table
            'id',         // Local key on course_modules table
            'id'          // Local key on module_content table
        );
    }

    public function learningSessions(): HasMany
    {
        return $this->hasMany(LearningSession::class, 'content_id');
    }

    /**
     * Get the quiz for this module (one-to-one relationship).
     */
    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class, 'module_id')
            ->where('is_module_quiz', true);
    }

    /**
     * Get all quizzes for this module (in case of multiple).
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'module_id')
            ->where('is_module_quiz', true);
    }

    /**
     * Get quiz results for this module.
     */
    public function quizResults(): HasMany
    {
        return $this->hasMany(ModuleQuizResult::class, 'module_id');
    }

    // ✅ ENHANCED: Progress Methods with Quiz Support
    public function getUserProgress(int $userId): array
    {
        $totalContent = $this->content()->count();
        $isUnlocked = $this->isUnlockedForUser($userId);
        
        // Get quiz status
        $quizStatus = $this->getQuizStatus($userId);
        
        if ($totalContent === 0) {
            return [
                'total_content' => 0,
                'completed_content' => 0,
                'completion_percentage' => 0,
                'is_unlocked' => $isUnlocked,
                'is_completed' => false,
                // NEW: Quiz information
                'has_quiz' => $this->has_quiz,
                'quiz_required' => $this->quiz_required,
                'quiz_status' => $quizStatus,
                'content_completed' => false,
                'quiz_passed' => $quizStatus['passed'] ?? false,
                'fully_completed' => false,
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
        $contentCompleted = $completionPercentage >= 100;
        
        // Module is fully completed when:
        // 1. All content is completed AND
        // 2. If quiz is required, quiz must be passed
        $fullyCompleted = $contentCompleted;
        if ($this->has_quiz && $this->quiz_required) {
            $fullyCompleted = $contentCompleted && ($quizStatus['passed'] ?? false);
        }

        return [
            'total_content' => $totalContent,
            'completed_content' => $completedContent,
            'completion_percentage' => round($completionPercentage, 2),
            'is_unlocked' => $isUnlocked,
            'is_completed' => $fullyCompleted, // Now considers quiz
            // NEW: Quiz information
            'has_quiz' => $this->has_quiz,
            'quiz_required' => $this->quiz_required,
            'quiz_status' => $quizStatus,
            'content_completed' => $contentCompleted,
            'quiz_passed' => $quizStatus['passed'] ?? false,
            'fully_completed' => $fullyCompleted,
        ];
    }
    /**
     * Check if module is unlocked for user.
     * Updated to require quiz pass from previous module if quiz is required.
     */
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

        // Check if previous module content is completed
        $previousProgress = $previousModule->getUserProgress($userId);
        
        // If previous module has tasks, check task completion
        if ($previousModule->tasks()->count() > 0) {
            if (!$this->isPreviousModuleTasksCompleted($userId, $previousModule->id)) {
                return false;
            }
        } else {
            // Check content completion
            if (!$previousProgress['content_completed']) {
                return false;
            }
        }

        // NEW: Check if previous module has a required quiz
        if ($previousModule->has_quiz && $previousModule->quiz_required) {
            // User must pass the quiz to unlock next module
            if (!$previousModule->hasUserPassedQuiz($userId)) {
                return false;
            }
        }

        return true;
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

    // === QUIZ HELPER METHODS ===

    /**
     * Check if user has passed this module's quiz.
     */
    public function hasUserPassedQuiz(int $userId): bool
    {
        // Quick lookup from module_quiz_results table
        if (ModuleQuizResult::hasUserPassedModule($userId, $this->id)) {
            return true;
        }

        // Fallback: Check quiz attempts directly
        $quiz = $this->quiz;
        if (!$quiz) {
            return true; // No quiz means "passed"
        }

        return $quiz->hasUserPassed($userId);
    }

    /**
     * Get quiz status for user.
     */
    public function getQuizStatus(int $userId): array
    {
        if (!$this->has_quiz) {
            return [
                'has_quiz' => false,
                'quiz_required' => false,
                'passed' => true, // No quiz = passed
                'status' => 'no_quiz',
            ];
        }

        $quiz = $this->quiz;
        if (!$quiz) {
            return [
                'has_quiz' => true,
                'quiz_required' => $this->quiz_required,
                'passed' => !$this->quiz_required, // If not required, consider passed
                'status' => 'quiz_not_created',
                'message' => 'Quiz has not been created yet.',
            ];
        }

        // Get detailed status from quiz model
        $quizStatus = $quiz->getUserStatus($userId);

        return [
            'has_quiz' => true,
            'quiz_required' => $this->quiz_required,
            'quiz_id' => $quiz->id,
            'quiz_title' => $quiz->title,
            'passed' => $quizStatus['passed'],
            'status' => $quizStatus['status'],
            'attempts_used' => $quizStatus['attempts_used'],
            'max_attempts' => $quizStatus['max_attempts'],
            'attempts_remaining' => $quizStatus['attempts_remaining'],
            'can_attempt' => $quizStatus['can_attempt'],
            'attempt_message' => $quizStatus['attempt_message'],
            'show_correct_answers' => $quizStatus['show_correct_answers'],
            'latest_attempt' => $quizStatus['latest_attempt'],
            'best_attempt' => $quizStatus['best_attempt'],
            'pass_threshold' => $quiz->pass_threshold,
            'time_limit_minutes' => $quiz->time_limit_minutes,
        ];
    }

    /**
     * Check if user can take the module quiz.
     * User must complete all content first.
     */
    public function canUserTakeQuiz(int $userId): array
    {
        if (!$this->has_quiz) {
            return [
                'can_take' => false,
                'reason' => 'no_quiz',
                'message' => 'This module does not have a quiz.',
            ];
        }

        $quiz = $this->quiz;
        if (!$quiz) {
            return [
                'can_take' => false,
                'reason' => 'quiz_not_created',
                'message' => 'The quiz for this module has not been created yet.',
            ];
        }

        // Check if module is unlocked
        if (!$this->isUnlockedForUser($userId)) {
            return [
                'can_take' => false,
                'reason' => 'module_locked',
                'message' => 'You must complete the previous module first.',
            ];
        }

        // Check if all content is completed
        $progress = $this->getUserProgress($userId);
        if (!$progress['content_completed']) {
            $remaining = $progress['total_content'] - $progress['completed_content'];
            return [
                'can_take' => false,
                'reason' => 'content_incomplete',
                'message' => "You must complete all module content first. {$remaining} item(s) remaining.",
                'completed' => $progress['completed_content'],
                'total' => $progress['total_content'],
            ];
        }

        // Check quiz-specific restrictions (attempts, delay, deadline)
        $canAttempt = $quiz->canUserAttempt($userId);
        
        return [
            'can_take' => $canAttempt['can_attempt'],
            'reason' => $canAttempt['reason'],
            'message' => $canAttempt['message'],
            'attempts_used' => $canAttempt['attempts_used'],
            'max_attempts' => $canAttempt['max_attempts'],
            'attempts_remaining' => $canAttempt['attempts_remaining'] ?? null,
            'next_attempt_at' => $canAttempt['next_attempt_at'] ?? null,
        ];
    }

    /**
     * Get the quiz result for a user.
     */
    public function getUserQuizResult(int $userId): ?ModuleQuizResult
    {
        return ModuleQuizResult::getUserModuleResult($userId, $this->id);
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

    /**
     * Scope to get modules with quizzes.
     */
    public function scopeWithQuiz($query)
    {
        return $query->where('has_quiz', true);
    }

    /**
     * Scope to get modules with required quizzes.
     */
    public function scopeWithRequiredQuiz($query)
    {
        return $query->where('has_quiz', true)->where('quiz_required', true);
    }

    public function contents()
    {
        return $this->hasMany(ModuleContent::class, 'module_id');
    }

    // === COMPUTED ATTRIBUTES ===

    /**
     * Check if module has an active quiz.
     */
    public function getHasActiveQuizAttribute(): bool
    {
        return $this->has_quiz && $this->quiz()->exists();
    }

    /**
     * Get quiz completion summary for display.
     */
    public function getQuizSummaryAttribute(): ?array
    {
        if (!$this->has_quiz) {
            return null;
        }

        $quiz = $this->quiz;
        if (!$quiz) {
            return [
                'status' => 'not_created',
                'message' => 'Quiz not yet created',
            ];
        }

        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'questions_count' => $quiz->questions()->count(),
            'total_points' => $quiz->total_points,
            'pass_threshold' => $quiz->pass_threshold,
            'time_limit' => $quiz->time_limit_minutes,
            'max_attempts' => $quiz->max_attempts,
            'status' => $quiz->status,
        ];
    }
}
