<?php

namespace App\Models;

use Database\Factories\BookingInspectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['booking_id', 'inspected_by', 'condition_status', 'notes', 'inspected_at'])]
class BookingInspection extends Model
{
    /** @use HasFactory<BookingInspectionFactory> */
    use HasFactory;

    public const CONDITION_GOOD = 'good';

    public const CONDITION_DAMAGED = 'damaged';

    public const CONDITION_MISSING_ACCESSORY = 'missing_accessory';

    public const CONDITION_LATE_RETURN = 'late_return';

    public const CONDITION_STATUSES = [
        self::CONDITION_GOOD,
        self::CONDITION_DAMAGED,
        self::CONDITION_MISSING_ACCESSORY,
        self::CONDITION_LATE_RETURN,
    ];

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'inspected_at' => 'datetime',
        ];
    }
}
