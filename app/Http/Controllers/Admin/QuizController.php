<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\QuizCreatedNotification;
use App\Models\Course;
use App\Models\CourseOnline;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class QuizController extends Controller
{
    /**
     * Display a listing of quizzes.
     */
    public function index(Request $request)
    {
        $query = Quiz::with(['course', 'courseOnline', 'questions'])
            ->withCount('attempts');

        // Apply filters if provided
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('course_online_id')) {
            $query->where('course_online_id', $request->course_online_id);
        }

        if ($request->filled('course_type')) {
            if ($request->course_type === 'regular') {
                $query->whereNotNull('course_id');
            } elseif ($request->course_type === 'online') {
                $query->whereNotNull('course_online_id');
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $quizzes = $query->orderBy('created_at', 'desc')->paginate(15);

        // Transform the collection while preserving pagination structure
        $quizzes->getCollection()->transform(function ($quiz) {
            $associatedCourse = $quiz->getAssociatedCourse();

            return [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'course_type' => $quiz->getCourseType(),
                'course' => $associatedCourse ? [
                    'id' => $associatedCourse->id,
                    'name' => $associatedCourse->name
                ] : null,
                'status' => $quiz->status,
                'total_points' => $quiz->total_points,
                'questions_count' => $quiz->questions->count(),
                'attempts_count' => $quiz->attempts_count,
                'created_at' => $quiz->created_at,
                'pass_threshold' => $quiz->pass_threshold,
                'has_deadline' => $quiz->has_deadline,
                'deadline' => $quiz->deadline?->format('Y-m-d H:i:s'),
                'deadline_status' => $quiz->has_deadline ? $quiz->getDeadlineStatus() : null,
            ];
        });

        // Get courses for the filter dropdown
        $courses = Course::select('id', 'name')->orderBy('name')->get();
        $onlineCourses = CourseOnline::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Quizzes/Index', [
            'quizzes' => $quizzes,
            'courses' => $courses,
            'onlineCourses' => $onlineCourses,
            'filters' => $request->only(['course_id', 'course_online_id', 'course_type', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new quiz.
     */
    public function create()
    {
        $courses = Course::select('id', 'name')->orderBy('name')->get();
        $onlineCourses = CourseOnline::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Quizzes/Create', [
            'courses' => $courses,
            'onlineCourses' => $onlineCourses,
        ]);
    }

    /**
     * Store a newly created quiz with online course and deadline support.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Course assignment validation - FIXED TABLE NAME
            'course_type' => 'required|in:regular,online',
            'course_id' => 'required_if:course_type,regular|nullable|exists:courses,id',
            'course_online_id' => 'required_if:course_type,online|nullable|exists:course_online,id', // FIXED: course_online not course_onlines

            // Quiz basic fields
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'pass_threshold' => 'required|numeric|min:0|max:100',

            // Deadline fields
            'has_deadline' => 'boolean',
            'deadline_date' => 'nullable|date|after:today',
            'deadline_time' => 'nullable|date_format:H:i',
            'enforce_deadline' => 'boolean',
            'time_limit_minutes' => 'nullable|integer|min:1|max:1440',
            'allows_extensions' => 'boolean',

            // Questions validation
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

        // Combine deadline date and time
        $deadline = null;
        if (($validated['has_deadline'] ?? false) && $validated['deadline_date'] && $validated['deadline_time']) {
            $deadline = $validated['deadline_date'] . ' ' . $validated['deadline_time'];
        }

        DB::beginTransaction();
        try {
            $quiz = Quiz::create([
                // Course assignment based on type
                'course_id' => $validated['course_type'] === 'regular' ? $validated['course_id'] : null,
                'course_online_id' => $validated['course_type'] === 'online' ? $validated['course_online_id'] : null,

                // Basic quiz fields
                'title' => $validated['title'],
                'description' => $validated['description'],
                'status' => $validated['status'],
                'pass_threshold' => $validated['pass_threshold'],
                'total_points' => 0,

                // Deadline fields
                'has_deadline' => $validated['has_deadline'] ?? false,
                'deadline' => $deadline,
                'enforce_deadline' => $validated['enforce_deadline'] ?? true,
                'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
                'allows_extensions' => $validated['allows_extensions'] ?? false,
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

            // Send notifications to enrolled users
            $this->notifyEnrolledUsersOfNewQuiz($quiz);

            DB::commit();

            $courseType = $validated['course_type'] === 'online' ? 'online' : 'regular';
            return redirect()->route('admin.quizzes.index')
                ->with('success', "Quiz created successfully for {$courseType} course with notifications sent to enrolled users.");

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
        $quiz->load(['course', 'courseOnline', 'questions' => function($query) {
            $query->orderBy('order');
        }]);

        $associatedCourse = $quiz->getAssociatedCourse();

        return Inertia::render('Admin/Quizzes/Show', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'status' => $quiz->status,
                'total_points' => $quiz->total_points,
                'pass_threshold' => $quiz->pass_threshold,
                'created_at' => $quiz->created_at->format('Y-m-d H:i:s'),
                'course_type' => $quiz->getCourseType(),
                'course' => $associatedCourse ? [
                    'id' => $associatedCourse->id,
                    'name' => $associatedCourse->name
                ] : null,
                'has_deadline' => $quiz->has_deadline,
                'deadline' => $quiz->deadline?->format('Y-m-d H:i:s'),
                'deadline_formatted' => $quiz->getFormattedDeadline(),
                'time_until_deadline' => $quiz->getTimeUntilDeadline(),
                'deadline_status' => $quiz->getDeadlineStatus(),
                'enforce_deadline' => $quiz->enforce_deadline,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'allows_extensions' => $quiz->allows_extensions,
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
        $quiz->load(['course', 'courseOnline', 'questions' => function($query) {
            $query->orderBy('order');
        }]);

        $courses = Course::select('id', 'name')->orderBy('name')->get();
        $onlineCourses = CourseOnline::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('Admin/Quizzes/Edit', [
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'status' => $quiz->status,
                'course_type' => $quiz->getCourseType(),
                'course_id' => $quiz->course_id,
                'course_online_id' => $quiz->course_online_id,
                'pass_threshold' => $quiz->pass_threshold,
                'has_deadline' => $quiz->has_deadline,
                'deadline_date' => $quiz->deadline?->format('Y-m-d'),
                'deadline_time' => $quiz->deadline?->format('H:i'),
                'enforce_deadline' => $quiz->enforce_deadline,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'allows_extensions' => $quiz->allows_extensions,
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
            'onlineCourses' => $onlineCourses,
        ]);
    }

    /**
     * Update the specified quiz.
     */
    public function update(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            // Course assignment validation
            'course_type' => 'required|in:regular,online',
            'course_id' => 'required_if:course_type,regular|nullable|exists:courses,id',
            'course_online_id' => 'required_if:course_type,online|nullable|exists:course_online,id', // FIXED

            // Basic fields
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'pass_threshold' => 'required|numeric|min:0|max:100',

            // Deadline fields
            'has_deadline' => 'boolean',
            'deadline_date' => 'nullable|date',
            'deadline_time' => 'nullable|date_format:H:i',
            'enforce_deadline' => 'boolean',
            'time_limit_minutes' => 'nullable|integer|min:1|max:1440',
            'allows_extensions' => 'boolean',

            // Questions
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
        ]);

        // Custom validation for complex rules
        $this->validateQuestionData($validated['questions']);

        // Combine deadline date and time
        $deadline = null;
        if (($validated['has_deadline'] ?? false) && $validated['deadline_date'] && $validated['deadline_time']) {
            $deadline = $validated['deadline_date'] . ' ' . $validated['deadline_time'];
        }

        DB::beginTransaction();
        try {
            // Update quiz details
            $quiz->update([
                // Course assignment based on type
                'course_id' => $validated['course_type'] === 'regular' ? $validated['course_id'] : null,
                'course_online_id' => $validated['course_type'] === 'online' ? $validated['course_online_id'] : null,

                'title' => $validated['title'],
                'description' => $validated['description'],
                'status' => $validated['status'],
                'pass_threshold' => $validated['pass_threshold'],

                // Deadline fields
                'has_deadline' => $validated['has_deadline'] ?? false,
                'deadline' => $deadline,
                'enforce_deadline' => $validated['enforce_deadline'] ?? true,
                'time_limit_minutes' => $validated['time_limit_minutes'] ?? null,
                'allows_extensions' => $validated['allows_extensions'] ?? false,
            ]);

            // Handle questions (existing logic)
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

            $questionsToDelete = QuizQuestion::where('quiz_id', $quiz->id)
                ->whereNotIn('id', $processedQuestionIds)
                ->whereDoesntHave('answers')
                ->get();

            foreach ($questionsToDelete as $questionToDelete) {
                Log::info("Deleting question ID: {$questionToDelete->id} from quiz ID: {$quiz->id}");
                $questionToDelete->delete();
            }

            $totalPoints = $quiz->fresh()->questions()
                ->where('type', '!=', 'text')
                ->sum('points');

            $quiz->update(['total_points' => $totalPoints]);

            DB::commit();

            Log::info("Successfully updated quiz ID: {$quiz->id}", [
                'questions_processed' => count($processedQuestionIds),
                'questions_deleted' => $questionsToDelete->count(),
                'total_points' => $totalPoints,
                'course_type' => $quiz->getCourseType()
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
            $quiz->questions()->delete();
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

    /**
     * Get enrolled users for any course type
     */
    private function getEnrolledUsersForQuiz(Quiz $quiz)
    {
        $course = $quiz->getAssociatedCourse();

        if (!$course) {
            Log::warning('No associated course found for quiz', ['quiz_id' => $quiz->id]);
            return collect();
        }

        if ($quiz->isRegularCourse()) {
            // Regular course - use CourseRegistration
            return \App\Models\User::whereIn('id', function($query) use ($course) {
                $query->select('user_id')
                    ->from('course_registrations')
                    ->where('course_id', $course->id)
                    ->whereIn('status', ['in_progress', 'completed']);
            })->where('status', 'active')->get();

        } else {
            // Online course - use CourseOnlineAssignment
            return \App\Models\User::whereIn('id', function($query) use ($course) {
                $query->select('user_id')
                    ->from('course_online_assignments')
                    ->where('course_online_id', $course->id)
                    ->whereIn('status', ['assigned', 'in_progress', 'completed']);
            })->where('status', 'active')->get();
        }
    }

    /**
     * Generate appropriate quiz link based on course type and available routes
     */
    private function generateQuizLink(Quiz $quiz, $course): string
    {
        try {
            if ($quiz->isOnlineCourse()) {
                // Try common online course quiz route patterns
                $possibleRoutes = [
                    'course-online.quiz.show',
                    'courses-online.quiz.show',
                    'online.courses.quiz.show',
                    'course-online.quizzes.show',
                    'courses.online.quiz.show',
                    'admin.course-online.quiz.show'
                ];

                foreach ($possibleRoutes as $routeName) {
                    if (Route::has($routeName)) {
                        return route($routeName, [$course->id, $quiz->id]);
                    }
                }

                // Fallback: try generic quiz route
                if (Route::has('quizzes.show')) {
                    return route('quizzes.show', $quiz->id);
                }

                // Final fallback: admin route
                return route('admin.quizzes.show', $quiz->id);

            } else {
                // Regular course quiz routes
                $possibleRoutes = [
                    'courses.quiz.show',
                    'course.quiz.show',
                    'courses.quizzes.show'
                ];

                foreach ($possibleRoutes as $routeName) {
                    if (Route::has($routeName)) {
                        return route($routeName, [$course->id, $quiz->id]);
                    }
                }

                // Fallback: try generic quiz route
                if (Route::has('quizzes.show')) {
                    return route('quizzes.show', $quiz->id);
                }

                // Final fallback: admin route
                return route('admin.quizzes.show', $quiz->id);
            }

        } catch (\Exception $e) {
            Log::warning('Failed to generate quiz route, using fallback', [
                'quiz_id' => $quiz->id,
                'course_type' => $quiz->getCourseType(),
                'error' => $e->getMessage()
            ]);

            // Ultimate fallback
            return route('admin.quizzes.show', $quiz->id);
        }
    }

    /**
     * Enhanced notification method with course type and deadline support
     */
    private function notifyEnrolledUsersOfNewQuiz(Quiz $quiz)
    {
        try {
            $course = $quiz->getAssociatedCourse();

            if (!$course) {
                Log::error('Cannot send notifications - no associated course found', ['quiz_id' => $quiz->id]);
                return;
            }

            $enrolledUsers = $this->getEnrolledUsersForQuiz($quiz);

            Log::info('Starting quiz notifications', [
                'quiz_id' => $quiz->id,
                'quiz_title' => $quiz->title,
                'course_type' => $quiz->getCourseType(),
                'course_id' => $course->id,
                'course_name' => $course->name,
                'has_deadline' => $quiz->has_deadline,
                'deadline' => $quiz->deadline?->toDateTimeString(),
                'enrolled_users_count' => $enrolledUsers->count()
            ]);

            if ($enrolledUsers->count() === 0) {
                Log::warning('No enrolled users found for quiz', [
                    'quiz_id' => $quiz->id,
                    'course_type' => $quiz->getCourseType(),
                    'course_id' => $course->id
                ]);
                return;
            }

            $successCount = 0;
            foreach ($enrolledUsers as $user) {
                try {
                    if (empty($user->email)) {
                        Log::debug("Skipping user {$user->id} - no email address");
                        continue;
                    }

                    // FIXED: Generate appropriate quiz link based on your actual routes
                    $quizLink = $this->generateQuizLink($quiz, $course);

                    // Send email with course type and deadline info
                    Mail::to($user->email)->send(new QuizCreatedNotification($quiz, $course, $user, $quizLink));
                    $successCount++;

                    Log::info("Quiz notification sent to {$user->email} (User ID: {$user->id})");

                } catch (\Exception $e) {
                    Log::error("Failed to send quiz notification to {$user->email} (User ID: {$user->id}): " . $e->getMessage());
                }

                usleep(200000); // 0.2 second delay
            }

            Log::info('Quiz notifications completed', [
                'quiz_id' => $quiz->id,
                'successful_sends' => $successCount,
                'failed_sends' => ($enrolledUsers->count() - $successCount),
                'course_type' => $quiz->getCourseType()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send quiz notifications', [
                'quiz_id' => $quiz->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
