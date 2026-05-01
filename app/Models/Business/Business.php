<?php

namespace App\Models\Business;

use App\Models\Location\Location;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Business extends Model
{
    /** @use HasFactory<\Database\Factories\Business\BusinessFactory> */
    use HasFactory;

    protected $fillable = [
        'location_id',
        'user_id',
        'name',
        'description',
        'email',
        'phone',
        'opening_time',
        'closing_time',
        'profile_image',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User\User::class, 'user_id');
    }

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class, 'business_id', 'id');
    }
}
