<?php

namespace App\Models\Business;

use App\Models\Location\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Store extends Model
{
    protected $fillable = [
        'location_id',
        'business_id',
        'name',
        'description',
        'phone',
        'email',
        'opening_time',
        'closing_time',
    ];

    public function business(): BelongsTo
    {

        return $this->belongsTo(Business::class);
    }

    public function location(): BelongsTo
    {

        return $this->belongsTo(Location::class);
    }
}
