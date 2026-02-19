<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('customers.update');
    }

    public function rules(): array
    {
        $customerId = $this->route('customer')?->id ?? null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')
                    ->ignore($customerId)
                    ->whereNull('deleted_at'),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('customers', 'phone')
                    ->ignore($customerId)
                    ->whereNull('deleted_at'),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
