<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class CourseAssignmentManagerNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Course $course;
    public $assignedUsers; // Collection of users
    public User $assignedBy;
    public User $manager;
    public $assignmentDetails;

    public function __construct(Course $course, $assignedUsers, User $assignedBy, User $manager, $assignmentDetails = null)
    {
        $this->course = $course;
        $this->assignedUsers = $assignedUsers;
        $this->assignedBy = $assignedBy;
        $this->manager = $manager;
        $this->assignmentDetails = $assignmentDetails ?: [];
    }

    public function build()
    {
        $userCount = $this->assignedUsers->count();
        $userNames = $this->assignedUsers->take(3)->pluck('name')->join(', ');

        if ($userCount > 3) {
            $userNames .= " and " . ($userCount - 3) . " more";
        }

        return $this->subject("Team Course Assignment: {$this->course->name}")
            ->view('emails.course-assignment-manager-notification')
            ->with([
                'course' => $this->course,
                'assignedUsers' => $this->assignedUsers,
                'assignedBy' => $this->assignedBy,
                'manager' => $this->manager,
                'userCount' => $userCount,
                'userNames' => $userNames,
                'assignmentDate' => Carbon::now()->format('F j, Y'),
                'assignmentDetails' => $this->assignmentDetails,
            ]);
    }
}
