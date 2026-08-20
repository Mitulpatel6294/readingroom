<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seat_number' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:200'],
            'phone' => ['required', 'string', 'regex:/^\d{10}$/'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'join_date' => ['required', 'date'],
            'due_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone number must be a 10-digit number',
        ];
    }
}
