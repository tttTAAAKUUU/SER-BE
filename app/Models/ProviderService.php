<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderService extends Model
{
    /** @use HasFactory<\Database\Factories\ProviderServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'service_provider_id',
        'service_id',
        'price',
        'description',
    ];

    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProviderProfile::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
