<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\QuizAssignmentRequest;
use App\Mail\QuizCreatedNotification;
use App\Models\Department;
use App\Models\Quiz;
use App\Models\QuizAssignment;
use App\Models\User;
use App\Notifications\QuizAssignedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;


class QuizAssignmentController extends Controller
{
    /**
     * Show the quiz assignment creation page.
     */
    public function create(Request $request): Response
    {
        $departmentId = $request->query('department_id');

        // Get all departments
        $departments = Department::select('id', 'name')
            ->orderBy('name')
            ->get();

        // Get users filtered by department if selected
        $usersQuery = User::select('id', 'name', 'email', 'department_id')
            ->with('department:id,name')
            ->where('role', '!=', 'admin'); // Exclude admins

        if ($departmentId) {
            $usersQuery->where('department_id', $departmentId);
        }

        $users = $usersQuery->orderBy('name')->get();

        // Get all quizzes
        $quizzes = Quiz::select('id', 'title', 'description')
            ->orderBy('title')
            ->get();

        return Inertia::render('Admin/QuizAssignments/Create', [
            'departments' => $departments,
            'users' => $users,
            'quizzes' => $quizzes,
            'selectedDepartmentId' => $departmentId,
        ]);
    }

    /**
     * Store quiz assignments.
     */

    public function store(QuizAssignmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        try {
            $assignedUsers = [];
            $skippedUsers = [];
            $assignmentIds = [];

            DB::beginTransaction();

            // Preload users to avoid N+1
            $users = User::whereIn('id', $validated['user_ids'])
                ->get()
                ->keyBy('id');

            foreach ($validated['user_ids'] as $userId) {

                // Check if assignment already exists
                $existingAssignment = QuizAssignment::where('user_id', $userId)
                    ->where('quiz_id', $validated['quiz_id'])
                    ->first();

                if ($existingAssignment) {
                    $skippedUsers[] = $users[$userId]->name ?? "User #{$userId}";
                    continue;
                }

                // Create new assignment
                $assignment = QuizAssignment::create([
                    'user_id'            => $userId,
                    'quiz_id'            => $validated['quiz_id'],
                    'assigned_by'        => auth()->id(),
                    'assigned_at'        => now(),
                    'notification_sent'  => false,
                ]);

                $assignmentIds[] = $assignment->id;
                $assignedUsers[] = $users[$userId]->name ?? "User #{$userId}";
            }

            DB::commit();

            // Send notifications if requested (using your private-function style)
            if ($validated['send_notification'] === 'email_now' && count($assignmentIds) > 0) {
                $quiz = Quiz::findOrFail($validated['quiz_id']);
                $this->notifyAssignedUsersOfQuiz($quiz, $assignmentIds);
            }

            // Build success message
            $message = count($assignedUsers) . ' user(s) assigned successfully.';
            if (count($skippedUsers) > 0) {
                $message .= ' ' . count($skippedUsers) . ' user(s) were already assigned this quiz.';
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to assign quiz: ' . $e->getMessage());
        }
    }

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

            // Ultimate fallback
            return route('admin.quizzes.show', $quiz->id);
        }
    }

    /**
     * Same email logic as notifyEnrolledUsersOfNewQuiz,
     * but targets ONLY the users you just assigned.
     */
    private function notifyAssignedUsersOfQuiz(Quiz $quiz, array $assignmentIds): void
    {
        try {
            $course = $quiz->getAssociatedCourse();

            if (!$course) {
                return;
            }

            // Load assignments + users in one go
            $assignments = QuizAssignment::with('user')
                ->whereIn('id', $assignmentIds)
                ->get();

            if ($assignments->count() === 0) {
                return;
            }

            foreach ($assignments as $assignment) {
                try {
                    $user = $assignment->user;

                    if (!$user || empty($user->email)) {
                        continue;
                    }

                    // Generate quiz link based on your routes
                    $quizLink = $this->generateQuizLink($quiz, $course);

                    // Send email
                    Mail::to($user->email)
                        ->send(new QuizCreatedNotification($quiz, $course, $user, $quizLink));

                    // Mark as sent for THIS assignment
                    $assignment->update(['notification_sent' => true]);

                } catch (\Exception $e) {
                    // skip failing user, continue others
                }

                usleep(200000); // 0.2 second delay
            }

        } catch (\Exception $e) {
            // fail silently like your original function
        }
    }

    /**
     * Send notifications to selected users for their assignments.
     */
    public function notify(Request $request): RedirectResponse
    {
        $request->validate([
            'assignment_ids' => ['required', 'array', 'min:1'],
            'assignment_ids.*' => ['exists:quiz_assignments,id'],
        ]);

        try {
            $notifiedCount = 0;
            $alreadyNotifiedCount = 0;

            foreach ($request->assignment_ids as $assignmentId) {
                $assignment = QuizAssignment::with(['user', 'quiz', 'assignedBy'])->find($assignmentId);

                if ($assignment->notification_sent) {
                    $alreadyNotifiedCount++;
                    continue;
                }

                // Send notification
                $assignment->user->notify(new QuizAssignedNotification($assignment));

                // Mark as sent
                $assignment->update(['notification_sent' => true]);
                $notifiedCount++;
            }

            $message = $notifiedCount . ' notification(s) sent successfully.';
            if ($alreadyNotifiedCount > 0) {
                $message .= ' ' . $alreadyNotifiedCount . ' user(s) were already notified.';
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to send notifications: ' . $e->getMessage());
        }
    }
}
