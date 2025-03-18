<?php

namespace App\Listeners;

use App\Events\CourseCompleted;
use App\Mail\CourseCompletionNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendCourseCompletionEmail
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
    public function handle(CourseCompleted $event): void
    {
        Mail::to($event->user->email)->send(
            new CourseCompletionNotification($event->course, $event->user)
        );
    }
}
