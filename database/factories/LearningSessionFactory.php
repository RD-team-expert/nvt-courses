<?php

namespace Database\Factories;

use App\Models\LearningSession;
use App\Models\User;
use App\Models\ModuleContent;
use App\Models\CourseOnline;
use Illuminate\Database\Eloquent\Factories\Factory;

class LearningSessionFactory extends Factory
{
    protected $model = LearningSession::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_online_id' => CourseOnline::factory(),
            'content_id' => ModuleContent::factory(),
            'session_start' => now()->subMinutes(30),
            'session_end' => null,
            'total_duration_minutes' => 0,
            
            // Video-specific tracking
            'video_watch_time' => 0,
            'video_total_duration' => null,
            'video_skip_count' => 0,
            'video_replay_count' => 0,
            'video_completion_percentage' => 0,
            
            // Activity tracking
            'clicks_count' => 0,
            'pause_count' => 0,
            'seek_count' => 0,
            'fullscreen_count' => 0,
            'speed_changes' => 0,
            
            // Engagement flags - ✅ FIXED: Set default values instead of null
            'is_suspicious_activity' => false,
            'cheating_score' => 0,    // ✅ Changed from null to 0
            'attention_score' => 0,   // ✅ Changed from null to 0
        ];
    }

    public function ended()
    {
        return $this->state(fn (array $attributes) => [
            'session_end' => now(),
            'total_duration_minutes' => 30,
            'video_completion_percentage' => 100,
            'attention_score' => 85,
            'cheating_score' => 10,
        ]);
    }

    public function suspicious()
    {
        return $this->state(fn (array $attributes) => [
            'session_end' => now(),
            'total_duration_minutes' => 2,
            'video_watch_time' => 60,
            'video_skip_count' => 25,
            'video_completion_percentage' => 100,
            'attention_score' => 20,
            'cheating_score' => 95,
            'is_suspicious_activity' => true,
        ]);
    }
}
