<?php

namespace App\Models\CarWash;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarWashAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_wash_service_category_id',
        'name',
        'description',
        'washer_tier',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CarWashServiceCategory::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(CarWashAddonPrice::class);
    }

    public function priceFor(int $carTypeId): ?CarWashAddonPrice
    {
        return $this->prices()->where('car_wash_car_type_id', $carTypeId)->first();
    }
}