<?php

namespace App\Models\ServiceProvider;

use App\Models\Service\Service;
use App\Models\ServiceProviderProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderService extends Model
{
    /** @use HasFactory<\Database\Factories\ProviderServiceFactory> */
    use HasFactory;

    protected $table = 'provider_services';

    protected $fillable = [
        'service_provider_profile_id',
        'service_id',
        'price',
        'description',
    ];

    public function serviceProviderProfile()
    {
        return $this->belongsTo(ServiceProviderProfile::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
