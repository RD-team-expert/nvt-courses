<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseCreatedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $course;
    public $user;
    public $loginLink;

    /**
     * Create a new message instance.
     */
    public function __construct(Course $course, User $user, string $loginLink)
    {
        $this->course = $course;
        $this->user = $user;
        $this->loginLink = $loginLink;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Course Available: ' . $this->course->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $this->course->load('availabilities');

        return new Content(
            view: 'emails.course-created1',
            with: [
                'course' => $this->course,
                'user' => $this->user,
                'courseName' => $this->course->name,
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'loginLink' => $this->loginLink, // ✅ Login link for one-click access
                'description' => $this->course->description,
                'availabilities' => $this->course->availabilities,

                // ✅ Fixed: Access start_date properly
                'startDate' => $this->course->availabilities->first()
                    ? $this->course->availabilities->first()->start_date->format('F j, Y')
                    : 'TBD',
                'endDate' => $this->course->availabilities->first()
                    ? $this->course->availabilities->first()->end_date->format('F j, Y')
                    : 'TBD',
            ]
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
