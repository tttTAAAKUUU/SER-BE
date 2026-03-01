<?php

namespace App\Http\Resources\Business;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'client' => $this->user->userProfile,
            'service' => $this->storeService,
            'addons' => $this->whenLoaded('addons', function () {
                return $this->addons->map(function ($addon) {
                    // dd($addon->storeServiceAddon->serviceAddon);
                    return [

                        'name' => $addon->storeServiceAddon->serviceAddon->name,
                        'price' => $addon->storeServiceAddon->serviceAddon->price,
                        'duration' => $addon->storeServiceAddon->serviceAddon->duration_minutes,
                    ];
                });
            }),
            'employee' => $this->employee,

            'time_category' => $this->time_category,
            'time' => $this->time,
            'service_location' => $this->service_location,
        ];
    }
}
