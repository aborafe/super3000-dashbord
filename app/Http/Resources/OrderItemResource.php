<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\OrderItem
 */
class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'product_id' => $this->product_id !== null ? (string) $this->product_id : null,
            'qty' => (int) $this->qty,
            'price' => (float) $this->price,
            'base_price' => (float) ($this->base_price ?? $this->price),
            'cost' => (float) $this->cost,
            'unit_discount' => (float) $this->unit_discount,
            'discount_total' => (float) $this->discount_total,
            'line_total' => (float) $this->line_total,
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product?->id !== null ? (string) $this->product->id : null,
                    'name' => $this->product?->name,
                    'sku' => $this->product?->sku,
                    'cover_image_url' => $this->product?->cover_image
                        ? asset('storage/' . $this->product->cover_image)
                        : null,
                    'unit_label' => 'Unit',
                ];
            }),
        ];
    }
}
