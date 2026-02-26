<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('orders.change_status');
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(Order::workflowStatuses())],
        ];
    }
}
