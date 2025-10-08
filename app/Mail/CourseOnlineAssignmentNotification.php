<?php

namespace App\Mail;

use App\Models\CourseOnline;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseOnlineAssignmentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public CourseOnline $course;
    public User $user;
    public User $assignedBy;
    public ?string $loginLink;
    public  $metadata;

    public function __construct(
        CourseOnline $course,
        User $user,
        User $assignedBy,
        ?string $loginLink = null,
        array $metadata = []
    ) {
        $this->course = $course;
        $this->user = $user;
        $this->assignedBy = $assignedBy;
        $this->loginLink = $loginLink;
        $this->metadata = $metadata;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Online Course Assignment: {$this->course->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.course-online-assignment-notification',
        );
    }
}
