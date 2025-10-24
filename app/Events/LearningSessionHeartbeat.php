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

class LearningSessionHeartbeat implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public ModuleContent $content,
        public LearningSession $session,
        public int $skipCountIncrement,
        public int $seekCountIncrement,
        public int $pauseCountIncrement,
        public float $watchTimeIncrement
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id . '.heartbeat'),
            new PrivateChannel('session.' . $this->session->id . '.activity'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'content_id' => $this->content->id,
            'session_id' => $this->session->id,
            'incremental_data' => [
                'skip_count' => $this->skipCountIncrement,
                'seek_count' => $this->seekCountIncrement,
                'pause_count' => $this->pauseCountIncrement,
                'watch_time' => $this->watchTimeIncrement,
            ],
            'cumulative_totals' => [
                'total_skip_count' => $this->session->video_skip_count,
                'total_seek_count' => $this->session->seek_count,
                'total_pause_count' => $this->session->pause_count,
                'total_watch_time' => $this->session->video_watch_time,
                'total_duration' => $this->session->total_duration_minutes,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}
