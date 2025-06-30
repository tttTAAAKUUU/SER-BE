<?php

namespace App\Http\Resources\ServiceProvider;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderServiceRequestResource extends JsonResource
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
            'user' => [
                'first_name' => $this->user->userProfile->first_name,
                'last_name' => $this->user->userProfile->last_name,
                'phone' => $this->user->userProfile->phone,
                'address' => [
                    'street_address' => $this->user->userProfile->address->street_address,
                    'suburb' => $this->user->userProfile->address->suburb,
                    'city' => $this->user->userProfile->address->city,
                    'lat' => $this->user->userProfile->address->lat,
                    'lng' => $this->user->userProfile->address->lng,
                    'postal_code' => $this->user->userProfile->address->postal_code,
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
