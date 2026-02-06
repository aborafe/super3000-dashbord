<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('orders.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'partner_id' => ['required', 'exists:partners,id'],
            'status' => ['required', 'in:' . implode(',', [
                Order::STATUS_PENDING,
                Order::STATUS_CONFIRMED,
                Order::STATUS_SHIPPED,
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELED,
            ])],
            'payment_status' => ['required', 'in:' . implode(',', [
                Order::PAYMENT_UNPAID,
                Order::PAYMENT_PARTIAL,
                Order::PAYMENT_PAID,
            ])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
