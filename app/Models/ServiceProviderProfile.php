<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceProviderProfile extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceProviderProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'dob',
        'gender',
        'profile_image',
        'bio',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
