<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
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
            'first_name' => $this->userProfile->first_name,
            'last_name' => $this->userProfile->last_name,
            'phone' => $this->userProfile->phone,
            'dob' => $this->userProfile->dob,
            'bio' => $this->userProfile->bio,
            'gender' => $this->userProfile->gender,
            'address' => [
                'street_address' => $this->userProfile->address->street_address,
                'suburb' => $this->userProfile->address->suburb,
                'city' => $this->userProfile->address->city,
                'lat' => $this->userProfile->address->lat,
                'lng' => $this->userProfile->address->lng,
                'postal_code' => $this->userProfile->address->postal_code,
            ],
        ];
    }
}
