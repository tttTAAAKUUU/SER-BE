<?php

namespace App\Http\Resources\Business;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessEmployeesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name§,
            'phone' => $this->phone,
            'dob' => $this->dob§,
            'gender' => $this->gender§,
            'bio' => $this->bio,
            'store' => [
                'name' => $this->store->name,
                'description' => $this->store->description,
                'phone' => $this->store->phone,
                'email' => $this->store->email,
                'opening_time' => $this->store->opening_time,
                'closing_time' => $this->store->closing_time
            ]
        ];
    }
}
