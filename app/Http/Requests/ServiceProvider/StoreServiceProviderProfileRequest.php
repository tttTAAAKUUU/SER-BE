<?php

namespace App\Http\Requests\ServiceProvider;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceProviderProfileRequest extends FormRequest
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
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (\App\Models\User\User::whereRaw('LOWER(email) = ?', [strtolower($value)])->exists()) {
                        $fail('The email has already been taken.');
                    }
                },
            ],
            'password' => 'required|string|min:8|confirmed',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'dob' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'bio' => 'nullable|string',
            'service_area' => 'nullable|string|max:255',
            'service_category_ids' => 'nullable|array',
            'service_category_ids.*' => 'integer|exists:service_categories,id',
        ];
    }
}
