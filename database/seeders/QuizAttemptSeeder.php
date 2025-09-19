<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Quiz;
use App\Models\User;
use App\Models\QuizAttempt;
use App\Models\QuizAnswer;
use App\Models\QuizQuestion;
use Illuminate\Support\Facades\Hash;

class QuizAttemptSeeder extends Seeder
{
    public function run()
    {
        // Create or find a sample user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Create a sample quiz
        $quiz = Quiz::create([
            'course_id' => 1, // Assume a course exists, adjust if needed
            'title' => 'Sample Quiz',
            'description' => 'A test quiz',
            'status' => 'published',
            'total_points' => 100,
        ]);

        // Create sample questions
        $questions = [
            ['question_text' => 'What is 2+2?', 'type' => 'radio', 'points' => 50, 'options' => json_encode(['4', '5', '6']), 'correct_answer' => json_encode(['4'])],
            ['question_text' => 'Name a fruit?', 'type' => 'text', 'points' => null, 'options' => null, 'correct_answer' => null],
        ];
        foreach ($questions as $questionData) {
            $quiz->questions()->create($questionData);
        }

        // Create a sample attempt
        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'attempt_number' => 1,
            'score' => 50,
            'manual_score' => null,
            'total_score' => 50,
            'passed' => false,
            'completed_at' => now(),
        ]);

        // Create sample answers
        $answers = [
            ['quiz_question_id' => $quiz->questions[0]->id, 'answer' => json_encode(['4']), 'is_correct' => true, 'points_earned' => 50],
            ['quiz_question_id' => $quiz->questions[1]->id, 'answer' => 'Apple', 'is_correct' => null, 'points_earned' => null],
        ];
        foreach ($answers as $answerData) {
            $attempt->answers()->create($answerData);
        }
    }
}
