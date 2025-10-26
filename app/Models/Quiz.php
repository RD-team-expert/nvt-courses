<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        // Basic quiz fields
        'course_id',
        'title',
        'description',
        'status',
        'total_points',
        'pass_threshold',

        // NEW: Online Course Support
        'course_online_id',
        'courseable_type',
        'courseable_id',

        // NEW: Deadline Support
        'deadline',
        'has_deadline',
        'enforce_deadline',
        'time_limit_minutes',
        'allows_extensions'
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
     */
    private static function validateCourseAssignment($quiz)
    {
        $hasCourse = !empty($quiz->course_id);
        $hasOnlineCourse = !empty($quiz->course_online_id);

        if ($hasCourse && $hasOnlineCourse) {
            throw new \InvalidArgumentException('Quiz can only be assigned to either a regular course OR an online course, not both.');
        }

        if (!$hasCourse && !$hasOnlineCourse) {
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
}
