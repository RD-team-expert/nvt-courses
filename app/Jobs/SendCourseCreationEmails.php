<?php

namespace App\Jobs;

use App\Mail\CourseCreatedNotification;
use App\Models\Course;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendCourseCreationEmails 
{
    protected $course;
    /**
     * Create a new job instance.
     */
    public function __construct(Course $course)
    {
        $this->course = $course;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        User::all()->each(function ($user) {
            Mail::to($user->email)->send(new CourseCreatedNotification($this->course, $user));
        });
    }
}
