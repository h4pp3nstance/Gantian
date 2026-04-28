<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingInspection;
use App\Models\Item;
use App\Models\User;
use App\Services\BookingLifecycleService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class BookingLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_approves_pending_bookings(): void
    {
        $booking = Booking::factory()->pending()->create();

        $updated = app(BookingLifecycleService::class)->approve($booking);

        $this->assertSame(Booking::STATUS_APPROVED, $updated->status);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => Booking::STATUS_APPROVED,
        ]);
    }

    public function test_it_checks_out_approved_bookings(): void
    {
        $booking = Booking::factory()->approved()->create();

        $updated = app(BookingLifecycleService::class)->checkout($booking);

        $this->assertSame(Booking::STATUS_ACTIVE, $updated->status);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => Booking::STATUS_ACTIVE,
        ]);
    }

    public function test_it_checks_in_active_bookings_with_inspection(): void
    {
        $booking = Booking::factory()->active()->create();
        $staff = User::factory()->staff()->create();

        $updated = app(BookingLifecycleService::class)->checkin($booking, $staff, [
            'condition_status' => BookingInspection::CONDITION_GOOD,
            'notes' => '',
        ]);

        $this->assertSame(Booking::STATUS_COMPLETED, $updated->status);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => Booking::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseHas('booking_inspections', [
            'booking_id' => $booking->id,
            'inspected_by' => $staff->id,
            'condition_status' => BookingInspection::CONDITION_GOOD,
            'notes' => null,
        ]);
    }

    public function test_it_requires_notes_for_non_good_checkin_conditions(): void
    {
        $booking = Booking::factory()->active()->create();
        $staff = User::factory()->staff()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Inspection notes are required unless condition is good.');

        app(BookingLifecycleService::class)->checkin($booking, $staff, [
            'condition_status' => BookingInspection::CONDITION_DAMAGED,
            'notes' => '',
        ]);
    }

    public function test_it_keeps_checkin_and_inspection_atomic_when_transition_fails(): void
    {
        $booking = Booking::factory()->completed()->create();
        $staff = User::factory()->staff()->create();

        try {
            app(BookingLifecycleService::class)->checkin($booking, $staff, [
                'condition_status' => BookingInspection::CONDITION_GOOD,
                'notes' => '',
            ]);

            $this->fail('Expected a domain exception for invalid check-in transition.');
        } catch (DomainException $exception) {
            $this->assertSame('Cannot check in booking from status "completed".', $exception->getMessage());
        }

        $this->assertDatabaseMissing('booking_inspections', [
            'booking_id' => $booking->id,
        ]);
    }

    public function test_it_cancels_pending_and_approved_bookings(): void
    {
        $service = app(BookingLifecycleService::class);
        $pending = Booking::factory()->pending()->create();
        $approved = Booking::factory()->approved()->create();

        $this->assertSame(Booking::STATUS_CANCELLED, $service->cancel($pending)->status);
        $this->assertSame(Booking::STATUS_CANCELLED, $service->cancel($approved)->status);
    }

    public function test_it_rejects_invalid_booking_transitions(): void
    {
        $booking = Booking::factory()->active()->create();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot approve booking from status "active".');

        app(BookingLifecycleService::class)->approve($booking);
    }

    public function test_it_rejects_approval_when_reserved_bookings_consume_item_stock(): void
    {
        $item = Item::factory()->available()->create(['stock' => 1]);

        Booking::factory()
            ->approved()
            ->for($item)
            ->create([
                'start_date' => '2026-05-01',
                'end_date' => '2026-05-03',
            ]);

        $pending = Booking::factory()
            ->pending()
            ->for($item)
            ->create([
                'start_date' => '2026-05-02',
                'end_date' => '2026-05-04',
            ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot approve booking because this item is fully booked for the selected dates.');

        app(BookingLifecycleService::class)->approve($pending);
    }

    public function test_it_rejects_stale_status_transitions_atomically(): void
    {
        $booking = Booking::factory()->pending()->create();

        Booking::query()
            ->whereKey($booking->id)
            ->update(['status' => Booking::STATUS_ACTIVE]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot approve booking from status "active".');

        app(BookingLifecycleService::class)->approve($booking);
    }
}
