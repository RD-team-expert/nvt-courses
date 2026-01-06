<?php

namespace Database\Factories;

use App\Models\UserLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserLevel>
 */
class UserLevelFactory extends Factory
{
    protected $model = UserLevel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('L?')),
            'name' => $this->faker->jobTitle(),
            'hierarchy_level' => $this->faker->numberBetween(1, 5),
            'description' => $this->faker->sentence(),
            'can_manage_levels' => [],
        ];
    }

    /**
     * Employee level (L1 - lowest)
     */
    public function employee(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'L1',
            'name' => 'Employee',
            'hierarchy_level' => 1,
            'description' => 'Regular employee level',
            'can_manage_levels' => [],
        ]);
    }

    /**
     * Manager level (L2)
     */
    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'L2',
            'name' => 'Manager',
            'hierarchy_level' => 2,
            'description' => 'Manager level',
            'can_manage_levels' => ['L1'],
        ]);
    }

    /**
     * Director level (L3)
     */
    public function director(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'L3',
            'name' => 'Director',
            'hierarchy_level' => 3,
            'description' => 'Director level',
            'can_manage_levels' => ['L1', 'L2'],
        ]);
    }

    /**
     * Admin level (L4 - highest)
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'L4',
            'name' => 'Admin',
            'hierarchy_level' => 4,
            'description' => 'Admin level',
            'can_manage_levels' => ['L1', 'L2', 'L3'],
        ]);
    }
}
