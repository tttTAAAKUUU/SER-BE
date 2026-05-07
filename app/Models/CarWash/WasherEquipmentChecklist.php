<?php

namespace App\Models\CarWash;

use App\Models\ServiceProvider\ServiceProviderProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WasherEquipmentChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_provider_profile_id',
        'two_buckets',
        'microfiber_mitts_cloths',
        'ph_neutral_shampoo',
        'wheel_brush',
        'manual_vacuum',
        'tyre_polish',
        'car_air_freshener',
        'pressure_washer',
        'snow_foam_cannon',
        'wet_dry_vacuum',
        'da_polisher',
        'steam_cleaner',
        'clay_bar_kit',
        'microfiber_drying_towels',
    ];

    protected $casts = [
        'two_buckets' => 'boolean',
        'microfiber_mitts_cloths' => 'boolean',
        'ph_neutral_shampoo' => 'boolean',
        'wheel_brush' => 'boolean',
        'manual_vacuum' => 'boolean',
        'tyre_polish' => 'boolean',
        'car_air_freshener' => 'boolean',
        'pressure_washer' => 'boolean',
        'snow_foam_cannon' => 'boolean',
        'wet_dry_vacuum' => 'boolean',
        'da_polisher' => 'boolean',
        'steam_cleaner' => 'boolean',
        'clay_bar_kit' => 'boolean',
        'microfiber_drying_towels' => 'boolean',
    ];

    public function serviceProviderProfile()
    {
        return $this->belongsTo(ServiceProviderProfile::class);
    }

    public function applications()
    {
        return $this->hasMany(WasherTierApplication::class);
    }

    public function hasAllProTechEquipment(): bool
    {
        return $this->pressure_washer
            && $this->snow_foam_cannon
            && $this->wet_dry_vacuum
            && $this->da_polisher
            && $this->steam_cleaner
            && $this->clay_bar_kit
            && $this->microfiber_drying_towels;
    }

    public function hasAllEssentialEquipment(): bool
    {
        return $this->two_buckets
            && $this->microfiber_mitts_cloths
            && $this->ph_neutral_shampoo
            && $this->wheel_brush
            && $this->manual_vacuum
            && $this->tyre_polish
            && $this->car_air_freshener;
    }
}