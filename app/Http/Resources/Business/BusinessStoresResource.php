<?php

namespace App\Http\Resources\Business;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessStoresResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'store_id' => $this->id,
            'name' =>  $this->name,
            'description' =>  $this->description,
            'phone' =>  $this->phone,
            'email' => $this->email,
            'opening_time' =>  $this->opening_time,
            'closing_time' =>  $this->closing_time,
            'business' => [
                'name' => $this->business->name,
                'description' => $this->business->description,
                'email' => $this->business->email,
                'phone' => $this->business->phone,
                'opening_time' => $this->business->opening_time,
                'closing_time' => $this->business->closing_time,
            ],
            'location' => [
                'street_address' => $this->location->street_address,
                'suburb' => $this->location->suburb,
                'city' => $this->location->city,
                'lat' => $this->location->lat,
                'lng' => $this->location->lng,
                'postal_code' => $this->location->postal_code,
            ]

        ];
    }
}
