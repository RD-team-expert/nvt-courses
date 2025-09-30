<?php

namespace App\Listeners;

use App\Events\CourseCreated;
use App\Jobs\SendCourseCreationEmails;
use App\Mail\CourseCreatedNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendCourseCreationEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(CourseCreated $event): void
    {
        SendCourseCreationEmails::dispatch($event->course);
    }
}
