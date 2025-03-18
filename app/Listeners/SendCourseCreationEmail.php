<?php

namespace App\Listeners;

use App\Events\CourseCreated;
use App\Mail\CourseCreationNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendCourseCreationEmail
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CourseCreated $event): void
    {
        $users = User::all();

        foreach ($users as $user) {
            Mail::to($user->email)->queue(
                new CourseCreationNotification($event->course, $user)
            );
        }
    }
}
