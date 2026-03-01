<?php

namespace App\Http\Requests\Business\Employee;

use Illuminate\Foundation\Http\FormRequest;

class AddStoreEmployeeRequest extends FormRequest
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
            'store_id'   => 'required|integer|exists:stores,id',
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'phone'      => 'required|string|max:20',
            'dob'        => 'required|date|before:today',
            'gender'     => 'required|in:male,female,other',
            'bio'        => 'nullable|string|max:1000',
        ];
    }
}
