<?php

namespace App\Listeners;

use App\Events\CourseAssigned;
use App\Mail\CourseCreatedNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendCourseNotification
{
    public function handle(CourseAssigned $event)
    {
        $user = $event->user;
        $course = $event->course;
        $loginLink = $event->loginLink;
        try {
            // ✅ Send email with password
            Mail::to($user->email)->send(new CourseCreatedNotification($course, $user, $loginLink));

            Log::info("Course notification sent successfully to: {$user->email}");

        } catch (\Exception $e) {
            Log::error("Failed to send course notification to {$user->email}: " . $e->getMessage());
        }
    }
}
