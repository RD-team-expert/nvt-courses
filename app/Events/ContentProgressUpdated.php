<?php

namespace App\Events;

use App\Models\User;
use App\Models\ModuleContent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class ContentProgressUpdated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public ModuleContent $content,
        public float $currentPosition,
        public float $completionPercentage,
        public int $watchTime,
        public bool $skipDetected,
        public float $positionJump
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id . '.content.' . $this->content->id),
            new PrivateChannel('course.' . $this->content->module->course_online_id . '.progress'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'content_id' => $this->content->id,
            'current_position' => $this->currentPosition,
            'completion_percentage' => $this->completionPercentage,
            'watch_time' => $this->watchTime,
            'skip_detected' => $this->skipDetected,
            'position_jump' => $this->positionJump,
            'content_type' => $this->content->content_type,
            'timestamp' => now()->toISOString(),
        ];
    }
}
