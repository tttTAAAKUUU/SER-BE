<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'business' => [
                'name' => $this->business->name,
                'description' => $this->business->description,
                'email' => $this->business->email,
                'phone' => $this->business->phone,
                'opening_time' => $this->business->opening_time,
                'closing_time' => $this->business->closing_time,
                'location' => [
                    'street_address' => $this->business->location->street_address,
                    'suburb' => $this->business->location->suburb,
                    'city' => $this->business->location->city,
                    'lat' => $this->business->location->lat,
                    'lng' => $this->business->location->lng,
                    'postal_code' => $this->business->location->postal_code,
                ],
            ],
        ];
    }
}
