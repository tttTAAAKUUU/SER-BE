<?php

namespace App\Models\Business\Store;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Amenities extends Model
{
    protected $fillable = ['name'];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
