<?php

namespace App\Models\Business\Store;

use App\Models\Service\ServiceAddon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreServiceAddon extends Model
{
    protected $fillable = [
        'store_service_id',
        'service_addon_id',
        'duration_minutes',
        'price',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(StoreService::class);
    }

    public function serviceAddon(): BelongsTo
    {
        return $this->belongsTo(ServiceAddon::class);
    }

}
