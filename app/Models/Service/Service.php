<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    /** @use HasFactory<\Database\Factories\Service\ServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'service_category_id',
        'name',
        'description',
        'price',
        'duration_minutes',
        'is_active',
        'room_tiers',
        'break_duration_minutes',
        'package_type',
        'bathroom_cap',
    ];

    protected $casts = [
        'room_tiers' => 'array',
        'break_duration_minutes' => 'integer',
        'bathroom_cap' => 'integer',
        'is_active' => 'boolean',
    ];

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(ServiceAddon::class, 'service_id', 'id');
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    public function hasAddons(): bool
    {
        return $this->addons()->exists();
    }
}
