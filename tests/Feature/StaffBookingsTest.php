<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingInspection;
use App\Models\Fine;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class StaffBookingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_bookings_page_renders_bookings_with_related_details(): void
    {
        $staff = User::factory()->staff()->create();
        $customer = User::factory()->customer()->create(['name' => 'Ayu Customer']);
        $item = Item::factory()->create(['name' => 'Camera Kit']);
        $booking = Booking::factory()
            ->approved()
            ->for($customer)
            ->for($item)
            ->create(['total_price' => '125.50']);

        Fine::factory()->unpaid()->for($booking)->create([
            'fine_amount' => '12.00',
            'reason' => 'Late return',
        ]);

        $this->actingAs($staff)
            ->get(route('staff.bookings'))
            ->assertOk()
            ->assertSeeVolt('pages.staff.bookings')
            ->assertSee('Ayu Customer')
            ->assertSee('Camera Kit')
            ->assertSee('125.50')
            ->assertSee('12.00');
    }

    public function test_staff_can_move_bookings_through_operational_statuses(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        $pending = Booking::factory()->pending()->create();
        $approved = Booking::factory()->approved()->create();
        $active = Booking::factory()->active()->create();

        Volt::test('pages.staff.bookings')
            ->call('approve', $pending->id)
            ->assertSet('success', 'Booking approved.')
            ->call('checkout', $approved->id)
            ->assertSet('success', 'Booking checked out.')
            ->set("inspectionStatuses.$active->id", BookingInspection::CONDITION_DAMAGED)
            ->set("inspectionNotes.$active->id", 'Lens cap returned cracked')
            ->call('checkin', $active->id)
            ->assertSet('success', 'Booking checked in.')
            ->assertHasNoErrors();

        $this->assertSame(Booking::STATUS_APPROVED, $pending->fresh()->status);
        $this->assertSame(Booking::STATUS_ACTIVE, $approved->fresh()->status);
        $this->assertSame(Booking::STATUS_COMPLETED, $active->fresh()->status);
        $this->assertDatabaseHas('booking_inspections', [
            'booking_id' => $active->id,
            'condition_status' => BookingInspection::CONDITION_DAMAGED,
            'notes' => 'Lens cap returned cracked',
        ]);
    }

    public function test_staff_cannot_check_in_non_good_condition_without_notes(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        $booking = Booking::factory()->active()->create();

        Volt::test('pages.staff.bookings')
            ->set("inspectionStatuses.$booking->id", BookingInspection::CONDITION_DAMAGED)
            ->call('checkin', $booking->id)
            ->assertHasErrors(["inspectionNotes.$booking->id"]);

        $this->assertSame(Booking::STATUS_ACTIVE, $booking->fresh()->status);
        $this->assertDatabaseMissing('booking_inspections', [
            'booking_id' => $booking->id,
        ]);
    }

    public function test_staff_approval_surfaces_availability_errors(): void
    {
        $this->actingAs(User::factory()->staff()->create());

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

        Volt::test('pages.staff.bookings')
            ->call('approve', $pending->id)
            ->assertSet('error', 'Cannot approve booking because this item is fully booked for the selected dates.');

        $this->assertSame(Booking::STATUS_PENDING, $pending->fresh()->status);
    }

    public function test_staff_can_cancel_pending_and_approved_bookings(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        $pending = Booking::factory()->pending()->create();
        $approved = Booking::factory()->approved()->create();

        Volt::test('pages.staff.bookings')
            ->call('cancel', $pending->id)
            ->assertSet('success', 'Booking cancelled.')
            ->call('cancel', $approved->id)
            ->assertSet('success', 'Booking cancelled.');

        $this->assertSame(Booking::STATUS_CANCELLED, $pending->fresh()->status);
        $this->assertSame(Booking::STATUS_CANCELLED, $approved->fresh()->status);
    }

    public function test_staff_can_issue_fines_for_active_or_completed_bookings(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        $booking = Booking::factory()->active()->create();

        Volt::test('pages.staff.bookings')
            ->set("fineAmounts.$booking->id", '35.25')
            ->set("fineReasons.$booking->id", 'Returned with damaged lens cap')
            ->call('issueFine', $booking->id)
            ->assertSet('success', 'Fine issued.')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('fines', [
            'booking_id' => $booking->id,
            'fine_amount' => '35.25',
            'reason' => 'Returned with damaged lens cap',
            'status' => Fine::STATUS_UNPAID,
        ]);
    }

    public function test_staff_can_settle_unpaid_fines(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        $booking = Booking::factory()->completed()->create();
        $paidFine = Fine::factory()->unpaid()->for($booking)->create();
        $waivedFine = Fine::factory()->unpaid()->for($booking)->create();

        Volt::test('pages.staff.bookings')
            ->call('markFinePaid', $paidFine->id)
            ->assertSet('success', 'Fine marked paid.')
            ->call('waiveFine', $waivedFine->id)
            ->assertSet('success', 'Fine waived.');

        $this->assertSame(Fine::STATUS_PAID, $paidFine->fresh()->status);
        $this->assertSame(Fine::STATUS_WAIVED, $waivedFine->fresh()->status);
    }

    public function test_settled_fines_cannot_be_transitioned_again(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        $fine = Fine::factory()->paid()->create();

        Volt::test('pages.staff.bookings')
            ->call('waiveFine', $fine->id)
            ->assertSet('error', 'Cannot waive fine from status "paid".');

        $this->assertSame(Fine::STATUS_PAID, $fine->fresh()->status);
    }

    public function test_invalid_lifecycle_action_surfaces_short_error_feedback(): void
    {
        $this->actingAs(User::factory()->staff()->create());

        $booking = Booking::factory()->completed()->create();

        Volt::test('pages.staff.bookings')
            ->call('cancel', $booking->id)
            ->assertSet('error', 'Cannot cancel booking from status "completed".');
    }
}
