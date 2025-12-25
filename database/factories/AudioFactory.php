<?php

namespace Database\Factories;

use App\Models\Audio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AudioFactory extends Factory
{
    protected $model = Audio::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'google_cloud_url' => $this->faker->url(),
            'duration' => $this->faker->numberBetween(60, 3600),
            'thumbnail_url' => $this->faker->imageUrl(),
            'is_active' => true,
            'created_by' => User::factory(),
            'audio_category_id' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withLocalStorage(): static
    {
        return $this->state(fn (array $attributes) => [
            'storage_type' => 'local',
            'local_path' => 'audios/' . $this->faker->uuid() . '.mp3',
            'google_cloud_url' => null,
        ]);
    }

    public function withGoogleDrive(): static
    {
        return $this->state(fn (array $attributes) => [
            'storage_type' => 'google_drive',
            'local_path' => null,
        ]);
    }
}
