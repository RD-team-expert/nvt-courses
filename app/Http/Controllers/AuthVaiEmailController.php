<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthVaiEmailController extends Controller
{
    public function tokenLogin(Request $request, User $user, Course $course)
    {
        // Check if token exists and hasn't expired
        if ($user->login_token === null || $user->loginTokenExpired()) {
            return redirect('/login')
                ->with('error', 'Login link has expired. Please contact your administrator.');
        }

        $token = $request->query('token');

        // Verify the token matches (using hash comparison for security)
        if (!hash_equals($user->login_token, hash('sha256', $token))) {
            return redirect('/login')
                ->with('error', 'Invalid login link.');
        }
        // Clear the token (single-use)
        $user->update([
            'login_token' => null,
            'login_token_expires_at' => null,
        ]);
        // Log the user in
        Auth::login($user);

        // ✅ Redirect directly to the assigned course instead of dashboard
        return redirect()->route('courses.show', $course->id)
            ->with('success', 'Welcome! You have been logged in and redirected to your assigned course.');
    }
}
