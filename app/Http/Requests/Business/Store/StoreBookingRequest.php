<?php

namespace App\Http\Requests\Business\Store;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
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
            'store_service_id' => 'required|numeric',
            'employee_id' => 'required|numeric',
            'time_category' => 'required|string',
            'time' => 'required|date',
            'service_location' => 'required|string',

            'addons' => 'nullable|array',
            'addons.*.store_service_addon_id' => 'required_with:addons|numeric|exists:store_service_addons,id',
        ];
    }
}
