<?php

namespace App\Listeners;

use App\Events\CourseCreated;
use App\Jobs\SendCourseCreationEmails;
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
        SendCourseCreationEmails::dispatch($event->course);

//        User::all()->each(function ($user) use ($event) {
//            Mail::to($user->email)
//                ->send(new CourseCreationNotification($event->course, $user));
//        });
    }
}
