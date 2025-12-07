<?php

namespace App\Models;

use App\Models\Service\Service;
use App\Models\ServiceProvider\ProviderService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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

    public function services(): HasManyThrough
    {
        return $this->hasManyThrough(Service::class, ProviderService::class, 'service_id', 'provider_service_id');
    }
}
