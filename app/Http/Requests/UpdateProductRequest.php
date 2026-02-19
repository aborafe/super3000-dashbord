<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('products.update');
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id ?? null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_qty' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'category_id' => ['required', 'exists:categories,id'],
            // new fields
            'brand' => ['nullable', 'string', 'max:255'],
            'made_in' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'deleted_image_ids' => ['array'],
            'deleted_image_ids.*' => ['integer', 'exists:product_images,id'],
            'images_orders' => ['array'],
            'images_orders.*.id' => ['required_with:images_orders.*.sort_order', 'integer', 'exists:product_images,id'],
            'images_orders.*.sort_order' => ['required_with:images_orders.*.id', 'integer'],
            'remove_cover' => ['boolean'],
        ];
    }
}
