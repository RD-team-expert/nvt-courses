<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Mail\PasswordResetWithLoginLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ResendLoginController extends Controller
{
    public function index(Request $request)
    {
        // Get users with expired or missing login tokens
        $query = User::query()
            ->whereNotNull('email_verified_at')
            ->where(function($q) {
                $q->whereNull('login_token_expires_at')
                    ->orWhere('login_token_expires_at', '<', now());
            });

        // Filter by search term if provided
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->with(['courseRegistrations.course'])
            ->paginate(15)
            ->withQueryString();

        // Get available courses for bulk assignment
        $courses = Course::select('id', 'name')->get();

        return Inertia::render('Admin/Users/ResendLoginLinks', [
            'users' => $users,
            'courses' => $courses,
            'filters' => $request->only(['search'])
        ]);
    }

    public function resend(Request $request, User $user)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id'
        ]);

        try {
            $course = Course::findOrFail($request->course_id);

            // Generate new login link
            $loginLink = $user->generateLoginLink($course->id);

            // Send email with new login link
            Mail::to($user->email)->send(new PasswordResetWithLoginLink($user, $course, $loginLink));

            Log::info("Login link resent to user {$user->email} for course {$course->name}");

            return back()->with('success', "Login link sent successfully to {$user->name}");

        } catch (\Exception $e) {
            Log::error("Failed to resend login link to {$user->email}: " . $e->getMessage());
            return back()->with('error', 'Failed to send login link. Please try again.');
        }
    }

    public function bulkResend(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'course_id' => 'required|exists:courses,id'
        ]);

        try {
            $course = Course::findOrFail($request->course_id);
            $users = User::whereIn('id', $request->user_ids)->get();

            $successCount = 0;
            $failureCount = 0;

            foreach ($users as $user) {
                try {
                    // Generate new login link
                    $loginLink = $user->generateLoginLink($course->id);

                    // Send email
                    Mail::to($user->email)->send(new PasswordResetWithLoginLink($user, $course, $loginLink));

                    $successCount++;
                    Log::info("Bulk login link sent to {$user->email}");

                    // Rate limiting
                    sleep(1);

                } catch (\Exception $e) {
                    $failureCount++;
                    Log::error("Failed to send bulk login link to {$user->email}: " . $e->getMessage());
                }
            }

            $message = "Login links sent: {$successCount} successful, {$failureCount} failed.";
            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error("Bulk resend failed: " . $e->getMessage());
            return back()->with('error', 'Bulk operation failed. Please try again.');
        }
    }
}
