<?php

namespace App\Http\Resources\Service;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicesResource extends JsonResource
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
            'price' => $this->price,
            'duration_minutes' => $this->duration_minutes,
            'is_active' => $this->is_active,
            'description' => $this->description,
            'category' => [
                'name' => $this->serviceCategory->name,
                'description' => $this->serviceCategory->description,
            ],
            'addons' => $this->addons
        ];
    }
}
