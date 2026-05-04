<?php

namespace App\Models\User;

use App\Models\Location\Location;
use App\Models\Service\ServiceAddon;
use App\Models\ServiceProvider\ProviderService;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    /** @use HasFactory<\Database\Factories\User\ServiceRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider_service_id',
        'location_id',
        'starts_at',
        'completed_at',
        'cancelled_at',
        'rejected_at',
        'accepted_at',
        'notes',
        'status',
        'service_pillar',
        'capacity',
        'workout_category',
        'equipment_required',
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

    public function calculateFitnessTotal($service): float
    {
        $basePrice = (float) $service->price;

        if (! $this->equipment_required) {
            return $basePrice;
        }

        // Look up the workout type service and its equipment addon
        $workoutTypeMap = [
            'functional' => 'Functional Flow / HIIT',
            'strength' => 'Strength Equipment',
            'reformer' => 'Reformer Equipment',
        ];

        $addonName = $workoutTypeMap[$this->workout_category] ?? null;
        if (! $addonName) {
            return $basePrice;
        }

        $addon = ServiceAddon::where('name', $addonName)->first();
        if (! $addon) {
            return $basePrice;
        }

        return $basePrice + ((float) $addon->price * $this->capacity);
    }

    public function getFitnessPriceBreakdown($service): array
    {
        $baseTotal = $this->calculateFitnessTotal($service);
        $platformFee = $baseTotal * 0.15;
        $displayTotal = $baseTotal + $platformFee;

        return [
            'base_total' => $baseTotal,
            'platform_fee' => round($platformFee, 2),
            'display_total' => round($displayTotal, 2),
        ];
    }
}
