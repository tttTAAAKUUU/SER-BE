<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceProviderProfile extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceProviderProfileFactory> */
    use HasFactory;

    protected $table = 'service_provider_profiles';

    protected $fillable = [
        'user_id',
        'address_id',
        'first_name',
        'last_name',
        'phone',
        'dob',
        'gender',
        'bio',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }
}
