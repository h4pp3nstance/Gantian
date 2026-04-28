<?php

namespace Tests\Unit;

use App\Models\Item;
use App\Services\BookingPricingService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BookingPricingServiceTest extends TestCase
{
    public function test_it_counts_inclusive_rental_days(): void
    {
        $service = new BookingPricingService;

        $this->assertSame(1, $service->inclusiveDays('2026-05-01', '2026-05-01'));
        $this->assertSame(3, $service->inclusiveDays('2026-05-01', '2026-05-03'));
    }

    public function test_it_rejects_an_end_date_before_the_start_date(): void
    {
        $service = new BookingPricingService;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('End date must be on or after start date.');

        $service->inclusiveDays('2026-05-03', '2026-05-01');
    }

    public function test_it_calculates_total_price_as_a_decimal_string(): void
    {
        $service = new BookingPricingService;
        $item = new Item(['price_per_day' => '125.50']);

        $this->assertSame('376.50', $service->totalPrice($item, '2026-05-01', '2026-05-03'));
    }
}
