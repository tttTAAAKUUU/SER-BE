<?php

namespace App\Models\Store;

use App\Models\Business\Store\Employee;
use App\Models\Business\Store\StoreService;
use App\Models\Business\Store\StoreServiceAddon;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'store_service_id',
        'employee_id',
        'time_category',
        'time',
        'service_location'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function storeService(): BelongsTo
    {
        return $this->belongsTo(StoreService::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(BookingAddon::class);
    }
}
