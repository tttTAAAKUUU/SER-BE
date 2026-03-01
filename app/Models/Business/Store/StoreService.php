<?php

namespace App\Models\Business\Store;

use App\Models\Business\Store;
use App\Models\Service\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreService extends Model
{
    protected $fillable = [
        'store_id',
        'service_id',
        'description',
        'price',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(StoreServiceAddon::class);
    }

}
