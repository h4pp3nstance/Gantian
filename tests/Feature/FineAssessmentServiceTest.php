<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Fine;
use App\Services\FineAssessmentService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class FineAssessmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_issues_an_unpaid_fine_for_active_bookings(): void
    {
        $booking = Booking::factory()->active()->create();

        $fine = app(FineAssessmentService::class)->issue($booking, '25.5', 'Returned with missing charger');

        $this->assertSame($booking->id, $fine->booking_id);
        $this->assertSame('25.50', $fine->fine_amount);
        $this->assertSame('Returned with missing charger', $fine->reason);
        $this->assertSame(Fine::STATUS_UNPAID, $fine->status);
        $this->assertDatabaseHas('fines', [
            'booking_id' => $booking->id,
            'fine_amount' => '25.50',
            'reason' => 'Returned with missing charger',
            'status' => Fine::STATUS_UNPAID,
        ]);
    }

    public function test_it_issues_an_unpaid_fine_for_completed_bookings(): void
    {
        $booking = Booking::factory()->completed()->create();

        $fine = app(FineAssessmentService::class)->issue($booking, 10, 'Late return');

        $this->assertSame(Fine::STATUS_UNPAID, $fine->status);
        $this->assertSame('10.00', $fine->fine_amount);
    }

    public function test_it_rejects_fines_for_ineligible_booking_statuses(): void
    {
        $booking = Booking::factory()->pending()->create();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot issue a fine for booking status "pending".');

        app(FineAssessmentService::class)->issue($booking, '15.00', 'Late return');
    }

    public function test_it_rejects_invalid_amounts_and_blank_reasons(): void
    {
        $booking = Booking::factory()->active()->create();
        $service = app(FineAssessmentService::class);

        try {
            $service->issue($booking, '0.00', 'Late return');
            $this->fail('Expected invalid amount exception.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Fine amount must be greater than zero.', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fine reason is required.');

        $service->issue($booking, '10.00', '   ');
    }

    public function test_it_marks_unpaid_fines_as_paid(): void
    {
        $fine = Fine::factory()->unpaid()->create();

        $updated = app(FineAssessmentService::class)->markPaid($fine);

        $this->assertSame(Fine::STATUS_PAID, $updated->status);
        $this->assertDatabaseHas('fines', [
            'id' => $fine->id,
            'status' => Fine::STATUS_PAID,
        ]);
    }

    public function test_it_waives_unpaid_fines(): void
    {
        $fine = Fine::factory()->unpaid()->create();

        $updated = app(FineAssessmentService::class)->waive($fine);

        $this->assertSame(Fine::STATUS_WAIVED, $updated->status);
        $this->assertDatabaseHas('fines', [
            'id' => $fine->id,
            'status' => Fine::STATUS_WAIVED,
        ]);
    }

    public function test_it_rejects_invalid_fine_status_transitions(): void
    {
        $fine = Fine::factory()->paid()->create();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cannot waive fine from status "paid".');

        app(FineAssessmentService::class)->waive($fine);
    }
}
