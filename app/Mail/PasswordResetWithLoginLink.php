<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetWithLoginLink extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $course;
    public $loginLink;

    public function __construct(User $user, Course $course, string $loginLink)
    {
        $this->user = $user;
        $this->course = $course;
        $this->loginLink = $loginLink;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Course Access Link - ' . $this->course->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-login-link',
            with: [
                'user' => $this->user,
                'course' => $this->course,
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'courseName' => $this->course->name,
                'loginLink' => $this->loginLink,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
