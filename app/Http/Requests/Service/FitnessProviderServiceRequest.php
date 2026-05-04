<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class FitnessProviderServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => 'required|exists:services,id',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
        ];
    }
}
