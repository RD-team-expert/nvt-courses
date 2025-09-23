<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CoursePrivacyChangeNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Course $course;
    public User $user;
    public string $loginLink;

    public function __construct(Course $course, User $user, string $loginLink)
    {
        $this->course = $course;
        $this->user = $user;
        $this->loginLink = $loginLink;
    }

    public function build()
    {
        return $this->subject("🎉 {$this->course->name} is Now Available to Everyone!")
            ->view('emails.course-privacy-change')
            ->with([
                'course' => $this->course,
                'user' => $this->user,
                'loginLink' => $this->loginLink,
            ]);
    }
}
