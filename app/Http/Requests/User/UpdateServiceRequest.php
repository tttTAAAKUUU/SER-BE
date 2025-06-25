<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
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
            'completed_at' => 'nullable|date',
            'cancelled_at' => 'nullable|date',
            'rejected_at' => 'nullable|date',
            'accepted_at' => 'nullable|date',
            'start_at' => 'required|date',
            'notes' => 'nullable|string',
        ];
    }
}
