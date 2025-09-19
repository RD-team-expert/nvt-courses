<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'title', 'description', 'status', 'total_points', 'pass_threshold'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the course that owns the quiz.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the questions for the quiz.
     */
    public function questions()
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    /**
     * Get the attempts for the quiz.
     */
    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Calculate total points for the quiz (excluding text questions).
     */

    /**
     * Scope to get only published quizzes.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function calculateTotalPoints()
    {
        $this->total_points = $this->questions()->sum('points');
        $this->save();
        return $this->total_points;
    }

}
