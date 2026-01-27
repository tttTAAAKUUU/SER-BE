<?php

namespace App\Models\Business;

use App\Models\Location\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Business extends Model
{
    protected $fillable = [
        'location_id',
        'user_id',
        'name',
        'description',
        'email',
        'phone',
        'opening_time',
        'closing_time',
    ];

    public function location(): BelongsTo {

        return $this->belongsTo(Location::class);
    }
}
