<?php

namespace App\Models\CarWash;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarWashPackage extends Model
{
    use HasFactory;

    protected $fillable = ['car_wash_service_category_id', 'name', 'description'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(CarWashServiceCategory::class, 'car_wash_service_category_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(CarWashPackagePrice::class);
    }

    public function priceFor(string $tier, int $carTypeId): ?CarWashPackagePrice
    {
        return $this->prices()->where('washer_tier', $tier)->where('car_wash_car_type_id', $carTypeId)->first();
    }
}