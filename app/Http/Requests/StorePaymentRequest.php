<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'exists:orders,id'],
            'method' => ['required', 'in:cash,card,transfer'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:paid,failed,pending'],
            'paid_at' => ['nullable', 'date'],
        ];
    }
}
