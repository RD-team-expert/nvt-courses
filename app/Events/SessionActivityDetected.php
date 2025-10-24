<?php

namespace App\Events;

use App\Models\User;
use App\Models\ModuleContent;
use App\Models\LearningSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class SessionActivityDetected implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public ModuleContent $content,
        public LearningSession $session,
        public string $action, // 'start', 'heartbeat', 'end', 'pause', 'play', 'seek', 'skip'
        public float $currentPosition,
        public array $activityData = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id . '.session.' . $this->session->id),
            new PrivateChannel('content.' . $this->content->id . '.activity'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'content_id' => $this->content->id,
            'session_id' => $this->session->id,
            'action' => $this->action,
            'current_position' => $this->currentPosition,
            'session_duration' => $this->session->total_duration_minutes,
            'activity_data' => $this->activityData,
            'timestamp' => now()->toISOString(),
        ];
    }
}
