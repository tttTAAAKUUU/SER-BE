<?php

namespace App\Http\Requests\Business\Store;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreServiceRequest extends FormRequest
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
            'store_id' => 'nullable|numeric|exists:stores,id',
            'service_id' => 'nullable|numeric|exists:services,id',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
        ];
    }
}
