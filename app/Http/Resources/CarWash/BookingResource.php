<?php

namespace App\Http\Resources\CarWash;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at?->toDateTimeString(),
            'client_address' => $this->client_address,
            'total_price' => (float) $this->total_price,
            'ser_cut' => (float) $this->ser_cut,
            'washer_payout' => (float) $this->washer_payout,
            'package' => $this->package ? [
                'id' => $this->package->id,
                'name' => $this->package->name,
            ] : null,
            'car_type' => $this->carType ? [
                'id' => $this->carType->id,
                'name' => $this->carType->name,
                'slug' => $this->carType->slug,
            ] : null,
            'washer' => $this->serviceProvider ? [
                'id' => $this->serviceProvider->id,
                'name' => $this->serviceProvider->first_name . ' ' . $this->serviceProvider->last_name,
                'tier' => $this->washer_tier,
            ] : null,
            'addons' => $this->addons?->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->addon?->name,
                'price' => (float) $a->price,
            ]) ?? [],
            'paid_at' => $this->paid_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
            'dispute_window_closes_at' => $this->dispute_window_closes_at?->toDateTimeString(),
        ];
    }
}