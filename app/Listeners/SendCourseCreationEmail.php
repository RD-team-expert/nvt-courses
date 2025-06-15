<?php

namespace App\Listeners;

use App\Events\CourseCreated;
use App\Mail\CourseCreatedNotification; // Changed from CourseCreationNotification
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendCourseCreationEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CourseCreated $event): void
    {
        User::where('role', '!=', 'admin')->each(function ($user) use ($event) {
            Mail::to($user->email)
                ->queue(new CourseCreatedNotification($event->course, $user));
        });
    }
}