<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ModuleQuizResult Model
 * 
 * Stores quiz results for each user per module per attempt.
 * This provides quick lookup for module quiz status.
 */
class ModuleQuizResult extends Model
{
    use HasFactory;

    protected $table = 'module_quiz_results';

    protected $fillable = [
        'user_id',
        'module_id',
        'quiz_id',
        'quiz_attempt_id',
        'passed',
        'score_percentage',
        'points_earned',
        'total_points',
        'completed_at',
        'time_taken_seconds',
    ];

    protected $casts = [
        'score_percentage' => 'decimal:2',
        'points_earned' => 'integer',
        'total_points' => 'integer',
        'passed' => 'boolean',
        'completed_at' => 'datetime',
        'time_taken_seconds' => 'integer',
    ];

    // === RELATIONSHIPS ===

    /**
     * Get the user who took the quiz.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the module this result belongs to.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    /**
     * Get the quiz this result is for.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    // === SCOPES ===

    /**
     * Scope to get passed results only.
     */
    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    /**
     * Scope to get results for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get results for a specific module.
     */
    public function scopeForModule($query, int $moduleId)
    {
        return $query->where('module_id', $moduleId);
    }

    // === RELATIONSHIPS ===

    /**
     * Get the quiz attempt this result is for.
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }

    // === STATIC METHODS ===

    /**
     * Create a result record after a quiz attempt.
     */
    public static function updateFromAttempt(QuizAttempt $attempt): self
    {
        $quiz = $attempt->quiz;
        
        if (!$quiz->isModuleQuiz()) {
            throw new \InvalidArgumentException('This method is only for module quizzes.');
        }

        // Calculate percentage
        $percentage = $quiz->total_points > 0 
            ? ($attempt->total_score / $quiz->total_points) * 100 
            : 0;

        // Calculate time taken
        $timeTaken = null;
        if ($attempt->started_at && $attempt->completed_at) {
            $timeTaken = $attempt->started_at->diffInSeconds($attempt->completed_at);
        } elseif ($attempt->created_at && $attempt->completed_at) {
            // Fallback to created_at if started_at is not set
            $timeTaken = $attempt->created_at->diffInSeconds($attempt->completed_at);
        }

        // Create new result for this attempt
        return self::create([
            'user_id' => $attempt->user_id,
            'module_id' => $quiz->module_id,
            'quiz_id' => $quiz->id,
            'quiz_attempt_id' => $attempt->id,
            'passed' => $attempt->passed,
            'score_percentage' => $percentage,
            'points_earned' => $attempt->total_score,
            'total_points' => $quiz->total_points,
            'completed_at' => $attempt->completed_at ?? now(),
            'time_taken_seconds' => $timeTaken,
        ]);
    }

    /**
     * Check if a user has passed a specific module's quiz.
     */
    public static function hasUserPassedModule(int $userId, int $moduleId): bool
    {
        return self::where('user_id', $userId)
            ->where('module_id', $moduleId)
            ->where('passed', true)
            ->exists();
    }

    /**
     * Get user's best result for a specific module.
     */
    public static function getUserModuleResult(int $userId, int $moduleId): ?self
    {
        return self::where('user_id', $userId)
            ->where('module_id', $moduleId)
            ->orderByDesc('score_percentage')
            ->first();
    }

    /**
     * Get user's best score for a module.
     */
    public static function getUserBestScore(int $userId, int $moduleId): ?array
    {
        $best = self::where('user_id', $userId)
            ->where('module_id', $moduleId)
            ->orderByDesc('score_percentage')
            ->first();

        if (!$best) {
            return null;
        }

        return [
            'score' => $best->points_earned,
            'percentage' => $best->score_percentage,
            'passed' => $best->passed,
        ];
    }

    /**
     * Get attempt count for a user on a module.
     */
    public static function getUserAttemptCount(int $userId, int $moduleId): int
    {
        return self::where('user_id', $userId)
            ->where('module_id', $moduleId)
            ->count();
    }
}