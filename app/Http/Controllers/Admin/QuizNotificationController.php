<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\User;
use App\Mail\QuizCreatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class QuizNotificationController extends Controller
{
    /**
     * Notify selected users of a quiz assignment.
     */
    public function notifySelectedUsers(Request $request, Quiz $quiz)
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        // Fetch users to notify
        $users = User::whereIn('id', $request->input('user_ids'))->get();

        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No valid users found for notification.',
            ], 422);
        }

        $course = $quiz->getAssociatedCourse();
        if (!$course) {
            return response()->json([
                'message' => 'Associated course not found for this quiz.',
            ], 404);
        }

        foreach ($users as $user) {
            if (empty($user->email)) {
                continue;
            }

            $quizLink = $this->generateQuizLink($quiz, $course);

            try {
                Mail::to($user->email)->send(new QuizCreatedNotification($quiz, $course, $user, $quizLink));
                usleep(200000); // To limit email sending frequency
            } catch (\Exception $e) {
                // Log error or continue silently as preferred
            }
        }

        return response()->json([
            'message' => 'Notifications sent successfully.',
        ]);
    }

    /**
     * Helper method to generate quiz URL.
     */
    private function generateQuizLink(Quiz $quiz, $course): string
    {
        // Your existing logic for route generation
        try {
            // Add your route detection and fallback logic here (same as in QuizController)
            if ($quiz->isOnlineCourse()) {
                $possibleRoutes = [
                    // ... your online course related routes
                ];
                foreach ($possibleRoutes as $name) {
                    if (\Route::has($name)) {
                        return route($name, [$course->id, $quiz->id]);
                    }
                }
            } else {
                $possibleRoutes = [
                    // ... your regular course routes
                ];
                foreach ($possibleRoutes as $name) {
                    if (\Route::has($name)) {
                        return route($name, [$course->id, $quiz->id]);
                    }
                }
            }
            if (\Route::has('quizzes.show')) {
                return route('quizzes.show', $quiz->id);
            }
            return route('admin.quizzes.show', $quiz->id);

        } catch (\Exception $e) {
            return route('admin.quizzes.show', $quiz->id);
        }
    }
}
