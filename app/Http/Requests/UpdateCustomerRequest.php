<?php

namespace App\Http\Requests;

use App\Models\Customer;
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
        $routeCustomer = $this->route('customer');
        $customerId = $routeCustomer instanceof Customer
            ? (int) $routeCustomer->getKey()
            : (is_numeric($routeCustomer) ? (int) $routeCustomer : null);

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
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:1000'],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
