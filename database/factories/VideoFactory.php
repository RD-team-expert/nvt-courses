<?php

namespace Database\Factories;

use App\Models\Video;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoFactory extends Factory
{
    protected $model = Video::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(4), // ✅ Changed from 'title' to 'name' (matches your model)
            'description' => fake()->paragraph(),
            'google_drive_url' => 'https://drive.google.com/file/d/ABC123/view',
            'duration' => fake()->numberBetween(300, 3600),
            'thumbnail_path' => null,
            'is_active' => true,
            'created_by' => User::factory(),
            // ✅ REMOVED: video_category_id - column doesn't exist in your table
        ];
    }

    public function inactive()
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
