<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = ['quiz_id', 'user_id', 'attempt_number', 'score', 'manual_score', 'total_score', 'passed', 'completed_at'];

    protected $casts = [
        'completed_at' => 'datetime',
        'passed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the quiz that owns the attempt.
     */

    public function scopeFilter($query, array $filters)
    {
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['quiz_id'])) {
            $query->where('quiz_id', $filters['quiz_id']);
        }

        if (isset($filters['passed']) && $filters['passed'] !== '') {
            $query->where('passed', (bool) $filters['passed']);
        }

        return $query;
    }
    public function answers()
    {
        return $this->hasMany(QuizAnswer::class, 'quiz_attempt_id');
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function calculateScore()
    {
        return $this->answers()
            ->whereHas('question', function ($query) {
                $query->where('type', '!=', 'text');
            })
            ->where('is_correct', true)
            ->sum('points_earned');
    }

    public function isPassed()
    {
        // Make sure quiz relationship is loaded
        if (!$this->quiz || !$this->quiz->total_points) {
            return false;
        }

        // Calculate passing threshold using quiz's pass_threshold (e.g., 80.00 means 80%)
        $passingThreshold = ($this->quiz->pass_threshold / 100) * $this->quiz->total_points;

        // Return true if total score meets or exceeds threshold
        return $this->total_score >= $passingThreshold;
    }

    // Update scores and pass status
    public function updateScores()
    {
        $autoScore = $this->calculateScore();
        $totalScore = $autoScore + ($this->manual_score ?? 0);

        $this->update([
            'score' => $autoScore,
            'total_score' => $totalScore,
            'passed' => $this->isPassed(),
        ]);
    }
}
