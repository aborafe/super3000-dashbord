<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('orders.change_status');
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:' . implode(',', [
                Order::STATUS_PENDING,
                Order::STATUS_SHIPPED,
                Order::STATUS_DELIVERED,
            ])],
        ];
    }
}
