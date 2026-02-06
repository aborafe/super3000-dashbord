<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Order
 */
class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_no' => $this->order_no,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'total' => (float) $this->total,
            'cost_total' => (float) $this->cost_total,
            'profit' => (float) $this->profit,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'partner' => $this->whenLoaded('partner', function () {
                return [
                    'id' => $this->partner?->id,
                    'name' => $this->partner?->name,
                ];
            }),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
