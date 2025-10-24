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

class SessionScoreCalculated implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public ModuleContent $content,
        public LearningSession $session,
        public int $attentionScore,
        public int $cheatingScore,
        public bool $isSuspiciousActivity
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->user->id . '.scores'),
            new PrivateChannel('session.' . $this->session->id . '.analytics'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'content_id' => $this->content->id,
            'session_id' => $this->session->id,
            'attention_score' => $this->attentionScore,
            'cheating_score' => $this->cheatingScore,
            'is_suspicious' => $this->isSuspiciousActivity,
            'session_metrics' => [
                'duration_minutes' => $this->session->total_duration_minutes,
                'completion_percentage' => $this->session->video_completion_percentage,
                'skip_count' => $this->session->video_skip_count,
                'seek_count' => $this->session->seek_count,
                'pause_count' => $this->session->pause_count,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}
