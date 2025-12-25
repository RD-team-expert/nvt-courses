<?php

namespace App\Mail;

use App\Models\Audio;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class AudioAssignmentManagerNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Audio $audio;
    public Collection $teamMembers;
    public User $assignedBy;
    public User $manager;
    public array $assignmentMetadata;

    public function __construct(
        Audio $audio,
        Collection $teamMembers,
        User $assignedBy,
        User $manager,
        array $assignmentMetadata = []
    ) {
        $this->audio = $audio;
        $this->teamMembers = $teamMembers;
        $this->assignedBy = $assignedBy;
        $this->manager = $manager;
        $this->assignmentMetadata = $assignmentMetadata;
    }

    public function envelope(): Envelope
    {
        $memberCount = $this->teamMembers->count();

        $subject = $memberCount === 1
            ? "Team Member Assigned to Audio: {$this->audio->name}"
            : "{$memberCount} Team Members Assigned to Audio: {$this->audio->name}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.audio-assignment-manager-notification',
            with: [
                'audio' => $this->audio,
                'teamMembers' => $this->teamMembers,
                'assignedBy' => $this->assignedBy,
                'manager' => $this->manager,
                'assignmentMetadata' => $this->assignmentMetadata,
                'audioName' => $this->audio->name,
                'audioDescription' => $this->audio->description,
                'audioDuration' => $this->audio->formatted_duration,
            ]
        );
    }
}
