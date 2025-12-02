<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['quiz_id', 'question_text', 'type', 'points', 'options', 'correct_answer', 'order', 'correct_answer_explanation'];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the options attribute - handles double-encoded JSON
     */
    protected function options(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $this->decodeJsonValue($value);
            },
            set: function ($value) {
                // If it's already an array, encode it once
                if (is_array($value)) {
                    return json_encode($value);
                }
                // If it's null, return null
                if ($value === null) {
                    return null;
                }
                // If it's a string, assume it's already JSON
                return $value;
            }
        );
    }

    /**
     * Get the correct_answer attribute - handles double-encoded JSON
     */
    protected function correctAnswer(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                return $this->decodeJsonValue($value);
            },
            set: function ($value) {
                // If it's already an array, encode it once
                if (is_array($value)) {
                    return json_encode($value);
                }
                // If it's null, return null
                if ($value === null) {
                    return null;
                }
                // If it's a string, assume it's already JSON
                return $value;
            }
        );
    }

    /**
     * Helper to decode potentially double-encoded JSON values
     */
    private function decodeJsonValue($value)
    {
        if ($value === null) {
            return null;
        }

        // If it's already an array, return it
        if (is_array($value)) {
            return $value;
        }

        // Try to decode the JSON
        $decoded = $value;
        $maxIterations = 5; // Prevent infinite loops
        $iterations = 0;

        while (is_string($decoded) && $iterations < $maxIterations) {
            $iterations++;
            $tryDecode = json_decode($decoded, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                $decoded = $tryDecode;
            } else {
                // Can't decode further, break
                break;
            }
        }

        // Ensure we return an array or null
        if (is_array($decoded)) {
            return $decoded;
        }

        // If we still have a string, wrap it in an array
        if (is_string($decoded) && !empty($decoded)) {
            return [$decoded];
        }

        return [];
    }

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
