<?php

namespace App\Http\Requests\Business\Employee;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreEmployeeRequest extends FormRequest
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
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'phone'      => 'nullable|string|max:20',
            'dob'        => 'nullable|date|before:today',
            'gender'     => 'nullable|in:male,female,other',
            'bio'        => 'nullable|string|max:1000',
        ];
    }
}
