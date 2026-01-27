<?php

namespace App\Models\Business;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    protected $fillable = [
        'store_id',
        'first_name',
        'last_name',
        'phone',
        'dob',
        'gender',
        'bio',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
