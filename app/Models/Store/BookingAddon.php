<?php

namespace App\Models\Store;

use App\Models\Business\Store\StoreServiceAddon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAddon extends Model
{
    /** @use HasFactory<\Database\Factories\ServicesFactory> */
    use HasFactory;

    protected $fillable = [
        'store_service_id',
        'booking_id',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function storeServiceAddon(): BelongsTo
    {
        return $this->belongsTo(StoreServiceAddon::class);
    }
}
