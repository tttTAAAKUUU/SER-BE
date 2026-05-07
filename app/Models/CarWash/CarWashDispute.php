<?php

namespace App\Models\CarWash;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarWashDispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_wash_booking_id',
        'user_id',
        'reason',
        'description',
        'status',
        'resolution',
        'refund_amount',
        'resolved_at',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];

    const STATUS_OPEN = 'open';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_REJECTED = 'rejected';

    const RESOLUTION_FULL_REFUND = 'full_refund';
    const RESOLUTION_PARTIAL_REFUND = 'partial_refund';
    const RESOLUTION_NO_REFUND = 'no_refund';
    const RESOLUTION_WASHER_PAID = 'washer_paid';

    public function booking(): BelongsTo
    {
        return $this->belongsTo(CarWashBooking::class, 'car_wash_booking_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}