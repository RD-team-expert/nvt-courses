<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CourseModule;
use App\Models\CourseOnlineAssignment;
use App\Models\ModuleQuizResult;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * User ModuleQuizController
 * 
 * Handles the user-facing quiz taking experience for module quizzes.
 * Users can view quiz info, start attempts, submit answers, and view results.
 */
class ModuleQuizController extends Controller
{
    /**
     * Display the quiz start page with instructions and status.
     */
    public function show($courseOnline, CourseModule $courseModule)
    {
        $user = Auth::user();
        
        // Check if user is assigned to this course
        $assignment = CourseOnlineAssignment::where('course_online_id', $courseModule->course_online_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$assignment) {
            return redirect()->route('courses-online.index')
                ->with('error', 'You are not enrolled in this course.');
        }

        // Check if module has a quiz
        if (!$courseModule->has_quiz) {
            return redirect()->route('courses-online.show', $courseModule->course_online_id)
                ->with('error', 'This module does not have a quiz.');
        }

        $quiz = $courseModule->quiz;
        if (!$quiz || $quiz->status !== 'published') {
            return redirect()->route('courses-online.show', $courseModule->course_online_id)
                ->with('error', 'The quiz for this module is not available yet.');
        }

        // Check if user can take the quiz
        $canTakeQuiz = $courseModule->canUserTakeQuiz($user->id);
        $quizStatus = $courseModule->getQuizStatus($user->id);

        $courseModule->load('courseOnline');

        return Inertia::render('User/ModuleQuiz/Show', [
            'module' => [
                'id' => $courseModule->id,
                'name' => $courseModule->name,
                'order_number' => $courseModule->order_number,
            ],
            'course' => [
                'id' => $courseModule->courseOnline->id,
                'name' => $courseModule->courseOnline->name,
            ],
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'total_points' => $quiz->total_points,
                'pass_threshold' => $quiz->pass_threshold,
                'passing_score' => $quiz->getPassingScore(),
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'max_attempts' => $quiz->max_attempts,
                'questions_count' => $quiz->questions()->count(),
                'required_to_proceed' => $quiz->required_to_proceed,
            ],
            'quizStatus' => $quizStatus,
            'canTakeQuiz' => $canTakeQuiz,
        ]);
    }

    /**
     * Start a new quiz attempt.
     */
    public function start($courseOnline, CourseModule $courseModule)
    {
        $user = Auth::user();

        // Validate user can take quiz
        $canTakeQuiz = $courseModule->canUserTakeQuiz($user->id);
        if (!$canTakeQuiz['can_take']) {
            return back()->with('error', $canTakeQuiz['message']);
        }

        $quiz = $courseModule->quiz;

        DB::beginTransaction();
        try {
            // Get the next attempt number
            $attemptNumber = QuizAttempt::where('quiz_id', $quiz->id)
                ->where('user_id', $user->id)
                ->count() + 1;

            // Create new attempt
            $attempt = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'user_id' => $user->id,
                'attempt_number' => $attemptNumber,
                'started_at' => now(),
                'score' => 0,
                'manual_score' => 0,
                'total_score' => 0,
                'passed' => false,
            ]);

            DB::commit();

            return redirect()->route('courses-online.modules.quiz.take', [
                'courseOnline' => $courseModule->course_online_id,
                'courseModule' => $courseModule->id,
                'attempt' => $attempt->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to start quiz attempt: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'module_id' => $courseModule->id,
                'quiz_id' => $quiz->id,
            ]);
            return back()->with('error', 'Failed to start quiz. Please try again.');
        }
    }

    /**
     * Display the quiz taking interface.
     */
    public function take($courseOnline, CourseModule $courseModule, QuizAttempt $attempt)
    {
        $user = Auth::user();

        // Validate attempt belongs to user
        if ($attempt->user_id !== $user->id) {
            return redirect()->route('courses-online.show', $courseModule->course_online_id)
                ->with('error', 'Invalid quiz attempt.');
        }

        // Check if attempt is already completed
        if ($attempt->completed_at) {
            return redirect()->route('courses-online.modules.quiz.result', [
                'courseOnline' => $courseModule->course_online_id,
                'courseModule' => $courseModule->id,
                'attempt' => $attempt->id,
            ]);
        }

        $quiz = $courseModule->quiz;

        // Check time limit
        $timeRemaining = null;
        $isExpired = false;
        if ($quiz->time_limit_minutes) {
            $endTime = $attempt->started_at->addMinutes($quiz->time_limit_minutes);
            $timeRemaining = max(0, now()->diffInSeconds($endTime, false));
            $isExpired = $timeRemaining <= 0;

            if ($isExpired) {
                // Auto-submit if time expired
                return $this->autoSubmit($courseModule, $attempt);
            }
        }

        // Get questions with shuffled order (but consistent for this attempt)
        $questions = $quiz->questions()
            ->orderBy('order')
            ->get()
            ->map(function ($question) {
                return [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'type' => $question->type,
                    'points' => $question->points,
                    'options' => $question->options ?? [],
                    'order' => $question->order,
                ];
            });

        // Get existing answers for this attempt
        $existingAnswers = QuizAnswer::where('quiz_attempt_id', $attempt->id)
            ->get()
            ->keyBy('quiz_question_id')
            ->map(function ($answer) {
                return $answer->answer;
            });

        $courseModule->load('courseOnline');

        return Inertia::render('User/ModuleQuiz/Take', [
            'module' => [
                'id' => $courseModule->id,
                'name' => $courseModule->name,
            ],
            'course' => [
                'id' => $courseModule->courseOnline->id,
                'name' => $courseModule->courseOnline->name,
            ],
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'total_points' => $quiz->total_points,
                'time_limit_minutes' => $quiz->time_limit_minutes,
            ],
            'attempt' => [
                'id' => $attempt->id,
                'attempt_number' => $attempt->attempt_number,
                'started_at' => $attempt->started_at->toIso8601String(),
            ],
            'questions' => $questions,
            'existingAnswers' => $existingAnswers,
            'timeRemaining' => $timeRemaining,
        ]);
    }

    /**
     * Save answer for a question (auto-save).
     */
    public function saveAnswer(Request $request, $courseOnline, CourseModule $courseModule, QuizAttempt $attempt)
    {
        $user = Auth::user();

        // Validate attempt belongs to user and is not completed
        if ($attempt->user_id !== $user->id || $attempt->completed_at) {
            return response()->json(['error' => 'Invalid attempt'], 403);
        }

        $validated = $request->validate([
            'question_id' => 'required|exists:quiz_questions,id',
            'answer' => 'nullable|array',
        ]);

        try {
            // Update or create answer
            QuizAnswer::updateOrCreate(
                [
                    'quiz_attempt_id' => $attempt->id,
                    'quiz_question_id' => $validated['question_id'],
                ],
                [
                    'answer' => $validated['answer'] ?? [],
                ]
            );

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Failed to save quiz answer: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to save answer'], 500);
        }
    }

    /**
     * Submit the quiz attempt.
     */
    public function submit(Request $request, $courseOnline, CourseModule $courseModule, QuizAttempt $attempt)
    {
        $user = Auth::user();

        // Validate attempt belongs to user and is not completed
        if ($attempt->user_id !== $user->id) {
            return redirect()->route('courses-online.show', $courseModule->course_online_id)
                ->with('error', 'Invalid quiz attempt.');
        }

        if ($attempt->completed_at) {
            return redirect()->route('courses-online.modules.quiz.result', [
                'courseOnline' => $courseModule->course_online_id,
                'courseModule' => $courseModule->id,
                'attempt' => $attempt->id,
            ]);
        }

        $validated = $request->validate([
            'answers' => 'nullable|array',
            'answers.*.question_id' => 'required|exists:quiz_questions,id',
            'answers.*.answer' => 'nullable|array',
        ]);

        $quiz = $courseModule->quiz;

        DB::beginTransaction();
        try {
            // Save all answers
            if (!empty($validated['answers'])) {
                foreach ($validated['answers'] as $answerData) {
                    QuizAnswer::updateOrCreate(
                        [
                            'quiz_attempt_id' => $attempt->id,
                            'quiz_question_id' => $answerData['question_id'],
                        ],
                        [
                            'answer' => $answerData['answer'] ?? [],
                        ]
                    );
                }
            }

            // Calculate score
            $scoreResult = $this->calculateScore($attempt, $quiz);

            // Update attempt
            $attempt->update([
                'score' => $scoreResult['auto_score'],
                'manual_score' => 0, // Text questions need manual grading
                'total_score' => $scoreResult['auto_score'],
                'passed' => $scoreResult['passed'],
                'completed_at' => now(),
            ]);

            // Update module quiz result
            ModuleQuizResult::updateFromAttempt($attempt);

            DB::commit();

            return redirect()->route('courses-online.modules.quiz.result', [
                'courseOnline' => $courseModule->course_online_id,
                'courseModule' => $courseModule->id,
                'attempt' => $attempt->id,
            ])->with('success', 'Quiz submitted successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to submit quiz: ' . $e->getMessage(), [
                'attempt_id' => $attempt->id,
                'exception' => $e,
            ]);
            return back()->with('error', 'Failed to submit quiz. Please try again.');
        }
    }

    /**
     * Display quiz result.
     */
    public function result($courseOnline, CourseModule $courseModule, QuizAttempt $attempt)
    {
        $user = Auth::user();

        // Validate attempt belongs to user
        if ($attempt->user_id !== $user->id) {
            return redirect()->route('courses-online.show', $courseModule->course_online_id)
                ->with('error', 'Invalid quiz attempt.');
        }

        // Redirect to take page if not completed
        if (!$attempt->completed_at) {
            return redirect()->route('courses-online.modules.quiz.take', [
                'courseOnline' => $courseModule->course_online_id,
                'courseModule' => $courseModule->id,
                'attempt' => $attempt->id,
            ]);
        }

        $quiz = $courseModule->quiz;
        $showCorrectAnswers = $quiz->shouldShowCorrectAnswers($user->id);

        // Get questions with answers
        $questions = $quiz->questions()
            ->orderBy('order')
            ->get()
            ->map(function ($question) use ($attempt, $showCorrectAnswers) {
                $userAnswer = QuizAnswer::where('quiz_attempt_id', $attempt->id)
                    ->where('quiz_question_id', $question->id)
                    ->first();

                $isCorrect = $this->checkAnswer($question, $userAnswer?->answer ?? []);

                $questionData = [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'type' => $question->type,
                    'points' => $question->points,
                    'options' => $question->options ?? [],
                    'user_answer' => $userAnswer?->answer ?? [],
                    'is_correct' => $isCorrect,
                    'points_earned' => $isCorrect ? $question->points : 0,
                ];

                // Only show correct answers if allowed
                if ($showCorrectAnswers) {
                    $questionData['correct_answer'] = $question->correct_answer ?? [];
                    $questionData['correct_answer_explanation'] = $question->correct_answer_explanation;
                }

                return $questionData;
            });

        $courseModule->load('courseOnline');
        $quizStatus = $courseModule->getQuizStatus($user->id);
        $canRetake = $courseModule->canUserTakeQuiz($user->id);

        // Check if next module is now unlocked
        $nextModule = CourseModule::where('course_online_id', $courseModule->course_online_id)
            ->where('order_number', $courseModule->order_number + 1)
            ->first();

        $nextModuleUnlocked = $nextModule ? $nextModule->isUnlockedForUser($user->id) : false;

        return Inertia::render('User/ModuleQuiz/Result', [
            'module' => [
                'id' => $courseModule->id,
                'name' => $courseModule->name,
                'order_number' => $courseModule->order_number,
            ],
            'course' => [
                'id' => $courseModule->courseOnline->id,
                'name' => $courseModule->courseOnline->name,
            ],
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'total_points' => $quiz->total_points,
                'pass_threshold' => $quiz->pass_threshold,
                'passing_score' => $quiz->getPassingScore(),
                'max_attempts' => $quiz->max_attempts,
            ],
            'attempt' => [
                'id' => $attempt->id,
                'attempt_number' => $attempt->attempt_number,
                'score' => $attempt->total_score,
                'score_percentage' => $quiz->total_points > 0 
                    ? round(($attempt->total_score / $quiz->total_points) * 100, 1) 
                    : 0,
                'passed' => $attempt->passed,
                'started_at' => $attempt->started_at->toIso8601String(),
                'completed_at' => $attempt->completed_at->toIso8601String(),
                'duration_minutes' => $attempt->started_at->diffInMinutes($attempt->completed_at),
            ],
            'questions' => $questions,
            'showCorrectAnswers' => $showCorrectAnswers,
            'quizStatus' => $quizStatus,
            'canRetake' => $canRetake,
            'nextModule' => $nextModule ? [
                'id' => $nextModule->id,
                'name' => $nextModule->name,
                'is_unlocked' => $nextModuleUnlocked,
            ] : null,
        ]);
    }

    /**
     * Auto-submit when time expires.
     */
    private function autoSubmit(CourseModule $courseModule, QuizAttempt $attempt)
    {
        $quiz = $courseModule->quiz;

        DB::beginTransaction();
        try {
            // Calculate score with existing answers
            $scoreResult = $this->calculateScore($attempt, $quiz);

            // Update attempt
            $attempt->update([
                'score' => $scoreResult['auto_score'],
                'manual_score' => 0,
                'total_score' => $scoreResult['auto_score'],
                'passed' => $scoreResult['passed'],
                'completed_at' => now(),
            ]);

            // Update module quiz result
            ModuleQuizResult::updateFromAttempt($attempt);

            DB::commit();

            return redirect()->route('courses-online.modules.quiz.result', [
                'courseOnline' => $courseModule->course_online_id,
                'courseModule' => $courseModule->id,
                'attempt' => $attempt->id,
            ])->with('info', 'Time expired. Your quiz has been automatically submitted.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to auto-submit quiz: ' . $e->getMessage());
            return redirect()->route('courses-online.show', $courseModule->course_online_id)
                ->with('error', 'An error occurred. Please contact support.');
        }
    }

    /**
     * Calculate the score for an attempt.
     */
    private function calculateScore(QuizAttempt $attempt, Quiz $quiz): array
    {
        $autoScore = 0;
        $hasTextQuestions = false;

        $questions = $quiz->questions()->get();
        $answers = QuizAnswer::where('quiz_attempt_id', $attempt->id)
            ->get()
            ->keyBy('quiz_question_id');

        foreach ($questions as $question) {
            if ($question->type === 'text') {
                $hasTextQuestions = true;
                continue; // Text questions need manual grading
            }

            $userAnswer = $answers->get($question->id)?->answer ?? [];
            
            if ($this->checkAnswer($question, $userAnswer)) {
                $autoScore += $question->points;
            }
        }

        $passed = $quiz->isPassingScore($autoScore);

        return [
            'auto_score' => $autoScore,
            'has_text_questions' => $hasTextQuestions,
            'passed' => $passed,
        ];
    }

    /**
     * Check if an answer is correct.
     */
    private function checkAnswer($question, array $userAnswer): bool
    {
        if ($question->type === 'text') {
            return false; // Text questions need manual grading
        }

        $correctAnswer = $question->correct_answer ?? [];
        
        if (empty($correctAnswer)) {
            return false;
        }

        // Normalize arrays for comparison
        $userAnswerNormalized = array_map('trim', array_filter($userAnswer, 'strlen'));
        $correctAnswerNormalized = array_map('trim', array_filter($correctAnswer, 'strlen'));

        sort($userAnswerNormalized);
        sort($correctAnswerNormalized);

        return $userAnswerNormalized === $correctAnswerNormalized;
    }

    /**
     * Get quiz history for a module.
     */
    public function history($courseOnline, CourseModule $courseModule)
    {
        $user = Auth::user();

        $quiz = $courseModule->quiz;
        if (!$quiz) {
            return redirect()->route('courses-online.show', $courseModule->course_online_id)
                ->with('error', 'This module does not have a quiz.');
        }

        $attempts = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($attempt) use ($quiz) {
                return [
                    'id' => $attempt->id,
                    'attempt_number' => $attempt->attempt_number,
                    'score' => $attempt->total_score,
                    'score_percentage' => $quiz->total_points > 0 
                        ? round(($attempt->total_score / $quiz->total_points) * 100, 1) 
                        : 0,
                    'passed' => $attempt->passed,
                    'started_at' => $attempt->started_at->toIso8601String(),
                    'completed_at' => $attempt->completed_at?->toIso8601String(),
                    'duration_minutes' => $attempt->completed_at 
                        ? $attempt->started_at->diffInMinutes($attempt->completed_at) 
                        : null,
                ];
            });

        $courseModule->load('courseOnline');
        $quizStatus = $courseModule->getQuizStatus($user->id);

        return Inertia::render('User/ModuleQuiz/History', [
            'module' => [
                'id' => $courseModule->id,
                'name' => $courseModule->name,
            ],
            'course' => [
                'id' => $courseModule->courseOnline->id,
                'name' => $courseModule->courseOnline->name,
            ],
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'total_points' => $quiz->total_points,
                'pass_threshold' => $quiz->pass_threshold,
                'max_attempts' => $quiz->max_attempts,
            ],
            'attempts' => $attempts,
            'quizStatus' => $quizStatus,
        ]);
    }
}
