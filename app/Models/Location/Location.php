<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    /** @use HasFactory<\Database\Factories\Location\LocationFactory> */
    use HasFactory;

    protected $fillable = [
        'street_address',
        'suburb',
        'city',
        'lat',
        'lng',
        'postal_code',
    ];
}
