<?php

namespace App\Mail;

use App\Models\CourseOnline;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CourseOnlineAssignmentManagerNotification extends Mailable
{
    use Queueable, SerializesModels;

    public CourseOnline $course;
    public Collection $teamMembers;
    public User $assignedBy;
    public User $manager;
    public  $metadata;

    public function __construct(
        CourseOnline $course,
        Collection $teamMembers,
        User $assignedBy,
        User $manager,
        array $metadata = []
    ) {
        $this->course = $course;
        $this->teamMembers = $teamMembers;
        $this->assignedBy = $assignedBy;
        $this->manager = $manager;
        $this->metadata = $metadata;
    }

    public function envelope(): Envelope
    {
        $memberCount = $this->teamMembers->count();
        $subject = $memberCount === 1
            ? "Team Member Assigned to Online Course: {$this->course->name}"
            : "{$memberCount} Team Members Assigned to Online Course: {$this->course->name}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.course-online-assignment-manager-notification',
        );
    }
}
