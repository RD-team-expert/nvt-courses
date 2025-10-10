<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuizController extends Controller
{
    /**
     * Display a listing of quizzes.
     */
    public function index(Request $request)
    {
        $query = Quiz::with(['course', 'questions'])
            ->withCount('attempts');

        // Apply filters if provided
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $quizzes = $query->orderBy('created_at', 'desc')->paginate(15);

        // Transform the collection while preserving pagination structure
        $quizzes->getCollection()->transform(function ($quiz) {
            return [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'course' => $quiz->course ? [
                    'id' => $quiz->course->id,
                    'name' => $quiz->course->name
                ] : null,
                'status' => $quiz->status,
                'total_points' => $quiz->total_points,
                'questions_count' => $quiz->questions->count(),
                'attempts_count' => $quiz->attempts_count,
                'created_at' => $quiz->created_at,
                'pass_threshold' => $quiz->pass_threshold,
            ];
        });

        // Get courses for the filter dropdown
        $courses = \App\Models\Course::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Quizzes/Index', [
            'quizzes' => $quizzes,
            'courses' => $courses,
            'filters' => $request->only(['course_id', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new quiz.
     */
    public function create()
    {
        $courses = Course::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Quizzes/Create', [
            'courses' => $courses,
        ]);
    }

    /**
     * Store a newly created quiz.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'pass_threshold' => 'required|numeric|min:0|max:100',
            'questions' => 'required|array|min:1|max:20',
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
            $quiz = Quiz::create([
                'course_id' => $validated['course_id'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'status' => $validated['status'],
                'pass_threshold' => $validated['pass_threshold'],
                'total_points' => 0,
            ]);

            foreach ($validated['questions'] as $index => $questionData) {
                QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question_text' => $questionData['question_text'],
                    'type' => $questionData['type'],
                    'points' => $questionData['type'] !== 'text' ? ($questionData['points'] ?? 0) : 0,
                    'options' => $questionData['type'] !== 'text' ? json_encode(array_filter($questionData['options'] ?? [], 'strlen')) : null,
                    'correct_answer' => $questionData['type'] !== 'text' ? json_encode(array_filter($questionData['correct_answer'] ?? [], 'strlen')) : null,
                    'correct_answer_explanation' => $questionData['correct_answer_explanation'] ?? null,
                    'order' => $index + 1,
                ]);
            }

            // Calculate total points
            $totalPoints = $quiz->questions()->where('type', '!=', 'text')->sum('points');
            $quiz->update(['total_points' => $totalPoints]);

            DB::commit();
            return redirect()->route('admin.quizzes.index')
                ->with('success', 'Quiz created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create quiz', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to create quiz: ' . $e->getMessage()]);
        }
    }

    /**
     * Custom validation for question data based on question type
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

    /**
     * Display the specified quiz.
     */
    public function show(Quiz $quiz)
    {
        $quiz->load(['course', 'questions' => function($query) {
            $query->orderBy('order');
        }]);

        return Inertia::render('Admin/Quizzes/Show', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'status' => $quiz->status,
                'total_points' => $quiz->total_points,
                'pass_threshold' => $quiz->pass_threshold,
                'created_at' => $quiz->created_at->format('Y-m-d H:i:s'),
                'course' => $quiz->course ? [
                    'id' => $quiz->course->id,
                    'name' => $quiz->course->name
                ] : null,
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
        ]);
    }

    /**
     * Show the form for editing the specified quiz.
     */
    public function edit(Quiz $quiz)
    {
        $quiz->load(['course', 'questions' => function($query) {
            $query->orderBy('order');
        }]);
        $courses = Course::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Quizzes/Edit', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'status' => $quiz->status,
                'course_id' => $quiz->course_id,
                'pass_threshold' => $quiz->pass_threshold,
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
            'courses' => $courses,
        ]);
    }

    /**
     * Update the specified quiz.
     * FIXED: Resolved question duplication issues
     */
    public function update(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'pass_threshold' => 'required|numeric|min:0|max:100',
            'questions' => 'required|array|min:1|max:20',
            'questions.*.id' => 'nullable|exists:quiz_questions,id',
            'questions.*.question_text' => 'required|string',
            'questions.*.type' => 'required|in:radio,checkbox,text',
            'questions.*.points' => 'nullable|integer|min:0',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*' => 'nullable|string',
            'questions.*.correct_answer' => 'nullable|array',
            'questions.*.correct_answer.*' => 'nullable|string',
            'questions.*.correct_answer_explanation' => 'nullable|string',
        ], [
            'questions.*.question_text.required' => 'Question text is required.',
            'questions.*.type.required' => 'Question type is required.',
            'questions.*.type.in' => 'Question type must be radio, checkbox, or text.',
            'questions.max' => 'Maximum 20 questions allowed per quiz.',
        ]);

        // Custom validation for complex rules
        $this->validateQuestionData($validated['questions']);

        DB::beginTransaction();
        try {
            // Update quiz details
            $quiz->update([
                'course_id' => $validated['course_id'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'status' => $validated['status'],
                'pass_threshold' => $validated['pass_threshold'],
            ]);

            // CRITICAL FIX: Get existing questions that belong to this quiz only
            $existingQuestions = $quiz->questions()->pluck('id')->toArray();
            $processedQuestionIds = [];

            // Process each question
            foreach ($validated['questions'] as $index => $questionData) {
                if (isset($questionData['id']) && $questionData['id']) {
                    // CRITICAL FIX: Verify the question belongs to this quiz
                    if (in_array($questionData['id'], $existingQuestions)) {
                        $question = QuizQuestion::find($questionData['id']);
                        $question->update([
                            'question_text' => $questionData['question_text'],
                            'type' => $questionData['type'],
                            'points' => $questionData['type'] !== 'text' ? ($questionData['points'] ?? 0) : 0,
                            'options' => $questionData['type'] !== 'text' ?
                                json_encode(array_values(array_filter($questionData['options'] ?? [], 'strlen'))) : null,
                            'correct_answer' => $questionData['type'] !== 'text' ?
                                json_encode(array_values(array_filter($questionData['correct_answer'] ?? [], 'strlen'))) : null,
                            'correct_answer_explanation' => $questionData['correct_answer_explanation'] ?? null,
                            'order' => $index + 1,
                        ]);
                        $processedQuestionIds[] = $question->id;
                    }
                } else {
                    // Create new question
                    $question = QuizQuestion::create([
                        'quiz_id' => $quiz->id,
                        'question_text' => $questionData['question_text'],
                        'type' => $questionData['type'],
                        'points' => $questionData['type'] !== 'text' ? ($questionData['points'] ?? 0) : 0,
                        'options' => $questionData['type'] !== 'text' ?
                            json_encode(array_values(array_filter($questionData['options'] ?? [], 'strlen'))) : null,
                        'correct_answer' => $questionData['type'] !== 'text' ?
                            json_encode(array_values(array_filter($questionData['correct_answer'] ?? [], 'strlen'))) : null,
                        'correct_answer_explanation' => $questionData['correct_answer_explanation'] ?? null,
                        'order' => $index + 1,
                    ]);
                    $processedQuestionIds[] = $question->id;
                }
            }

            // CRITICAL FIX: Delete questions that are no longer in the form
            // Only delete questions that belong to this quiz and are not in the processed list
            $questionsToDelete = QuizQuestion::where('quiz_id', $quiz->id)
                ->whereNotIn('id', $processedQuestionIds)
                ->whereDoesntHave('answers')
                ->get();

            foreach ($questionsToDelete as $questionToDelete) {
                Log::info("Deleting question ID: {$questionToDelete->id} from quiz ID: {$quiz->id}");
                $questionToDelete->delete();
            }

            // Recalculate total points
            $totalPoints = $quiz->fresh()->questions()
                ->where('type', '!=', 'text')
                ->sum('points');

            $quiz->update(['total_points' => $totalPoints]);

            DB::commit();

            Log::info("Successfully updated quiz ID: {$quiz->id}", [
                'questions_processed' => count($processedQuestionIds),
                'questions_deleted' => $questionsToDelete->count(),
                'total_points' => $totalPoints
            ]);

            return redirect()->route('admin.quizzes.index')
                ->with('success', 'Quiz updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update quiz', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'quiz_id' => $quiz->id,
                'data' => $validated
            ]);
            return back()->withErrors(['error' => 'Failed to update quiz: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified quiz.
     */
    public function destroy(Quiz $quiz)
    {
        if ($quiz->attempts()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete quiz with existing attempts.']);
        }

        DB::beginTransaction();
        try {
            // Delete all questions first
            $quiz->questions()->delete();

            // Delete the quiz
            $quiz->delete();

            DB::commit();
            return redirect()->route('admin.quizzes.index')
                ->with('success', 'Quiz deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete quiz', ['error' => $e->getMessage(), 'quiz_id' => $quiz->id]);
            return back()->withErrors(['error' => 'Failed to delete quiz.']);
        }
    }

    /**
     * Display all attempts for the specified quiz.
     */
    public function attempts(Quiz $quiz)
    {
        $attempts = $quiz->attempts()
            ->with(['user', 'quiz'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $attempts->getCollection()->transform(function ($attempt) {
            return [
                'id' => $attempt->id,
                'user' => [
                    'id' => $attempt->user->id,
                    'name' => $attempt->user->name,
                    'email' => $attempt->user->email
                ],
                'attempt_number' => $attempt->attempt_number,
                'score' => $attempt->score,
                'manual_score' => $attempt->manual_score,
                'total_score' => $attempt->total_score,
                'passed' => $attempt->passed,
                'completed_at' => $attempt->completed_at ? $attempt->completed_at->format('Y-m-d H:i:s') : null,
                'created_at' => $attempt->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return Inertia::render('Admin/Quizzes/Attempts', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'total_points' => $quiz->total_points,
                'pass_threshold' => $quiz->pass_threshold,
            ],
            'attempts' => $attempts,
            'filters' => request()->only(['user_id']),
        ]);
    }
}
