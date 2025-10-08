<?php

namespace App\Events;

use App\Models\CourseOnline;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourseOnlineAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public CourseOnline $course;
    public User $user;
    public ?string $loginLink;
    public User $assignedBy;
    public array $metadata;

    public function __construct(
        CourseOnline $course,
        User $user,
        ?string $loginLink = null,
        User $assignedBy = null,
        array $metadata = []
    ) {
        $this->course = $course;
        $this->user = $user;
        $this->loginLink = $loginLink;
        $this->assignedBy = $assignedBy ?? auth()->user();
        $this->metadata = $metadata;
    }
}
