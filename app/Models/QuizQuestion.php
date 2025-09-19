<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['quiz_id', 'question_text', 'type', 'points', 'options', 'correct_answer', 'order', 'correct_answer_explanation'];
    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the quiz that owns the question.
     */
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Get the answers for the question.
     */
    public function answers()
    {
        return $this->hasMany(QuizAnswer::class);
    }

    /**
     * Check if the provided answer is correct (for radio/checkbox).
     */


    public function isCorrect($userAnswer)
    {
        // Helper function to convert any input to array
        $toArray = function ($value) {
            if (is_string($value)) {
                // Try to decode JSON first
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return is_array($decoded) ? $decoded : [$decoded];
                }
                return [$value];
            }
            return is_array($value) ? $value : [$value];
        };

        $correctAnswer = $toArray($this->correct_answer);
        $userAnswer = $toArray($userAnswer);

        if (empty($correctAnswer) || empty($userAnswer)) {
            return false;
        }

        if ($this->type === 'radio') {
            // For radio buttons: both should have exactly one value and they should match
            return count($correctAnswer) === 1 &&
                count($userAnswer) === 1 &&
                trim(strtolower($correctAnswer[0])) === trim(strtolower($userAnswer[0]));
        }

        if ($this->type === 'checkbox') {
            // For checkboxes: sort both arrays and compare
            $correct = array_map('trim', array_map('strtolower', $correctAnswer));
            $user = array_map('trim', array_map('strtolower', $userAnswer));
            sort($correct);
            sort($user);
            return $correct === $user;
        }

        if ($this->type === 'text') {
            // For text: compare as trimmed lowercase strings
            $correctText = trim(strtolower(implode(' ', $correctAnswer)));
            $userText = trim(strtolower(implode(' ', $userAnswer)));
            return $correctText === $userText;
        }

        return false;
    }

}
