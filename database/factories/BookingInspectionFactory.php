<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingInspection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingInspection>
 */
class BookingInspectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory()->completed(),
            'inspected_by' => User::factory()->staff(),
            'condition_status' => BookingInspection::CONDITION_GOOD,
            'notes' => null,
            'inspected_at' => now(),
        ];
    }

    /**
     * Indicate that the inspected booking was returned in good condition.
     */
    public function good(): static
    {
        return $this->state(fn (array $attributes) => [
            'condition_status' => BookingInspection::CONDITION_GOOD,
            'notes' => null,
        ]);
    }

    /**
     * Indicate that the inspected booking had damage.
     */
    public function damaged(): static
    {
        return $this->state(fn (array $attributes) => [
            'condition_status' => BookingInspection::CONDITION_DAMAGED,
            'notes' => 'Item returned with visible damage.',
        ]);
    }

    /**
     * Indicate that the inspected booking had a missing accessory.
     */
    public function missingAccessory(): static
    {
        return $this->state(fn (array $attributes) => [
            'condition_status' => BookingInspection::CONDITION_MISSING_ACCESSORY,
            'notes' => 'One accessory was missing during check-in.',
        ]);
    }

    /**
     * Indicate that the inspected booking was returned late.
     */
    public function lateReturn(): static
    {
        return $this->state(fn (array $attributes) => [
            'condition_status' => BookingInspection::CONDITION_LATE_RETURN,
            'notes' => 'Item was returned after the scheduled end date.',
        ]);
    }
}
