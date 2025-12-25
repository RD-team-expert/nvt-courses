<?php

namespace App\Mail;

use App\Models\Audio;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AudioAssignmentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Audio $audio;
    public User $user;
    public User $assignedBy;
    public ?string $loginLink;
    public array $assignmentMetadata;

    public function __construct(
        Audio $audio,
        User $user,
        User $assignedBy,
        ?string $loginLink = null,
        array $assignmentMetadata = []
    ) {
        $this->audio = $audio;
        $this->user = $user;
        $this->assignedBy = $assignedBy;
        $this->loginLink = $loginLink;
        $this->assignmentMetadata = $assignmentMetadata;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Audio Assignment: {$this->audio->name}"
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.audio-assignment-notification',
            with: [
                'audio' => $this->audio,
                'user' => $this->user,
                'assignedBy' => $this->assignedBy,
                'loginLink' => $this->loginLink,
                'assignmentMetadata' => $this->assignmentMetadata,
                'audioName' => $this->audio->name,
                'audioDescription' => $this->audio->description,
                'audioDuration' => $this->audio->formatted_duration,
            ]
        );
    }
}
