<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('products.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['required_without:name_en', 'nullable', 'string', 'max:255'],
            'name_en' => ['required_without:name_ar', 'nullable', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'category_id' => ['required', 'exists:categories,id'],
            'image' => ['nullable', 'string', 'max:255'],
        ];
    }
}
