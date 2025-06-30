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
                'address' => [
                    'street_address' => $this->providerService->serviceProviderProfile->address->street_address,
                    'suburb' => $this->providerService->serviceProviderProfile->address->suburb,
                    'city' => $this->providerService->serviceProviderProfile->address->city,
                    'lat' => $this->providerService->serviceProviderProfile->address->lat,
                    'lng' => $this->providerService->serviceProviderProfile->address->lng,
                    'postal_code' => $this->providerService->serviceProviderProfile->address->postal_code,
                ],
            ],
            'service' => [
                'name' => $this->providerService->service->name,
                'description' => $this->providerService->service->description,
                'price' => $this->providerService->price,
            ],
        ];
    }
}
