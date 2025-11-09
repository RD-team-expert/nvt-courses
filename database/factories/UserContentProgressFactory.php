<?php

namespace Database\Factories;

use App\Models\UserContentProgress;
use App\Models\User;
use App\Models\ModuleContent;
use App\Models\CourseOnline;
use App\Models\CourseModule;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserContentProgressFactory extends Factory
{
    protected $model = UserContentProgress::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'content_id' => ModuleContent::factory(),
            'course_online_id' => CourseOnline::factory(),
            'module_id' => CourseModule::factory(),
            'video_id' => null, // This is nullable
            'content_type' => $this->faker->randomElement(['video', 'pdf']),
            
            // Video-related fields
            'watch_time' => 0,
            'total_duration' => 0, // ✅ Changed from null to 0
            'playback_position' => 0.00,
            
            // PDF-related fields
            'pdf_pages_viewed' => 0, // ✅ Changed from null to 0 (NOT NULL column)
            
            // Progress tracking
            'completion_percentage' => 0.00,
            'is_completed' => false,
            'task_completed' => false,
            
            // Timestamps
            'completed_at' => null,
            'last_accessed_at' => now(),
        ];
    }

    /**
     * State: Completed progress (100%)
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completion_percentage' => 100.00,
            'is_completed' => true,
            'task_completed' => true,
            'completed_at' => now(),
            'last_accessed_at' => now(),
        ]);
    }

    /**
     * State: In progress with specific percentage
     */
    public function inProgress(int $percentage = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'completion_percentage' => (float) $percentage,
            'is_completed' => false,
            'task_completed' => false,
            'playback_position' => $this->faker->numberBetween(10, 100),
            'watch_time' => $this->faker->numberBetween(60, 300),
            'last_accessed_at' => now(),
        ]);
    }

    /**
     * State: Video content progress
     */
    public function forVideo(Video $video = null): static
    {
        return $this->state(function (array $attributes) use ($video) {
            $vid = $video ?? Video::factory()->create();
            
            return [
                'video_id' => $vid->id,
                'content_type' => 'video',
                'total_duration' => $vid->duration ?? 300,
                'watch_time' => $this->faker->numberBetween(0, $vid->duration ?? 300),
                'pdf_pages_viewed' => 0, // ✅ Keep as 0 for video content
            ];
        });
    }

    /**
     * State: PDF content progress
     */
    public function forPdf(int $totalPages = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'video_id' => null,
            'content_type' => 'pdf',
            'pdf_pages_viewed' => $this->faker->numberBetween(0, $totalPages),
            'total_duration' => 0, // ✅ Changed from null to 0
            'watch_time' => 0,
            'playback_position' => 0,
        ]);
    }

    /**
     * State: For specific user and content
     */
    public function forUserAndContent(User $user, ModuleContent $content): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'content_id' => $content->id,
            'course_online_id' => $content->module->course_online_id ?? CourseOnline::factory(),
            'module_id' => $content->module_id,
            'video_id' => $content->video_id,
            'content_type' => $content->content_type,
        ]);
    }

    /**
     * State: With task completed
     */
    public function withTaskCompleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'task_completed' => true,
        ]);
    }
}
