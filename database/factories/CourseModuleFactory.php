<?php

namespace Database\Factories;

use App\Models\CourseModule;
use App\Models\CourseOnline;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseModuleFactory extends Factory
{
    protected $model = CourseModule::class;

    public function definition(): array
    {
        return [
            'course_online_id' => CourseOnline::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'order_number' => fake()->numberBetween(1, 10),
            'estimated_duration' => fake()->numberBetween(30, 120),
            'is_required' => true,
            'is_active' => true,
        ];
    }
}
