<?php

namespace App\Events;

use App\Models\User;
use App\Models\ModuleContent;
use App\Models\CourseOnlineAssignment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class ContentCompletionTracked implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public ModuleContent $content,
        public CourseOnlineAssignment $assignment,
        public float $finalPosition,
        public float $finalWatchTime,
        public float $courseProgress
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id . '.completions'),
            new PrivateChannel('course.' . $this->content->module->course_online_id . '.completions'),
            new PrivateChannel('assignment.' . $this->assignment->id . '.progress'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'content_id' => $this->content->id,
            'assignment_id' => $this->assignment->id,
            'course_id' => $this->content->module->course_online_id,
            'final_position' => $this->finalPosition,
            'final_watch_time' => $this->finalWatchTime,
            'course_progress' => $this->courseProgress,
            'content_title' => $this->content->title,
            'content_type' => $this->content->content_type,
            'completed_at' => now()->toISOString(),
        ];
    }
}
