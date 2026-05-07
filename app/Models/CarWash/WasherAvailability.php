<?php

namespace App\Models\CarWash;

use App\Models\ServiceProvider\ServiceProviderProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasherAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_provider_profile_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function serviceProviderProfile(): BelongsTo
    {
        return $this->belongsTo(ServiceProviderProfile::class);
    }

    public function isAvailableAt(string $time): bool
    {
        return $this->is_active && $time >= $this->start_time && $time <= $this->end_time;
    }
}