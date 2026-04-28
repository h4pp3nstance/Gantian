<?php

namespace Database\Factories;

use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'price_per_day' => fake()->randomFloat(2, 10, 500),
            'stock' => fake()->numberBetween(1, 25),
            'status' => Item::STATUS_AVAILABLE,
        ];
    }

    /**
     * Indicate that the item should be available.
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Item::STATUS_AVAILABLE,
        ]);
    }

    /**
     * Indicate that the item should be unavailable.
     */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Item::STATUS_UNAVAILABLE,
        ]);
    }

    /**
     * Indicate that the item should be under maintenance.
     */
    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Item::STATUS_MAINTENANCE,
        ]);
    }
}
