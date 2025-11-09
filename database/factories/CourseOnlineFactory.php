<?php

namespace Database\Factories;

use App\Models\CourseOnline;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseOnlineFactory extends Factory
{
    protected $model = CourseOnline::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'image_path' => null,
            'estimated_duration' => fake()->numberBetween(60, 300),
            'difficulty_level' => fake()->randomElement(['beginner', 'intermediate', 'advanced']),
            'is_active' => true,
            'created_by' => User::factory(),
            'deadline' => null,
            'has_deadline' => false,
            'deadline_type' => 'none', // ✅ FIX: Set default value instead of null
        ];
    }

    public function withDeadline()
    {
        return $this->state(fn (array $attributes) => [
            'deadline' => now()->addDays(30),
            'has_deadline' => true,
            'deadline_type' => 'fixed',
        ]);
    }
}
