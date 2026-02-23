<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['nullable', 'email', 'required_without_all:identifier,phone'],
            'phone' => ['nullable', 'string', 'max:25', 'required_without_all:identifier,email'],
            'identifier' => ['nullable', 'string', 'max:255', 'required_without_all:email,phone'],
            'password' => ['required', 'string', 'min:8'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
