<?php

namespace App\Models\CarWash;

use App\Models\ServiceProvider\ServiceProviderProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasherAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_provider_profile_id',
        'car_wash_addon_id',
        'car_wash_car_type_id',
        'price',
    ];

    protected $casts = ['price' => 'decimal:2'];

    public function serviceProviderProfile(): BelongsTo
    {
        return $this->belongsTo(ServiceProviderProfile::class);
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