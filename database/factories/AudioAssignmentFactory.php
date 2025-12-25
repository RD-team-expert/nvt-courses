<?php

namespace Database\Factories;

use App\Models\Audio;
use App\Models\AudioAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AudioAssignmentFactory extends Factory
{
    protected $model = AudioAssignment::class;

    public function definition(): array
    {
        return [
            'audio_id' => Audio::factory(),
            'user_id' => User::factory(),
            'assigned_by' => User::factory(),
            'assigned_at' => now(),
            'started_at' => null,
            'completed_at' => null,
            'status' => 'assigned',
            'progress_percentage' => 0,
            'notification_sent' => false,
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'started_at' => now()->subHours($this->faker->numberBetween(1, 24)),
            'progress_percentage' => $this->faker->numberBetween(1, 99),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subDays($this->faker->numberBetween(1, 7)),
            'completed_at' => now()->subHours($this->faker->numberBetween(1, 24)),
            'progress_percentage' => 100,
        ]);
    }

    public function notificationSent(): static
    {
        return $this->state(fn (array $attributes) => [
            'notification_sent' => true,
        ]);
    }
}
