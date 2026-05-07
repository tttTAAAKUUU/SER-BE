<?php

namespace App\Models\CarWash;

use App\Models\Administrator\AdministratorProfile;
use App\Models\ServiceProvider\ServiceProviderProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasherTierApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_provider_profile_id',
        'admin_id',
        'washer_equipment_checklist_id',
        'requested_tier',
        'status',
        'admin_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function serviceProviderProfile(): BelongsTo
    {
        return $this->belongsTo(ServiceProviderProfile::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AdministratorProfile::class, 'admin_id');
    }

    public function equipmentChecklist(): BelongsTo
    {
        return $this->belongsTo(WasherEquipmentChecklist::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}