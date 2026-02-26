<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.productId' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],

            // Backward-compatible checkout payload (existing mobile clients)
            'checkout' => ['nullable', 'array'],
            'checkout.name' => ['nullable', 'string', 'max:255'],
            'checkout.shippingAddress' => ['nullable', 'string', 'min:5', 'max:1000'],
            'checkout.billingAddress' => ['nullable', 'string', 'max:1000'],
            'checkout.address' => ['nullable', 'string', 'min:5', 'max:1000'],
            'checkout.city' => ['nullable', 'string', 'max:120'],
            'checkout.phone' => ['nullable', 'string', 'max:50'],
            'checkout.whatsapp' => ['nullable', 'string', 'max:50'],
            'checkout.email' => ['nullable', 'email', 'max:255'],
            'checkout.notes' => ['nullable', 'string', 'max:2000'],
            'checkout.billing_payment_method' => ['nullable', 'string', 'max:100'],

            // Flat payload support for new mobile app clients
            'name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'min:5', 'max:1000'],
            'city' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'shipping_address' => ['nullable', 'array'],
            'shipping_address.address_line_1' => ['nullable', 'string', 'max:1000'],
            'shipping_address.city' => ['nullable', 'string', 'max:120'],
            'billing_address' => ['nullable', 'array'],
            'billing_address.address_line_1' => ['nullable', 'string', 'max:1000'],
            'billing_address.city' => ['nullable', 'string', 'max:120'],
            'billing_payment_method' => ['nullable', 'string', 'max:100'],
        ];
    }
}
