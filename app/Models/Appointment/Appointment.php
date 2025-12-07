<?php

namespace App\Models\Appointment;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status',
        'price',
    ];
}
