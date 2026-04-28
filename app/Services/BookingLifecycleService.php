<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Item;
use DomainException;
use Illuminate\Support\Facades\DB;

class BookingLifecycleService
{
    public function __construct(
        private readonly BookingAvailabilityService $availabilityService,
    ) {}

    public function approve(Booking $booking): Booking
    {
        return $this->transition(
            $booking,
            [Booking::STATUS_PENDING],
            Booking::STATUS_APPROVED,
            'approve',
            function (Booking $lockedBooking): void {
                $item = Item::query()
                    ->whereKey($lockedBooking->item_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedBooking->setRelation('item', $item);

                if (! $this->availabilityService->hasAvailability(
                    $lockedBooking->item,
                    $lockedBooking->start_date,
                    $lockedBooking->end_date,
                    $lockedBooking
                )) {
                    throw new DomainException('Cannot approve booking because this item is fully booked for the selected dates.');
                }
            }
        );
    }

    public function checkout(Booking $booking): Booking
    {
        return $this->transition($booking, [Booking::STATUS_APPROVED], Booking::STATUS_ACTIVE, 'checkout');
    }

    public function checkin(Booking $booking): Booking
    {
        return $this->transition($booking, [Booking::STATUS_ACTIVE], Booking::STATUS_COMPLETED, 'check in');
    }

    public function cancel(Booking $booking): Booking
    {
        return $this->transition(
            $booking,
            [Booking::STATUS_PENDING, Booking::STATUS_APPROVED],
            Booking::STATUS_CANCELLED,
            'cancel'
        );
    }

    /**
     * @param  array<int, string>  $allowedStatuses
     */
    private function transition(
        Booking $booking,
        array $allowedStatuses,
        string $nextStatus,
        string $action,
        ?\Closure $beforeUpdate = null
    ): Booking {
        return DB::transaction(function () use ($booking, $allowedStatuses, $nextStatus, $action, $beforeUpdate): Booking {
            $lockedBooking = Booking::query()
                ->with('item')
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($lockedBooking->status, $allowedStatuses, true)) {
                throw new DomainException(sprintf(
                    'Cannot %s booking from status "%s".',
                    $action,
                    $lockedBooking->status
                ));
            }

            if ($beforeUpdate !== null) {
                $beforeUpdate($lockedBooking);
            }

            $updated = Booking::query()
                ->whereKey($lockedBooking->id)
                ->whereIn('status', $allowedStatuses)
                ->update(['status' => $nextStatus]);

            if ($updated !== 1) {
                $lockedBooking->refresh();

                throw new DomainException(sprintf(
                    'Cannot %s booking from status "%s".',
                    $action,
                    $lockedBooking->status
                ));
            }

            return $lockedBooking->refresh();
        });
    }
}
