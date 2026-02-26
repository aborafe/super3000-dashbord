<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var \App\Models\Customer|null $customer */
        $customer = $this->user();

        $customerId = $customer ? (int) $customer->getKey() : null;

        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(Customer::class, 'email')->ignore($customerId),
            ],
            'phone' => [
                'required',
                'string',
                'min:8',
                'max:255',
                Rule::unique(Customer::class, 'phone')->ignore($customerId),
            ],
            'whatsapp' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'address' => ['required', 'string', 'max:65535'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'email' => is_string($email) ? strtolower(trim($email)) : $email,
            'phone' => is_string($this->input('phone')) ? trim($this->input('phone')) : $this->input('phone'),
            'whatsapp' => is_string($this->input('whatsapp')) ? trim($this->input('whatsapp')) : $this->input('whatsapp'),
            'city' => is_string($this->input('city')) ? trim($this->input('city')) : $this->input('city'),
            'address' => is_string($this->input('address')) ? trim($this->input('address')) : $this->input('address'),
        ]);
    }
}
