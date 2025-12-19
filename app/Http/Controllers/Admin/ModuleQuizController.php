<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseModule;
use App\Models\CourseOnline;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * ModuleQuizController
 * 
 * Handles CRUD operations for module-level quizzes.
 * Module quizzes are tied to specific course modules and must be passed
 * before users can proceed to the next module.
 */
class ModuleQuizController extends Controller
{
    /**
     * Display module quiz management page.
     */
    public function index(CourseOnline $courseOnline, CourseModule $courseModule)
    {
        $courseModule->load(['quiz' => function($q) {
            $q->withCount(['questions', 'attempts']);
        }]);

        $quizzes = [];
        if ($courseModule->quiz) {
            $quiz = $courseModule->quiz;
            $passRate = $quiz->attempts_count > 0 
                ? ($quiz->attempts()->where('passed', true)->count() / $quiz->attempts_count) * 100 
                : 0;
            
            $quizzes[] = [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'time_limit' => $quiz->time_limit_minutes,
                'pass_threshold' => $quiz->pass_threshold,
                'max_attempts' => $quiz->max_attempts,
                'retry_delay_hours' => $quiz->retry_delay_hours,
                'show_correct_answers' => $quiz->show_correct_answers,
                'is_active' => $quiz->status === 'published',
                'questions_count' => $quiz->questions_count,
                'attempts_count' => $quiz->attempts_count,
                'pass_rate' => $passRate,
                'created_at' => $quiz->created_at->toIso8601String(),
            ];
        }

        return Inertia::render('Admin/ModuleQuiz/Index', [
            'course' => [
                'id' => $courseOnline->id,
                'name' => $courseOnline->name,
            ],
            'module' => [
                'id' => $courseModule->id,
                'name' => $courseModule->name,
                'order_number' => $courseModule->order_number,
                'has_quiz' => $courseModule->has_quiz,
                'quiz_required' => $courseModule->quiz_required,
            ],
            'quizzes' => $quizzes,
        ]);
    }

    /**
     * Show the form for creating a new module quiz.
     */
    public function create(CourseOnline $courseOnline, CourseModule $courseModule)
    {
        // Check if module already has a quiz
        if ($courseModule->quiz) {
            return redirect()->route('admin.module-quiz.edit', [
                'courseOnline' => $courseOnline->id,
                'courseModule' => $courseModule->id,
                'quiz' => $courseModule->quiz->id
            ])->with('info', 'This module already has a quiz. You can edit it here.');
        }

        return Inertia::render('Admin/ModuleQuiz/Create', [
            'module' => [
                'id' => $courseModule->id,
                'name' => $courseModule->name,
                'order_number' => $courseModule->order_number,
                'course_online_id' => $courseModule->course_online_id,
            ],
            'course' => [
                'id' => $courseOnline->id,
                'name' => $courseOnline->name,
            ],
            'showCorrectAnswersOptions' => [
                ['value' => 'never', 'label' => 'Never show correct answers'],
                ['value' => 'after_pass', 'label' => 'Show after passing'],
                ['value' => 'after_max_attempts', 'label' => 'Show after all attempts used'],
                ['value' => 'always', 'label' => 'Always show correct answers'],
            ],
        ]);
    }

