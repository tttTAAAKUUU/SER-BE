<?php

namespace App\Models\CarWash;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarWashBookingAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_wash_booking_id',
        'car_wash_addon_id',
        'car_wash_car_type_id',
        'price',
    ];

    protected $casts = ['price' => 'decimal:2'];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(CarWashBooking::class);
    }

    public function addon(): BelongsTo
    {
        return $this->belongsTo(CarWashAddon::class);
    }

    public function carType(): BelongsTo
    {
        return $this->belongsTo(CarWashCarType::class);
    }
}