<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Fine;
use DomainException;
use InvalidArgumentException;

class FineAssessmentService
{
    public function issue(Booking $booking, int|float|string $amount, string $reason): Fine
    {
        if (! in_array($booking->status, [Booking::STATUS_ACTIVE, Booking::STATUS_COMPLETED], true)) {
            throw new DomainException(sprintf(
                'Cannot issue a fine for booking status "%s".',
                $booking->status
            ));
        }

        $fineAmount = $this->normalizeAmount($amount);
        $fineReason = trim($reason);

        if ($fineReason === '') {
            throw new InvalidArgumentException('Fine reason is required.');
        }

        return $booking->fines()->create([
            'fine_amount' => $fineAmount,
            'reason' => $fineReason,
            'status' => Fine::STATUS_UNPAID,
        ]);
    }

    public function markPaid(Fine $fine): Fine
    {
        return $this->transition($fine, Fine::STATUS_PAID, 'mark paid');
    }

    public function waive(Fine $fine): Fine
    {
        return $this->transition($fine, Fine::STATUS_WAIVED, 'waive');
    }

    private function normalizeAmount(int|float|string $amount): string
    {
        $normalized = trim((string) $amount);

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $normalized)) {
            throw new InvalidArgumentException('Fine amount must be a valid positive decimal amount.');
        }

        [$whole, $fraction] = array_pad(explode('.', $normalized, 2), 2, '0');
        $cents = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');

        if ($cents <= 0) {
            throw new InvalidArgumentException('Fine amount must be greater than zero.');
        }

        return number_format($cents / 100, 2, '.', '');
    }

    private function transition(Fine $fine, string $nextStatus, string $action): Fine
    {
        if ($fine->status !== Fine::STATUS_UNPAID) {
            throw new DomainException(sprintf(
                'Cannot %s fine from status "%s".',
                $action,
                $fine->status
            ));
        }

        $fine->forceFill(['status' => $nextStatus])->save();

        return $fine->refresh();
    }
}
