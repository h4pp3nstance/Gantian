<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Fine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fine>
 */
class FineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'fine_amount' => fake()->randomFloat(2, 5, 250),
            'reason' => fake()->sentence(),
            'status' => Fine::STATUS_UNPAID,
        ];
    }

    /**
     * Indicate that the fine should be unpaid.
     */
    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Fine::STATUS_UNPAID,
        ]);
    }

    /**
     * Indicate that the fine should be paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Fine::STATUS_PAID,
        ]);
    }

    /**
     * Indicate that the fine should be waived.
     */
    public function waived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Fine::STATUS_WAIVED,
        ]);
    }
}
