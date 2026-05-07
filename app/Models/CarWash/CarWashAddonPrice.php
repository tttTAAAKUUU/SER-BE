<?php

namespace App\Models\CarWash;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarWashAddonPrice extends Model
{
    use HasFactory;

    protected $fillable = ['car_wash_addon_id', 'car_wash_car_type_id', 'min_price', 'max_price'];

    protected $casts = [
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
    ];

    public function addon(): BelongsTo
    {
        return $this->belongsTo(CarWashAddon::class);
    }

    public function carType(): BelongsTo
    {
        return $this->belongsTo(CarWashCarType::class);
    }
}