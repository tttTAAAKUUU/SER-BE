<?php

namespace App\Http\Requests\Business\Store;

use Illuminate\Foundation\Http\FormRequest;

class AddStoreServiceRequest extends FormRequest
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
            // Service Data
            'service' => 'required|array',
            'service.store_id' => 'required|numeric|exists:stores,id',
            'service.service_id' => 'required|numeric|exists:services,id',
            'service.description' => 'nullable|string',
            'service.price' => 'required|numeric|min:0',

            // Addons (Optional)
            'addons' => 'nullable|array',
            'addons.*.service_addon_id' => 'required_with:addons|numeric|exists:service_addons,id',
            'addons.*.price' => 'required_with:addons|numeric|min:0',
            'addons.*.duration_minutes' => 'required',

        ];
    }
}
