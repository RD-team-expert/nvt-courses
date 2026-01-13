<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Quiz extends Model
{
    use HasFactory;

    // Show correct answers options
    const SHOW_ANSWERS_NEVER = 'never';
    const SHOW_ANSWERS_AFTER_PASS = 'after_pass';
    const SHOW_ANSWERS_AFTER_MAX_ATTEMPTS = 'after_max_attempts';
    const SHOW_ANSWERS_ALWAYS = 'always';

    protected $fillable = [
        // Basic quiz fields
        'course_id',
        'title',
        'description',
        'status',
        'total_points',
        'pass_threshold',

        // Online Course Support
        'course_online_id',
        'courseable_type',
        'courseable_id',

        // Deadline Support
        'deadline',
        'has_deadline',
        'enforce_deadline',
        'time_limit_minutes',
        'allows_extensions',

        // NEW: Module Quiz Support
        'module_id',
        'is_module_quiz',
        'required_to_proceed',
        'max_attempts',
        'retry_delay_hours',
        'show_correct_answers',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deadline' => 'datetime',
        'has_deadline' => 'boolean',
        'enforce_deadline' => 'boolean',
        'allows_extensions' => 'boolean',
        'total_points' => 'integer',
        'pass_threshold' => 'decimal:2',
        // NEW: Module quiz casts
        'is_module_quiz' => 'boolean',
        'required_to_proceed' => 'boolean',
        'max_attempts' => 'integer',
        'retry_delay_hours' => 'integer',
    ];

    // === MODEL BOOT METHODS ===

    protected static function booted()
    {
        static::creating(function ($quiz) {
            self::validateCourseAssignment($quiz);
        });

        static::updating(function ($quiz) {
            self::validateCourseAssignment($quiz);
        });
    }

    /**
     * Validate that only one course type is assigned
     * Updated to allow module quizzes (which inherit course from module)
     */
    private static function validateCourseAssignment($quiz)
    {
        // Module quizzes get their course from the module, so skip validation
        if ($quiz->is_module_quiz && !empty($quiz->module_id)) {
            return;
        }

        $hasCourse = !empty($quiz->course_id);
        $hasOnlineCourse = !empty($quiz->course_online_id);

        if ($hasCourse && $hasOnlineCourse) {
            throw new \InvalidArgumentException('Quiz can only be assigned to either a regular course OR an online course, not both.');
        }

        // Only require course assignment for non-module quizzes
        if (!$quiz->is_module_quiz && !$hasCourse && !$hasOnlineCourse) {
            throw new \InvalidArgumentException('Quiz must be assigned to either a regular course or an online course.');
        }
    }

    // === RELATIONSHIPS ===

    /**
     * Get the regular course that owns the quiz.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the online course that owns the quiz.
     */
    public function courseOnline(): BelongsTo
    {
        return $this->belongsTo(CourseOnline::class);
    }

    /**
     * Polymorphic relationship (future-proof)
     */
    public function courseable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the questions for the quiz.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    /**
     * Get the attempts for the quiz.
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Get the module that owns this quiz (for module quizzes).
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    /**
     * Get the module quiz results for this quiz.
     */
    public function moduleResults(): HasMany
    {
        return $this->hasMany(ModuleQuizResult::class);
    }

    // === COURSE TYPE HELPERS ===

    /**
     * Get the associated course (either regular or online)
     */
    public function getAssociatedCourse()
    {
        if ($this->courseable) {
            return $this->courseable; // Polymorphic
        }

        return $this->course_online_id ? $this->courseOnline : $this->course;
    }

    /**
     * Get course type
     */
    public function getCourseType(): string
    {
        if ($this->courseable_type) {
            return $this->courseable_type === 'App\Models\Course' ? 'regular' : 'online';
        }

        return $this->course_online_id ? 'online' : 'regular';
    }

    /**
     * Check if quiz belongs to regular course
     */
    public function isRegularCourse(): bool
    {
        return !empty($this->course_id);
    }

    /**
     * Check if quiz belongs to online course
     */
    public function isOnlineCourse(): bool
    {
        return !empty($this->course_online_id);
    }

    /**
     * Check if this is a module-level quiz
     */
    public function isModuleQuiz(): bool
    {
        return $this->is_module_quiz && !empty($this->module_id);
    }

    // === MODULE QUIZ HELPER METHODS ===

    /**
     * Check if user can attempt this quiz
     * Returns array with can_attempt status and details
     */
    public function canUserAttempt(int $userId): array
    {
        // Check max attempts
        $attemptCount = $this->attempts()
            ->where('user_id', $userId)
            ->count();

        if ($this->max_attempts && $attemptCount >= $this->max_attempts) {
            return [
                'can_attempt' => false,
                'reason' => 'maximum_attempts_reached',
                'message' => "You have used all {$this->max_attempts} attempts for this quiz.",
                'attempts_used' => $attemptCount,
                'max_attempts' => $this->max_attempts,
                'attempts_remaining' => 0,
            ];
        }

        // Check retry delay
        if ($this->retry_delay_hours > 0 && $attemptCount > 0) {
            $lastAttempt = $this->attempts()
                ->where('user_id', $userId)
                ->latest()
                ->first();

            if ($lastAttempt) {
                $nextAttemptTime = $lastAttempt->created_at->addHours($this->retry_delay_hours);
                
                if (now()->lt($nextAttemptTime)) {
                    $hoursRemaining = now()->diffInHours($nextAttemptTime, false);
                    $minutesRemaining = now()->diffInMinutes($nextAttemptTime) % 60;
                    
                    return [
                        'can_attempt' => false,
                        'reason' => 'retry_delay_not_elapsed',
                        'message' => "Please wait before retrying. Next attempt available in {$hoursRemaining}h {$minutesRemaining}m.",
                        'next_attempt_at' => $nextAttemptTime->toIso8601String(),
                        'hours_remaining' => $hoursRemaining,
                        'minutes_remaining' => $minutesRemaining,
                        'attempts_used' => $attemptCount,
                        'max_attempts' => $this->max_attempts,
                    ];
                }
            }
        }

        // Check deadline
        if (!$this->isAvailableForTaking()) {
            return [
                'can_attempt' => false,
                'reason' => 'deadline_passed',
                'message' => 'The deadline for this quiz has passed.',
                'attempts_used' => $attemptCount,
                'max_attempts' => $this->max_attempts,
            ];
        }

        return [
            'can_attempt' => true,
            'reason' => null,
            'message' => 'You can take this quiz.',
            'attempts_used' => $attemptCount,
            'max_attempts' => $this->max_attempts,
            'attempts_remaining' => $this->max_attempts ? ($this->max_attempts - $attemptCount) : null,
        ];
    }

    /**
     * Check if user has passed this quiz
     */
    public function hasUserPassed(int $userId): bool
    {
        return $this->attempts()
            ->where('user_id', $userId)
            ->where('passed', true)
            ->exists();
    }

    /**
     * Get user's best attempt for this quiz
     */
    public function getUserBestAttempt(int $userId): ?QuizAttempt
    {
        return $this->attempts()
            ->where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->orderByDesc('total_score')
            ->first();
    }

    /**
     * Get user's latest attempt for this quiz
     */
    public function getUserLatestAttempt(int $userId): ?QuizAttempt
    {
        return $this->attempts()
            ->where('user_id', $userId)
            ->latest()
            ->first();
    }

    /**
     * Determine if correct answers should be shown to user
     */
    public function shouldShowCorrectAnswers(int $userId): bool
    {
        switch ($this->show_correct_answers) {
            case self::SHOW_ANSWERS_ALWAYS:
                return true;

            case self::SHOW_ANSWERS_AFTER_PASS:
                return $this->hasUserPassed($userId);

            case self::SHOW_ANSWERS_AFTER_MAX_ATTEMPTS:
                $attemptCount = $this->attempts()
                    ->where('user_id', $userId)
                    ->count();
                return $this->hasUserPassed($userId) ||
                       ($this->max_attempts && $attemptCount >= $this->max_attempts);

            case self::SHOW_ANSWERS_NEVER:
            default:
                return false;
        }
    }

    /**
     * Get user's quiz status summary
     */
    public function getUserStatus(int $userId): array
    {
        $attemptCount = $this->attempts()
            ->where('user_id', $userId)
            ->count();
        
        $passed = $this->hasUserPassed($userId);
        $canAttempt = $this->canUserAttempt($userId);
        $latestAttempt = $this->getUserLatestAttempt($userId);
        $bestAttempt = $this->getUserBestAttempt($userId);

        $status = 'not_started';
        if ($passed) {
            $status = 'passed';
        } elseif ($attemptCount > 0) {
            $status = $canAttempt['can_attempt'] ? 'in_progress' : 'failed';
        }

        return [
            'status' => $status,
            'passed' => $passed,
            'attempts_used' => $attemptCount,
            'max_attempts' => $this->max_attempts,
            'attempts_remaining' => $this->max_attempts ? max(0, $this->max_attempts - $attemptCount) : null,
            'can_attempt' => $canAttempt['can_attempt'],
            'attempt_message' => $canAttempt['message'],
            'show_correct_answers' => $this->shouldShowCorrectAnswers($userId),
            'latest_attempt' => $latestAttempt ? [
                'id' => $latestAttempt->id,
                'score' => $latestAttempt->total_score,
                'score_percentage' => $this->total_points > 0
                    ? round(($latestAttempt->total_score / $this->total_points) * 100, 1)
                    : 0,
                'passed' => $latestAttempt->passed,
                'completed_at' => $latestAttempt->completed_at?->toIso8601String(),
            ] : null,
            'best_attempt' => $bestAttempt ? [
                'id' => $bestAttempt->id,
                'score' => $bestAttempt->total_score,
                'score_percentage' => $this->total_points > 0
                    ? round(($bestAttempt->total_score / $this->total_points) * 100, 1)
                    : 0,
                'passed' => $bestAttempt->passed,
            ] : null,
        ];
    }

    // === DEADLINE HELPER METHODS ===

    /**
     * Check if quiz is past deadline
     */
    public function isPastDeadline(): bool
    {
        if (!$this->has_deadline || !$this->deadline) {
            return false;
        }

        return now()->isAfter($this->deadline);
    }

    /**
     * Check if quiz is available for taking
     */
    public function isAvailableForTaking(): bool
    {
        if (!$this->has_deadline || !$this->enforce_deadline) {
            return true;
        }

        return !$this->isPastDeadline();
    }

    /**
     * Get time remaining until deadline
     */
    public function getTimeUntilDeadline(): ?string
    {
        if (!$this->has_deadline || !$this->deadline) {
            return null;
        }

        if ($this->isPastDeadline()) {
            return 'Deadline passed';
        }

        return $this->deadline->diffForHumans();
    }

    /**
     * Get formatted deadline
     */
    public function getFormattedDeadline(): ?string
    {
        if (!$this->has_deadline || !$this->deadline) {
            return null;
        }

        return $this->deadline->format('l, F j, Y \a\t g:i A');
    }

    /**
     * Get deadline status for display
     */
    public function getDeadlineStatus(): array
    {
        if (!$this->has_deadline || !$this->deadline) {
            return [
                'has_deadline' => false,
                'message' => 'No deadline set',
                'status' => 'none',
                'css_class' => 'text-gray-500'
            ];
        }

        if ($this->isPastDeadline()) {
            return [
                'has_deadline' => true,
                'status' => 'expired',
                'message' => 'Deadline has passed',
                'css_class' => 'text-red-600'
            ];
        }

        $hoursLeft = now()->diffInHours($this->deadline);

        if ($hoursLeft <= 24) {
            return [
                'has_deadline' => true,
                'status' => 'urgent',
                'message' => 'Due ' . $this->getTimeUntilDeadline(),
                'css_class' => 'text-red-500'
            ];
        } elseif ($hoursLeft <= 72) {
            return [
                'has_deadline' => true,
                'status' => 'soon',
                'message' => 'Due ' . $this->getTimeUntilDeadline(),
                'css_class' => 'text-orange-500'
            ];
        } else {
            return [
                'has_deadline' => true,
                'status' => 'normal',
                'message' => 'Due ' . $this->getTimeUntilDeadline(),
                'css_class' => 'text-green-600'
            ];
        }
    }

    /**
     * Get detailed time remaining until deadline
     */
    public function getDetailedTimeRemaining(): ?array
    {
        if (!$this->has_deadline || !$this->deadline) {
            return null;
        }

        if ($this->isPastDeadline()) {
            return [
                'status' => 'expired',
                'message' => 'Deadline has passed',
                'overdue_by' => $this->deadline->diffForHumans()
            ];
        }

        $now = now();
        $deadline = $this->deadline;

        $totalHours = $now->diffInHours($deadline);
        $days = intval($totalHours / 24);
        $hours = $totalHours % 24;
        $minutes = $now->diffInMinutes($deadline) % 60;

        return [
            'status' => 'active',
            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes,
            'total_hours' => $totalHours,
            'formatted' => $deadline->format('M j, Y \a\t g:i A'),
            'human' => $deadline->diffForHumans()
        ];
    }

    // === QUIZ CALCULATION METHODS ===

    /**
     * Calculate total points for the quiz (excluding text questions).
     */
    public function calculateTotalPoints(): int
    {
        $this->total_points = $this->questions()->where('type', '!=', 'text')->sum('points');
        $this->save();
        return $this->total_points;
    }

    /**
     * Get passing score based on total points and threshold
     */
    public function getPassingScore(): float
    {
        return ($this->total_points * $this->pass_threshold) / 100;
    }

    /**
     * Check if a score is passing
     */
    public function isPassingScore(float $score): bool
    {
        return $score >= $this->getPassingScore();
    }

    // === SCOPES ===

    /**
     * Scope to get only published quizzes.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope to get quizzes with deadlines
     */
    public function scopeWithDeadlines($query)
    {
        return $query->where('has_deadline', true)->whereNotNull('deadline');
    }

    /**
     * Scope to get quizzes for regular courses
     */
    public function scopeForRegularCourses($query)
    {
        return $query->whereNotNull('course_id');
    }

    /**
     * Scope to get quizzes for online courses
     */
    public function scopeForOnlineCourses($query)
    {
        return $query->whereNotNull('course_online_id');
    }

    /**
     * Scope to get module quizzes only
     */
    public function scopeModuleQuizzes($query)
    {
        return $query->where('is_module_quiz', true);
    }

    /**
     * Scope to get quizzes for a specific module
     */
    public function scopeForModule($query, int $moduleId)
    {
        return $query->where('module_id', $moduleId);
    }

    /**
     * Scope to get course-level quizzes (not module quizzes)
     */
    public function scopeCourseLevelQuizzes($query)
    {
        return $query->where(function($q) {
            $q->where('is_module_quiz', false)
              ->orWhereNull('is_module_quiz');
        });
    }

    /**
     * Scope to get expired quizzes
     */
    public function scopeExpired($query)
    {
        return $query->withDeadlines()
            ->where('deadline', '<', now());
    }

    /**
     * Scope to get quizzes expiring soon
     */
    public function scopeExpiringSoon($query, $hours = 24)
    {
        return $query->withDeadlines()
            ->where('deadline', '>', now())
            ->where('deadline', '<=', now()->addHours($hours));
    }

    /**
     * Scope to get available quizzes (not expired or soft deadline)
     */
    public function scopeAvailable($query)
    {
        return $query->where(function($q) {
            $q->where('has_deadline', false)
                ->orWhere('enforce_deadline', false)
                ->orWhere('deadline', '>', now());
        });
    }

    // === ACCESSOR ATTRIBUTES ===

    /**
     * Get quiz difficulty based on total points and question count
     */
    public function getDifficultyAttribute(): string
    {
        $questionCount = $this->questions()->count();
        $avgPointsPerQuestion = $questionCount > 0 ? $this->total_points / $questionCount : 0;

        if ($avgPointsPerQuestion >= 10) {
            return 'Hard';
        } elseif ($avgPointsPerQuestion >= 5) {
            return 'Medium';
        } else {
            return 'Easy';
        }
    }

    /**
     * Get estimated completion time in minutes
     */
    public function getEstimatedTimeAttribute(): int
    {
        $questionCount = $this->questions()->count();

        // Estimate: 2 minutes per question + 5 minutes buffer
        $estimated = ($questionCount * 2) + 5;

        // If time limit is set and lower, use that
        if ($this->time_limit_minutes && $this->time_limit_minutes < $estimated) {
            return $this->time_limit_minutes;
        }

        return $estimated;
    }

    // === UTILITY METHODS ===

    /**
     * Check if quiz can be edited (no attempts yet)
     */
    public function canBeEdited(): bool
    {
        return $this->attempts()->count() === 0;
    }

    /**
     * Check if quiz can be deleted
     */
    public function canBeDeleted(): bool
    {
        return $this->attempts()->count() === 0;
    }

    /**
     * Get quiz statistics
     */
    public function getStatistics(): array
    {
        $attempts = $this->attempts()->completed();

        return [
            'total_attempts' => $attempts->count(),
            'average_score' => $attempts->avg('total_score') ?? 0,
            'highest_score' => $attempts->max('total_score') ?? 0,
            'lowest_score' => $attempts->min('total_score') ?? 0,
            'pass_rate' => $attempts->count() > 0
                ? ($attempts->where('passed', true)->count() / $attempts->count()) * 100
                : 0,
        ];
    }

    /**
     * Reset quiz attempts for a specific user
     * This allows the user to retake the quiz from scratch
     */
    public function resetUserAttempts(int $userId): bool
    {
        try {
            // Delete all quiz answers for this user's attempts
            $attemptIds = $this->attempts()
                ->where('user_id', $userId)
                ->pluck('id');

            if ($attemptIds->isNotEmpty()) {
                // Delete answers first (foreign key constraint)
                QuizAnswer::whereIn('quiz_attempt_id', $attemptIds)->delete();
                
                // Delete module quiz results if this is a module quiz
                if ($this->isModuleQuiz()) {
                    ModuleQuizResult::where('quiz_id', $this->id)
                        ->where('user_id', $userId)
                        ->delete();
                }
                
                // Delete the attempts
                $this->attempts()
                    ->where('user_id', $userId)
                    ->delete();
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to reset quiz attempts', [
                'quiz_id' => $this->id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