    /**
     * Store a newly created module quiz.
     */
    public function store(Request $request, CourseOnline $courseOnline, CourseModule $courseModule)
    {
        // Check if module already has a quiz
        if ($courseModule->quiz) {
            return back()->withErrors(['error' => 'This module already has a quiz.']);
        }

        $validated = $request->validate([
            // Quiz basic fields
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'pass_threshold' => 'required|numeric|min:0|max:100',

            // Module quiz specific fields
            'required_to_proceed' => 'boolean',
            'max_attempts' => 'required|integer|min:1|max:100',
            'retry_delay_hours' => 'nullable|integer|min:0|max:168', // Max 1 week
            'show_correct_answers' => 'required|in:never,after_pass,after_max_attempts,always',
            'time_limit_minutes' => 'nullable|integer|min:1|max:1440',

            // Questions validation
            'questions' => 'required|array|min:1|max:50',
            'questions.*.question_text' => 'required|string',
            'questions.*.type' => 'required|in:radio,checkbox,text',
            'questions.*.points' => 'nullable|integer|min:0',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*' => 'nullable|string',
            'questions.*.correct_answer' => 'nullable|array',
            'questions.*.correct_answer.*' => 'nullable|string',
            'questions.*.correct_answer_explanation' => 'nullable|string',
        ]);

        // Validate questions data
        $this->validateQuestionData($validated['questions']);

        DB::beginTransaction();
        try {
            // Create the quiz
            $quiz = Quiz::create([
                // Module quiz specific fields
                'module_id' => $courseModule->id,
                'is_module_quiz' => true,
                'course_online_id' => $courseModule->course_online_id,
                
                // Basic quiz fields
                'title' => $validated['title'],
                'description' => $validated['description'],
                'status' => $validated['status'],
                'pass_threshold' => $validated['pass_threshold'],
                'total_points' => 0,

                // Module quiz settings
                'required_to_proceed' => $validated['required_to_proceed'] ?? true,
                'max_attempts' => $validated['max_attempts'],
                'retry_delay_hours' => $validated['retry_delay_hours'] ?? 0,
                'show_correct_answers' => $validated['show_correct_answers'],
                'time_limit_minutes' => $validated['time_limit_minutes'],
            ]);

            // Create questions
            foreach ($validated['questions'] as $index => $questionData) {
                QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question_text' => $questionData['question_text'],
                    'type' => $questionData['type'],
                    'points' => $questionData['type'] !== 'text' ? ($questionData['points'] ?? 0) : 0,
                    'options' => $questionData['type'] !== 'text' 
                        ? array_values(array_filter($questionData['options'] ?? [], 'strlen')) 
                        : null,
                    'correct_answer' => $questionData['type'] !== 'text' 
                        ? array_values(array_filter($questionData['correct_answer'] ?? [], 'strlen')) 
                        : null,
                    'correct_answer_explanation' => $questionData['correct_answer_explanation'] ?? null,
                    'order' => $index + 1,
                ]);
            }

            // Calculate total points
            $totalPoints = $quiz->questions()->where('type', '!=', 'text')->sum('points');
            $quiz->update(['total_points' => $totalPoints]);

            // Update module to indicate it has a quiz
            $courseModule->update([
                'has_quiz' => true,
                'quiz_required' => $validated['required_to_proceed'] ?? true,
            ]);

            DB::commit();

