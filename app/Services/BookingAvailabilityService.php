<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Item;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeInterface;

class BookingAvailabilityService
{
    public const RESERVING_STATUSES = [
        Booking::STATUS_APPROVED,
        Booking::STATUS_ACTIVE,
    ];

    public const DUPLICATE_STATUSES = [
        Booking::STATUS_PENDING,
        Booking::STATUS_APPROVED,
    ];

    public function hasBaseAvailability(Item $item): bool
    {
        return $item->status === Item::STATUS_AVAILABLE && $item->stock > 0;
    }

    public function hasAvailability(
        Item $item,
        DateTimeInterface|string $startDate,
        DateTimeInterface|string $endDate,
        ?Booking $excludingBooking = null
    ): bool {
        if (! $this->hasBaseAvailability($item)) {
            return false;
        }

        return $this->availableUnits($item, $startDate, $endDate, $excludingBooking) > 0;
    }

    public function availableUnits(
        Item $item,
        DateTimeInterface|string $startDate,
        DateTimeInterface|string $endDate,
        ?Booking $excludingBooking = null
    ): int {
        return max(0, $item->stock - $this->reservedUnits($item, $startDate, $endDate, $excludingBooking));
    }

    public function reservedUnits(
        Item $item,
        DateTimeInterface|string $startDate,
        DateTimeInterface|string $endDate,
        ?Booking $excludingBooking = null
    ): int {
        $start = $this->dateOnly($startDate);
        $end = $this->dateOnly($endDate);

        return Booking::query()
            ->where('item_id', $item->id)
            ->whereIn('status', self::RESERVING_STATUSES)
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->when($excludingBooking !== null, fn ($query) => $query->whereKeyNot($excludingBooking->id))
            ->count();
    }

    public function hasDuplicateOpenRequest(
        User $user,
        Item $item,
        DateTimeInterface|string $startDate,
        DateTimeInterface|string $endDate
    ): bool {
        return Booking::query()
            ->whereBelongsTo($user)
            ->whereBelongsTo($item)
            ->whereIn('status', self::DUPLICATE_STATUSES)
            ->whereDate('start_date', $this->dateOnly($startDate))
            ->whereDate('end_date', $this->dateOnly($endDate))
            ->exists();
    }

    private function dateOnly(DateTimeInterface|string $date): CarbonImmutable
    {
        if ($date instanceof DateTimeInterface) {
            return CarbonImmutable::instance($date)->startOfDay();
        }

        return CarbonImmutable::parse($date)->startOfDay();
    }
}
