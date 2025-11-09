<?php

namespace Database\Factories;

use App\Models\VideoCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VideoCategoryFactory extends Factory
{
    protected $model = VideoCategory::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true); // Generate a random 2-word name
        
        return [
            'name' => $name,
            'description' => $this->faker->sentence(),
            'slug' => null, // Let the model's boot() method auto-generate this
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 100),
        ];
    }

    /**
     * State: Inactive category
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * State: Category with specific name
     */
    public function withName(string $name): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $name,
            'slug' => Str::slug($name), // ✅ Added slug
               'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100), // ✅ Added sort_order
        ]);
    }

    /**
     * State: Category with specific sort order
     */
    public function withSortOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'sort_order' => $order,
        ]);
    }
}
