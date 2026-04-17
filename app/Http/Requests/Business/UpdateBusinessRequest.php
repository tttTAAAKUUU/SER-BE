<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessRequest extends FormRequest
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
        if ($this->isMethod('put')) {
            return [
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'email' => 'required|email',
                'phone' => 'required|string',
                'opening_time' => 'required|date_format:H:i',
                'closing_time' => 'required|date_format:H:i',
            ];
        }

        return [
            'name' => 'string|max:255',
            'description' => 'string',
            'email' => 'email',
            'phone' => 'string',
            'opening_time' => 'date_format:H:i',
            'closing_time' => 'date_format:H:i',
        ];
    }
}
