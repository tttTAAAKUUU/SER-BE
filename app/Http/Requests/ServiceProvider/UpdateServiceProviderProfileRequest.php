<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceProviderProfileRequest extends FormRequest
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
        $rules = [
            'first_name' => 'string|max:255',
            'last_name' => 'string|max:255',
            'phone' => 'string|max:255',
            'dob' => 'date',
            'gender' => 'in:male,female,other',
            'profile_image' => 'nullable|image',
            'bio' => 'string',
        ];

        if ($this->isMethod('put')) {
            $rules = [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone' => 'required|string|max:255',
                'dob' => 'required|date',
                'gender' => 'required|in:male,female,other',
                'profile_image' => 'nullable|image',
                'bio' => 'nullable|string',
            ];
        }

        return $rules;
    }
}
