<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class RegisterBusinessRequest extends FormRequest
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
            // Parent objects
            'user' => 'required|array',
            'business' => 'required|array',
            'location' => 'required|array',

            // User
            'user.first_name' => 'required|string|max:255',
            'user.last_name' => 'required|string|max:255',
            'user.email' => 'required|string|email|max:255|unique:users,email',
            'user.password' => 'required|string|min:8|confirmed',

            // Business
            'business.name' => 'required|string|max:255|unique:businesses,name',
            'business.description' => 'required|string',
            'business.email' => 'required|email',
            'business.phone' => 'required|string',
            'business.opening_time' => 'required|date_format:H:i',
            'business.closing_time' => 'required|date_format:H:i',

            // Location
            'location.street_address' => 'required|string|max:255',
            'location.suburb' => 'required|string|max:255',
            'location.city' => 'required|string|max:255',
            'location.postal_code' => 'required|digits_between:4,10',
            'location.lat' => 'required|numeric',
            'location.lng' => 'required|numeric',
        ];
    }
}
