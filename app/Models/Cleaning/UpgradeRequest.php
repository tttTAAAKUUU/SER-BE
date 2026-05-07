<?php

namespace App\Models\Cleaning;

use App\Models\Store\Booking;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpgradeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'provider_id',
        'client_id',
        'status',
        'price_difference',
        'requested_at',
        'responded_at',
        'paid_at',
    ];

    protected $casts = [
        'price_difference' => 'decimal:2',
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isDeclined(): bool
    {
        return $this->status === 'declined';
    }
}