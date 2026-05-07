<?php

namespace App\Models\CarWash;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CarWashPackagePrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_wash_package_id',
        'car_wash_car_type_id',
        'washer_tier',
        'min_price',
        'max_price',
    ];

    protected $casts = [
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(CarWashPackage::class);
    }

    public function carType(): BelongsTo
    {
        return $this->belongsTo(CarWashCarType::class);
    }

    public function isFloorOnly(): bool
    {
        return $this->max_price === null;
    }
}