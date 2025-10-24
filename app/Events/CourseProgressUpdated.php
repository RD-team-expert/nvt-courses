<?php

namespace App\Events;

use App\Models\User;
use App\Models\CourseOnline;
use App\Models\CourseOnlineAssignment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class CourseProgressUpdated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public CourseOnline $course,
        public CourseOnlineAssignment $assignment,
        public float $previousProgress,
        public float $newProgress
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id . '.courses'),
            new PrivateChannel('course.' . $this->course->id . '.progress'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'course_id' => $this->course->id,
            'assignment_id' => $this->assignment->id,
            'previous_progress' => $this->previousProgress,
            'new_progress' => $this->newProgress,
            'course_name' => $this->course->name,
            'assignment_status' => $this->assignment->status,
            'timestamp' => now()->toISOString(),
        ];
    }
}
