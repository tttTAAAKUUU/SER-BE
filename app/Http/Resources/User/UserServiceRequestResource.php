<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserServiceRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'start_at' => $this->start_at,
            'notes' => $this->providerService->description,
            'status' => $this->status,
            'serviceProvider' => [
                'first_name' => $this->providerService->serviceProviderProfile->first_name,
                'last_name' => $this->providerService->serviceProviderProfile->last_name,
                'phone' => $this->providerService->serviceProviderProfile->phone,
            ],
            'location' => [
                    'street_address' => $this->location->street_address,
                    'suburb' => $this->location->suburb,
                    'city' => $this->location->city,
                    'lat' => $this->location->lat,
                    'lng' => $this->location->lng,
                    'postal_code' => $this->location->postal_code,
                ],
            'service' => [
                'name' => $this->providerService->service->name,
                'description' => $this->providerService->service->description,
                'price' => $this->providerService->price,
            ],
        ];
    }
}
