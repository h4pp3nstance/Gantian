<?php

namespace App\Services;

use App\Models\Item;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

class BookingPricingService
{
    public function inclusiveDays(DateTimeInterface|string $startDate, DateTimeInterface|string $endDate): int
    {
        $start = $this->dateOnly($startDate);
        $end = $this->dateOnly($endDate);

        if ($end->lessThan($start)) {
            throw new InvalidArgumentException('End date must be on or after start date.');
        }

        return (int) $start->diffInDays($end) + 1;
    }

    public function totalPrice(Item $item, DateTimeInterface|string $startDate, DateTimeInterface|string $endDate): string
    {
        $days = $this->inclusiveDays($startDate, $endDate);
        $totalCents = $this->moneyToCents($item->price_per_day) * $days;

        return $this->centsToMoney($totalCents);
    }

    private function dateOnly(DateTimeInterface|string $date): CarbonImmutable
    {
        if ($date instanceof DateTimeInterface) {
            return CarbonImmutable::instance($date)->startOfDay();
        }

        return CarbonImmutable::parse($date)->startOfDay();
    }

    private function moneyToCents(int|float|string $amount): int
    {
        $normalized = trim((string) $amount);

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $normalized)) {
            throw new InvalidArgumentException('Item price per day must be a valid non-negative decimal amount.');
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function centsToMoney(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
