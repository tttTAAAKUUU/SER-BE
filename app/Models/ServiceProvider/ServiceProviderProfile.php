<?php

namespace App\Models\ServiceProvider;

use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\CarWash\WasherAddon;
use App\Models\CarWash\WasherAvailability;
use App\Models\CarWash\WasherEquipmentChecklist;
use App\Models\CarWash\WasherPackage;
use App\Models\CarWash\WasherTierApplication;
use App\Models\ServiceProvider\ProviderService;
use App\Models\Service\Service;
use App\Models\User\User;

use Illuminate\Database\Eloquent\Model;


class ServiceProviderProfile extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceProvider\ServiceProviderProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'dob',
        'gender',
        'profile_image',
        'bio',
        'washer_tier',
        'washer_tier_approved_at',
        'washer_equipment_verified',
        'washer_next_verification_at',
    ];

    protected $casts = [
        'washer_tier_approved_at' => 'datetime',
        'washer_equipment_verified' => 'boolean',
        'washer_next_verification_at' => 'datetime',
    ];

    public function isProTech(): bool
    {
        return $this->washer_tier === 'pro_tech' && $this->washer_equipment_verified;
    }

    public function isEssential(): bool
    {
        return $this->washer_tier === 'essential';
    }

    public function isWasherTierPending(): bool
    {
        return $this->washer_tier !== null && !$this->washer_equipment_verified;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function services(): HasManyThrough
    {
        return $this->hasManyThrough(Service::class, ProviderService::class, 'service_provider_profile_id', 'service_id');
    }

    public function equipmentChecklist(): HasOne
    {
        return $this->hasOne(WasherEquipmentChecklist::class);
    }

    public function washerPackages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WasherPackage::class);
    }

    public function washerAddons(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WasherAddon::class);
    }

    public function tierApplications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WasherTierApplication::class);
    }

    public function availabilities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WasherAvailability::class);
    }
}