            return redirect()->route('admin.course-online.show', $courseModule->course_online_id)
                ->with('success', "Quiz created successfully for module '{$courseModule->name}'.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create module quiz: ' . $e->getMessage(), [
                'module_id' => $courseModule->id,
                'exception' => $e,
            ]);
            return back()->withErrors(['error' => 'Failed to create quiz: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified module quiz.
     */
    public function show(CourseOnline $courseOnline, CourseModule $courseModule, Quiz $quiz)
    {
        if (!$quiz->isModuleQuiz()) {
            return redirect()->route('admin.quizzes.show', $quiz->id);
        }

        $quiz->load(['questions' => function($query) {
            $query->orderBy('order');
        }]);

        return Inertia::render('Admin/ModuleQuiz/Show', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'status' => $quiz->status,
                'total_points' => $quiz->total_points,
                'pass_threshold' => $quiz->pass_threshold,
                'max_attempts' => $quiz->max_attempts,
                'retry_delay_hours' => $quiz->retry_delay_hours,
                'show_correct_answers' => $quiz->show_correct_answers,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'required_to_proceed' => $quiz->required_to_proceed,
                'created_at' => $quiz->created_at->format('Y-m-d H:i:s'),
                'questions' => $quiz->questions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'question_text' => $question->question_text,
                        'type' => $question->type,
                        'points' => $question->points ?? 0,
                        'options' => $question->options ?? [],
                        'correct_answer' => $question->correct_answer ?? [],
                        'correct_answer_explanation' => $question->correct_answer_explanation ?? '',
                        'order' => $question->order,
                    ];
                }),
            ],
            'module' => [
                'id' => $courseModule->id,
                'name' => $courseModule->name,
                'order_number' => $courseModule->order_number,
            ],
            'course' => [
                'id' => $courseOnline->id,
                'name' => $courseOnline->name,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified module quiz.
     */
    public function edit(CourseOnline $courseOnline, CourseModule $courseModule, Quiz $quiz)
    {
        if (!$quiz->isModuleQuiz()) {
            return redirect()->route('admin.quizzes.edit', $quiz->id);
        }

        $quiz->load(['questions' => function($query) {
            $query->orderBy('order');
        }]);

        return Inertia::render('Admin/ModuleQuiz/Edit', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'status' => $quiz->status,
                'pass_threshold' => $quiz->pass_threshold,
                'max_attempts' => $quiz->max_attempts,
                'retry_delay_hours' => $quiz->retry_delay_hours,
                'show_correct_answers' => $quiz->show_correct_answers,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'required_to_proceed' => $quiz->required_to_proceed,
                'questions' => $quiz->questions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'question_text' => $question->question_text,
                        'type' => $question->type,
                        'points' => $question->points,
                        'options' => $question->options,
                        'correct_answer' => $question->correct_answer,
                        'correct_answer_explanation' => $question->correct_answer_explanation ?? '',
                        'order' => $question->order,
                    ];
                }),
            ],
            'module' => [
                'id' => $courseModule->id,
                'name' => $courseModule->name,
                'order_number' => $courseModule->order_number,
            ],
            'course' => [
                'id' => $courseOnline->id,
                'name' => $courseOnline->name,
            ],
            'showCorrectAnswersOptions' => [
                ['value' => 'never', 'label' => 'Never show correct answers'],
                ['value' => 'after_pass', 'label' => 'Show after passing'],
                ['value' => 'after_max_attempts', 'label' => 'Show after all attempts used'],
                ['value' => 'always', 'label' => 'Always show correct answers'],
            ],
        ]);
    }

    /**
     * Update the specified module quiz.
     */
    public function update(Request $request, CourseOnline $courseOnline, CourseModule $courseModule, Quiz $quiz)
    {
        if (!$quiz->isModuleQuiz()) {
            return redirect()->route('admin.quizzes.update', $quiz->id);
        }

        $validated = $request->validate([
            // Quiz basic fields
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'pass_threshold' => 'required|numeric|min:0|max:100',

            // Module quiz specific fields
            'required_to_proceed' => 'boolean',
            'max_attempts' => 'required|integer|min:1|max:100',
            'retry_delay_hours' => 'nullable|integer|min:0|max:168',
            'show_correct_answers' => 'required|in:never,after_pass,after_max_attempts,always',
            'time_limit_minutes' => 'nullable|integer|min:1|max:1440',

            // Questions
            'questions' => 'required|array|min:1|max:50',
            'questions.*.id' => 'nullable|exists:quiz_questions,id',
            'questions.*.question_text' => 'required|string',
            'questions.*.type' => 'required|in:radio,checkbox,text',
            'questions.*.points' => 'nullable|integer|min:0',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*' => 'nullable|string',
            'questions.*.correct_answer' => 'nullable|array',
            'questions.*.correct_answer.*' => 'nullable|string',
            'questions.*.correct_answer_explanation' => 'nullable|string',
        ]);

        // Validate questions data
        $this->validateQuestionData($validated['questions']);

        DB::beginTransaction();
        try {
            // Update quiz details
            $quiz->update([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'status' => $validated['status'],
                'pass_threshold' => $validated['pass_threshold'],
                'required_to_proceed' => $validated['required_to_proceed'] ?? true,
                'max_attempts' => $validated['max_attempts'],
                'retry_delay_hours' => $validated['retry_delay_hours'] ?? 0,
                'show_correct_answers' => $validated['show_correct_answers'],
                'time_limit_minutes' => $validated['time_limit_minutes'],
            ]);

            // Update module quiz_required flag
            $quiz->module->update([
                'quiz_required' => $validated['required_to_proceed'] ?? true,
            ]);

            // Handle questions update
            $existingQuestions = $quiz->questions()->pluck('id')->toArray();
            $processedQuestionIds = [];

            foreach ($validated['questions'] as $index => $questionData) {
                if (isset($questionData['id']) && $questionData['id']) {
                    if (in_array($questionData['id'], $existingQuestions)) {
                        $question = QuizQuestion::find($questionData['id']);
                        $question->update([
                            'question_text' => $questionData['question_text'],
                            'type' => $questionData['type'],
                            'points' => $questionData['type'] !== 'text' ? ($questionData['points'] ?? 0) : 0,
                            'options' => $questionData['type'] !== 'text' 
                                ? array_values(array_filter($questionData['options'] ?? [], 'strlen')) 
                                : null,
                            'correct_answer' => $questionData['type'] !== 'text' 
                                ? array_values(array_filter($questionData['correct_answer'] ?? [], 'strlen')) 
                                : null,
                            'correct_answer_explanation' => $questionData['correct_answer_explanation'] ?? null,
                            'order' => $index + 1,
                        ]);
                        $processedQuestionIds[] = $question->id;
                    }
                } else {
                    $question = QuizQuestion::create([
                        'quiz_id' => $quiz->id,
                        'question_text' => $questionData['question_text'],
                        'type' => $questionData['type'],
                        'points' => $questionData['type'] !== 'text' ? ($questionData['points'] ?? 0) : 0,
                        'options' => $questionData['type'] !== 'text' 
                            ? array_values(array_filter($questionData['options'] ?? [], 'strlen')) 
                            : null,
                        'correct_answer' => $questionData['type'] !== 'text' 
                            ? array_values(array_filter($questionData['correct_answer'] ?? [], 'strlen')) 
                            : null,
                        'correct_answer_explanation' => $questionData['correct_answer_explanation'] ?? null,
                        'order' => $index + 1,
                    ]);
                    $processedQuestionIds[] = $question->id;
                }
            }

            // Delete removed questions and their answers
            $questionsToDelete = QuizQuestion::where('quiz_id', $quiz->id)
                ->whereNotIn('id', $processedQuestionIds)
                ->get();

            foreach ($questionsToDelete as $questionToDelete) {
                // Delete all answers for this question first
                $questionToDelete->answers()->delete();
                // Then delete the question
                $questionToDelete->delete();
            }

            // Recalculate total points
            $totalPoints = $quiz->fresh()->questions()
                ->where('type', '!=', 'text')
                ->sum('points');

            $quiz->update(['total_points' => $totalPoints]);

            DB::commit();

            return redirect()->route('admin.course-online.show', $courseOnline->id)
                ->with('success', 'Quiz updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update module quiz: ' . $e->getMessage(), [
                'quiz_id' => $quiz->id,
                'exception' => $e,
            ]);
            return back()->withErrors(['error' => 'Failed to update quiz: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified module quiz.
     */
    public function destroy(CourseOnline $courseOnline, CourseModule $courseModule, Quiz $quiz)
    {
        if (!$quiz->isModuleQuiz()) {
            return redirect()->route('admin.quizzes.destroy', $quiz->id);
        }

        // Check if quiz has any attempts
        $attemptsCount = $quiz->attempts()->count();
        if ($attemptsCount > 0) {
            return back()->withErrors(['error' => 'Cannot delete quiz with existing attempts.']);
        }

        DB::beginTransaction();
        try {
            // Get all question IDs
            $questionIds = $quiz->questions()->pluck('id')->toArray();

            // Delete answers
            if (!empty($questionIds)) {
                \App\Models\QuizAnswer::whereIn('quiz_question_id', $questionIds)->delete();
            }

            // Delete questions
            $quiz->questions()->delete();

            // Delete module quiz results
            $quiz->moduleResults()->delete();

            // Delete the quiz
            $quiz->delete();

            // Update module to indicate it no longer has a quiz
            $courseModule->update([
                'has_quiz' => false,
                'quiz_required' => false,
            ]);

            DB::commit();

            return redirect()->route('admin.course-online.show', $courseOnline->id)
                ->with('success', 'Quiz deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete module quiz: ' . $e->getMessage(), [
                'quiz_id' => $quiz->id,
                'exception' => $e,
            ]);
            return back()->withErrors(['error' => 'Failed to delete quiz: ' . $e->getMessage()]);
        }
    }

    /**
     * Display attempts for a module quiz.
     */
    public function attempts(CourseOnline $courseOnline, CourseModule $courseModule, Quiz $quiz)
    {
        if (!$quiz->isModuleQuiz()) {
            return redirect()->route('admin.quizzes.attempts', $quiz->id);
        }

        $attempts = $quiz->attempts()
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $attempts->getCollection()->transform(function ($attempt) use ($quiz) {
            return [
                'id' => $attempt->id,
                'user' => [
                    'id' => $attempt->user->id,
                    'name' => $attempt->user->name,
                    'email' => $attempt->user->email,
                ],
                'attempt_number' => $attempt->attempt_number,
                'score' => $attempt->score,
                'total_score' => $attempt->total_score,
                'score_percentage' => $quiz->total_points > 0 
                    ? round(($attempt->total_score / $quiz->total_points) * 100, 1) 
                    : 0,
                'passed' => $attempt->passed,
                'completed_at' => $attempt->completed_at?->format('Y-m-d H:i:s'),
                'created_at' => $attempt->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return Inertia::render('Admin/ModuleQuiz/Attempts', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'total_points' => $quiz->total_points,
                'pass_threshold' => $quiz->pass_threshold,
                'max_attempts' => $quiz->max_attempts,
            ],
            'module' => [
                'id' => $courseModule->id,
                'name' => $courseModule->name,
            ],
            'course' => [
                'id' => $courseOnline->id,
                'name' => $courseOnline->name,
            ],
            'attempts' => $attempts,
        ]);
    }

    /**
     * Display a specific quiz attempt with all answers.
     */
    public function showAttempt(CourseOnline $courseOnline, CourseModule $courseModule, Quiz $quiz, QuizAttempt $attempt)
    {
        if (!$quiz->isModuleQuiz()) {
            return redirect()->route('admin.quizzes.attempts', $quiz->id);
        }

        // Verify the attempt belongs to this quiz
        if ($attempt->quiz_id !== $quiz->id) {
            return back()->withErrors(['error' => 'Invalid attempt for this quiz.']);
        }

        // Load attempt with relationships
        $attempt->load(['user', 'answers.question']);

        // Organize answers by question
        $questionsWithAnswers = $quiz->questions()
            ->orderBy('order')
            ->get()
            ->map(function ($question) use ($attempt) {
                $answer = $attempt->answers->firstWhere('quiz_question_id', $question->id);
                
                return [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'type' => $question->type,
                    'points' => $question->points,
                    'options' => $question->options ?? [],
                    'correct_answer' => $question->correct_answer ?? [],
                    'correct_answer_explanation' => $question->correct_answer_explanation,
                    'order' => $question->order,
                    'user_answer' => $answer ? $answer->answer : null,
                    'is_correct' => $answer ? $answer->is_correct : null,
                    'points_earned' => $answer ? $answer->points_earned : 0,
                    'answer_id' => $answer ? $answer->id : null,
                ];
            });

        return Inertia::render('Admin/ModuleQuiz/AttemptDetail', [
            'attempt' => [
                'id' => $attempt->id,
                'attempt_number' => $attempt->attempt_number,
                'score' => $attempt->score,
                'manual_score' => $attempt->manual_score,
                'total_score' => $attempt->total_score,
                'passed' => $attempt->passed,
                'started_at' => $attempt->started_at?->format('Y-m-d H:i:s'),
                'completed_at' => $attempt->completed_at?->format('Y-m-d H:i:s'),
                'created_at' => $attempt->created_at->format('Y-m-d H:i:s'),
            ],
            'user' => [
                'id' => $attempt->user->id,
                'name' => $attempt->user->name,
                'email' => $attempt->user->email,
            ],
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'total_points' => $quiz->total_points,
                'pass_threshold' => $quiz->pass_threshold,
            ],
            'module' => [
                'id' => $courseModule->id,
                'name' => $courseModule->name,
                'order_number' => $courseModule->order_number,
            ],
            'course' => [
                'id' => $courseOnline->id,
                'name' => $courseOnline->name,
            ],
            'questions' => $questionsWithAnswers,
        ]);
    }

    /**
     * Grade text answers manually.
     */
    public function gradeAttempt(Request $request, CourseOnline $courseOnline, CourseModule $courseModule, Quiz $quiz, QuizAttempt $attempt)
    {
        if (!$quiz->isModuleQuiz()) {
            return back()->withErrors(['error' => 'Not a module quiz']);
        }

        // Verify the attempt belongs to this quiz
        if ($attempt->quiz_id !== $quiz->id) {
            return back()->withErrors(['error' => 'Invalid attempt for this quiz.']);
        }

        $validated = $request->validate([
            'grades' => 'required|array',
            'grades.*' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $totalManualScore = 0;

            foreach ($validated['grades'] as $answerId => $points) {
                $answer = \App\Models\QuizAnswer::find($answerId);
                
                if (!$answer || $answer->attempt->id !== $attempt->id) {
                    continue;
                }

                // Ensure points don't exceed question's max points
                $question = $answer->question;
                $points = min($points, $question->points);
                $points = max(0, $points); // Ensure non-negative

                // Update the answer
                $answer->update([
                    'points_earned' => $points,
                    'is_correct' => $points > 0,
                ]);

                $totalManualScore += $points;
            }

            // Update attempt scores
            $autoScore = $attempt->answers()
                ->whereHas('question', function ($query) {
                    $query->where('type', '!=', 'text');
                })
                ->sum('points_earned');

            $totalScore = $autoScore + $totalManualScore;

            $attempt->update([
                'score' => $autoScore,
                'manual_score' => $totalManualScore,
                'total_score' => $totalScore,
            ]);

            // Recalculate pass status
            $passingThreshold = ($quiz->pass_threshold / 100) * $quiz->total_points;
            $passed = $totalScore >= $passingThreshold;

            $attempt->update(['passed' => $passed]);

            DB::commit();

            return back()->with('success', 'Manual grades saved successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to grade attempt: ' . $e->getMessage(), [
                'attempt_id' => $attempt->id,
                'exception' => $e,
            ]);
            return back()->withErrors(['error' => 'Failed to save grades: ' . $e->getMessage()]);
        }
    }

    /**
     * Get quiz statistics.
     */
    public function statistics(CourseOnline $courseOnline, CourseModule $courseModule, Quiz $quiz)
    {
        if (!$quiz->isModuleQuiz()) {
            return response()->json(['error' => 'Not a module quiz'], 400);
        }

        $stats = $quiz->getStatistics();
        
        // Add module-specific stats
        $moduleResults = $quiz->moduleResults();
        $stats['users_passed'] = $moduleResults->where('passed', true)->count();
        $stats['users_attempted'] = $moduleResults->count();

        return response()->json([
            'success' => true,
            'statistics' => $stats,
        ]);
    }

    /**
     * Toggle quiz status (publish/unpublish).
     */
    public function toggleStatus(CourseOnline $courseOnline, CourseModule $courseModule, Quiz $quiz)
    {
        if (!$quiz->isModuleQuiz()) {
            return back()->withErrors(['error' => 'Not a module quiz']);
        }

        $newStatus = $quiz->status === 'published' ? 'draft' : 'published';
        $quiz->update(['status' => $newStatus]);

        return back()->with('success', "Quiz status changed to {$newStatus}.");
    }

    /**
     * Custom validation for question data based on question type.
     */
    private function validateQuestionData($questions)
    {
        foreach ($questions as $index => $question) {
            $type = $question['type'];

            if ($type === 'radio' || $type === 'checkbox') {
                // Filter out empty options
                $options = array_filter($question['options'] ?? [], function($option) {
                    return !empty(trim($option));
                });

                // Validate that options exist and have at least 2 items
                if (count($options) < 2) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "questions.{$index}.options" => 'Radio and checkbox questions must have at least 2 non-empty options.'
                    ]);
                }

                // Validate that correct answers exist
                $correctAnswers = array_filter($question['correct_answer'] ?? [], function($answer) {
                    return !empty(trim($answer));
                });

                if (empty($correctAnswers)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "questions.{$index}.correct_answer" => 'Radio and checkbox questions must have at least one correct answer.'
                    ]);
                }

                // Validate that correct answers are in the options
                foreach ($correctAnswers as $correctAnswer) {
                    if (!in_array($correctAnswer, $options)) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "questions.{$index}.correct_answer" => 'Correct answers must be selected from the available options.'
                        ]);
                    }
                }

                // For radio buttons, ensure only one correct answer
                if ($type === 'radio' && count($correctAnswers) > 1) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "questions.{$index}.correct_answer" => 'Radio questions can only have one correct answer.'
                    ]);
                }

                // Validate points
                if (!isset($question['points']) || $question['points'] < 0) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "questions.{$index}.points" => 'Radio and checkbox questions must have a valid point value (0 or greater).'
                    ]);
                }
            }
        }
    }
}