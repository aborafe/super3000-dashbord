<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Product
 */
class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $basePrice = (float) $this->price;
        $soldQty = (int) ($this->sold_qty ?? 0);
        $isRecent = $this->created_at !== null
            ? $this->created_at->greaterThanOrEqualTo(now()->subDays(30))
            : false;

        return [
            'id' => (string) $this->id,
            'name' => (string) $this->name,
            'sku' => (string) $this->sku,
            'price' => $basePrice,
            'stock_qty' => (int) $this->stock_qty,
            'category_id' => $this->category_id !== null ? (string) $this->category_id : null,
            'brand' => $this->brand,
            'made_in' => $this->made_in,
            // description may be truncated by controller for listings
            'description' => $this->description !== null ? (string) $this->description : null,
            'cover_image_url' => $this->cover_image
                ? asset('storage/' . $this->cover_image)
                : null,
            'images_count' => $this->images_count ?? ($this->whenLoaded('images', fn() => $this->images->count())),
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(fn($img) => [
                    'id' => $img->id,
                    'image_url' => asset('storage/' . $img->image_path),
                    'sort_order' => $img->sort_order,
                ]);
            }),
            'unit_label' => 'Unit',
            'featured' => $isRecent,
            'best_seller' => $soldQty > 0,
            'sold_qty' => $soldQty,
            'tier_pricing' => [
                [
                    'min_qty' => 1,
                    'unit_price' => $basePrice,
                ],
            ],
            'category' => CategoryResource::make($this->whenLoaded('category')),
        ];
    }
}
