<?php

namespace App\Http\Controllers;

use App\Models\CourseOnline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthVaiEmailController extends Controller
{
    /**
     * Handle token-based login from email notifications
     * 
     * @param Request $request
     * @param User $user
     * @param int $course - The CourseOnline ID (using int since route binding may conflict)
     */
    public function tokenLogin(Request $request, User $user, int $course)
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

        // Find the online course
        $courseOnline = CourseOnline::find($course);
        if (!$courseOnline) {
            // Clear the token and log in, but redirect to dashboard
            $user->update([
                'login_token' => null,
                'login_token_expires_at' => null,
            ]);
            Auth::login($user);
            
            return redirect()->route('dashboard')
                ->with('warning', 'Course not found. You have been redirected to the dashboard.');
        }

        // Clear the token (single-use)
        $user->update([
            'login_token' => null,
            'login_token_expires_at' => null,
        ]);

        // Log the user in
        Auth::login($user);

        // ✅ Redirect directly to the assigned online course
        return redirect()->route('courses-online.show', $courseOnline->id)
            ->with('success', 'Welcome! You have been logged in and redirected to your assigned course.');
    }

    /**
     * Handle token-based login for audio assignments
     * 
     * @param Request $request
     * @param User $user
     * @param int $audio - The Audio ID
     */
    public function audioTokenLogin(Request $request, User $user, int $audio)
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

        // Find the audio
        $audioModel = \App\Models\Audio::find($audio);
        if (!$audioModel) {
            // Clear the token and log in, but redirect to dashboard
            $user->update([
                'login_token' => null,
                'login_token_expires_at' => null,
            ]);
            Auth::login($user);
            
            return redirect()->route('dashboard')
                ->with('warning', 'Audio not found. You have been redirected to the dashboard.');
        }

        // Clear the token (single-use)
        $user->update([
            'login_token' => null,
            'login_token_expires_at' => null,
        ]);

        // Log the user in
        Auth::login($user);

        // Redirect to the audio player
        return redirect()->route('audio.show', $audioModel->id)
            ->with('success', 'Welcome! You have been logged in and redirected to your assigned audio.');
    }
}
