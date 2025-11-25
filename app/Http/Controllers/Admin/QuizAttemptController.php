<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class QuizAttemptController extends Controller
{
    /**
     * Display a listing of quiz attempts.
     */
    public function index()
    {
        $attempts = QuizAttempt::with(['user', 'quiz'])
            ->filter(request()->only(['user_id', 'quiz_id', 'passed']))
            ->orderBy('completed_at', 'desc')
            ->paginate(15);

        $attempts->getCollection()->transform(function ($attempt) {
            return [
                'id' => $attempt->id,
                'user' => [
                    'id' => $attempt->user->id,
                    'name' => $attempt->user->name,
                    'email' => $attempt->user->email,
                ],
                'quiz' => [
                    'id' => $attempt->quiz->id,
                    'title' => $attempt->quiz->title,
                    'total_points' => $attempt->quiz->total_points,
                    'pass_threshold' => $attempt->quiz->pass_threshold ?? 80.00,
                ],
                'score' => $attempt->score,
                'manual_score' => $attempt->manual_score ?? 0,
                'total_score' => $attempt->total_score ?? ($attempt->score + ($attempt->manual_score ?? 0)),
                'passed' => $attempt->passed,
                'completed_at' => $attempt->completed_at ? $attempt->completed_at->format('Y-m-d H:i:s') : null,
            ];
        });

        $quiz = $attempts->isNotEmpty() ? $attempts->first()['quiz'] : null;

        // Generate breadcrumbs in controller
        $breadcrumbs = [];
        if ($quiz) {
            $breadcrumbs = [
                ['name' => 'Quizzes', 'route' => 'admin.quizzes.index'],
                ['name' => $quiz['title'], 'route' => 'admin.quizzes.show', 'params' => $quiz['id']],
                ['name' => 'Attempts', 'route' => null],
            ];
        }

        return Inertia::render('Admin/QuizAttempts/Index', [
            'attempts' => $attempts,
            'filters' => request()->only(['user_id', 'quiz_id', 'passed']),
            'quiz' => $quiz,
            'breadcrumbs' => $breadcrumbs, // Pass breadcrumbs from controller
        ]);
    }


    /**
     * Display the specified quiz attempt.
     */
    public function show(QuizAttempt $attempt)
    {
        $attempt->load(['user', 'quiz', 'answers' => function($query) {
            $query->with('question');
        }]);

        $responses = $attempt->answers->map(function ($answer) {
            // Check if question exists before accessing its properties
            if (!$answer->question) {
                return null; // Skip this answer if question doesn't exist
            }

            return [
                'id' => $answer->id,
                'question_id' => $answer->question_id,
                'question' => [
                    'id' => $answer->question->id,
                    'question_text' => $answer->question->question_text,
                    'points' => $answer->question->points ?? 0,
                    'type' => $answer->question->type,
                    'correct_answer' => $answer->question->correct_answer ?? [],
                    'correct_answer_explanation' => $answer->question->correct_answer_explanation ?? '', // Add explanation
                ],
                'answer' => is_string($answer->answer) ? $answer->answer : json_encode($answer->answer),
                'is_correct' => $answer->is_correct,
                'points_earned' => $answer->points_earned ?? 0,
            ];
        })->filter(); // Remove null values

        return Inertia::render('Admin/QuizAttempts/Show', [
            'attempt' => [
                'id' => $attempt->id,
                'user' => [
                    'id' => $attempt->user->id,
                    'name' => $attempt->user->name,
                    'email' => $attempt->user->email,
                ],
                'quiz' => [
                    'id' => $attempt->quiz->id,
                    'title' => $attempt->quiz->title,
                    'total_points' => $attempt->quiz->total_points,
                    'pass_threshold' => $attempt->quiz->pass_threshold ?? 80.00, // Add pass threshold
                ],
                'score' => $attempt->score,
                'manual_score' => $attempt->manual_score ?? 0,
                'total_score' => $attempt->total_score ?? ($attempt->score + ($attempt->manual_score ?? 0)),
                'passed' => $attempt->passed,
                'completed_at' => $attempt->completed_at ? $attempt->completed_at->format('Y-m-d H:i:s') : null,
                'responses' => $responses,
            ],
        ]);
    }

    /**
     * Update the specified quiz attempt with manual scores.
     */
    public function update(Request $request, QuizAttempt $attempt)
    {
        $request->validate([
            'manual_scores.*' => 'nullable|integer|min:0|max:100',
        ]);

        $manualScores = $request->input('manual_scores', []);
        $totalManualScore = 0;

        foreach ($attempt->answers as $answer) {
            if ($answer->question->type === 'text' && isset($manualScores[$answer->id])) {
                $manualScore = $manualScores[$answer->id];
                $answer->update(['points_earned' => $manualScore]);
                $totalManualScore += $manualScore;
            }
        }

        // Update the attempt with new scores and pass status
        $autoScore = $attempt->calculateScore();
        $totalScore = $autoScore + $totalManualScore;
        $passThreshold = $attempt->quiz->pass_threshold ?? 80.00; // Get dynamic pass threshold
        $requiredScore = ($passThreshold / 100) * $attempt->quiz->total_points;

        $attempt->update([
            'score' => $autoScore,
            'manual_score' => $totalManualScore,
            'total_score' => $totalScore,
            'passed' => $totalScore >= $requiredScore, // Recalculate based on pass_threshold
        ]);

        return redirect()->back()->with('success', 'Manual scores updated successfully.');
    }
}
