<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class FitnessServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => 'required|exists:services,id',
            'service_pillar' => 'required|in:mobile,virtual,group',
            'capacity' => 'required|integer|min:1|max:3',
            'workout_category' => 'required|in:functional,strength,reformer',
            'equipment_required' => 'required|boolean',
            'location_id' => 'required|exists:locations,id',
            'starts_at' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
