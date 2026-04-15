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
        $providerService = $this->providerService;
        $location = $this->location;
        $serviceData = null;
        $serviceProviderData = null;

        if ($providerService && $providerService->relationLoaded('service')) {
            $service = $providerService->service;
            $serviceData = [
                'name' => $service->name ?? null,
                'description' => $service->description ?? null,
                'price' => $providerService->price ?? null,
            ];
        }

        if ($providerService && $providerService->relationLoaded('serviceProviderProfile')) {
            $spProfile = $providerService->serviceProviderProfile;
            $serviceProviderData = [
                'first_name' => $spProfile->first_name ?? null,
                'last_name' => $spProfile->last_name ?? null,
                'phone' => $spProfile->phone ?? null,
            ];
        }

        return [
            'id' => $this->id,
            'starts_at' => $this->starts_at,
            'notes' => $this->notes,
            'status' => $this->status,
            'serviceProvider' => $serviceProviderData,
            'location' => $location ? [
                'street_address' => $location->street_address ?? null,
                'suburb' => $location->suburb ?? null,
                'city' => $location->city ?? null,
                'lat' => $location->lat ?? null,
                'lng' => $location->lng ?? null,
                'postal_code' => $location->postal_code ?? null,
            ] : null,
            'service' => $serviceData,
        ];
    }
}
