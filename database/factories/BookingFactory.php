<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+30 days');
        $endDate = fake()->dateTimeBetween($startDate, '+45 days');

        return [
            'user_id' => User::factory(),
            'item_id' => Item::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_price' => fake()->randomFloat(2, 25, 2500),
            'status' => Booking::STATUS_PENDING,
        ];
    }

    /**
     * Indicate that the booking should be pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Booking::STATUS_PENDING,
        ]);
    }

    /**
     * Indicate that the booking should be approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Booking::STATUS_APPROVED,
        ]);
    }

    /**
     * Indicate that the booking should be active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Booking::STATUS_ACTIVE,
        ]);
    }

    /**
     * Indicate that the booking should be completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Booking::STATUS_COMPLETED,
        ]);
    }

    /**
     * Indicate that the booking should be cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Booking::STATUS_CANCELLED,
        ]);
    }
}
