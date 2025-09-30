<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class PublicCourseEnrollmentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Course $course;
    public User $enrolledUser;
    public User $manager;
    public $enrollmentDetails;

    public function __construct(Course $course, User $enrolledUser, User $manager, $enrollmentDetails = null)
    {
        $this->course = $course;
        $this->enrolledUser = $enrolledUser;
        $this->manager = $manager;
        $this->enrollmentDetails = $enrollmentDetails ?: [];
    }

    public function build()
    {
        return $this->subject("Team Member Enrolled in Public Course: {$this->course->name}")
            ->view('emails.public-course-enrollment-notification')
            ->with([
                'course' => $this->course,
                'enrolledUser' => $this->enrolledUser,
                'manager' => $this->manager,
                'enrollmentDate' => Carbon::now()->format('F j, Y'),
                'enrollmentDetails' => $this->enrollmentDetails,
            ]);
    }
}
