<?php

namespace App\Listeners;

use App\Events\CourseEnrolled;
use App\Mail\CourseEnrollmentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendCourseEnrollmentEmail
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
    public function handle(CourseEnrolled $event): void
    {
        Mail::to($event->user->email)->send(
            new CourseEnrollmentNotification($event->course, $event->user)
        );
    }

}
