<?php

namespace App\Models\Cleaning;

use App\Models\User\User;
use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\Service\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'provider_id',
        'service_id',
        'package_type',
        'room_tier',
        'bathroom_count',
        'scheduling_mode',
        'recurring_days',
        'start_date',
        'addon_config',
        'distance_km',
        'service_location',
        'is_active',
    ];

    protected $casts = [
        'recurring_days' => 'array',
        'addon_config' => 'array',
        'bathroom_count' => 'integer',
        'distance_km' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(ServiceProviderProfile::class, 'provider_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(RecurringSession::class, 'template_id')->orderBy('scheduled_date');
    }
}