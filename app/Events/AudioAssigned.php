<?php

namespace App\Events;

use App\Models\Audio;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AudioAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Audio $audio;
    public User $user;
    public ?string $loginLink;
    public User $assignedBy;
    public array $metadata;

    public function __construct(
        Audio $audio,
        User $user,
        ?string $loginLink = null,
        User $assignedBy = null,
        array $metadata = []
    ) {
        $this->audio = $audio;
        $this->user = $user;
        $this->loginLink = $loginLink;
        $this->assignedBy = $assignedBy ?? auth()->user();
        $this->metadata = $metadata;
    }
}
