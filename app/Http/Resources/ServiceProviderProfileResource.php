<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceProviderProfileResource extends JsonResource
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
            'first_name' => $this->serviceProviderProfile->first_name,
            'last_name' => $this->serviceProviderProfile->last_name,
            'phone' => $this->serviceProviderProfile->phone,
            'dob' => $this->serviceProviderProfile->dob,
            'bio' => $this->serviceProviderProfile->bio,
            'gender' => $this->serviceProviderProfile->gender,
        ];
    }
}
