<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider_service_id',
        'location_id',
        'start_at',
        'completed_at',
        'cancelled_at',
        'rejected_at',
        'accepted_at',
        'notes',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function providerService()
    {
        return $this->belongsTo(ProviderService::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
