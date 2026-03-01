<?php

namespace App\Http\Requests\Business\Store;

use Illuminate\Foundation\Http\FormRequest;

class AddBusinessStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'location' => 'required|array',
            'store' => 'required|array',

            'store.name' => 'required|string|max:255',
            'store.description' => 'required|string',
            'store.email' => 'required|email',
            'store.phone' => 'required|string',
            'store.opening_time' => 'required|date_format:H:i',
            'store.closing_time' => 'required|date_format:H:i',
            'location.street_address' => 'required|string|max:255',
            'location.suburb' => 'required|string|max:255',
            'location.city' => 'required|string|max:255',
            'location.postal_code' => 'required|integer',
            'location.lat' => 'required|numeric|between:-90,90',
            'location.lng' => 'required|numeric|between:-180,180',
        ];
    }
}
