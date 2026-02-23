<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adjustments' => ['nullable', 'array'],
            'adjustments.*.label' => ['required_with:adjustments', 'string', 'max:255'],
            'adjustments.*.value' => ['required_with:adjustments', 'numeric'],
        ];
    }
}
