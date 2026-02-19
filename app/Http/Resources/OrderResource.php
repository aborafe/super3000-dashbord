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
            'id' => (string) $this->id,
            'order_no' => $this->order_no,
            'status' => \App\Models\Order::normalizeStatus((string) $this->status),
            'tracking' => [
                'order_no' => $this->order_no,
                'idempotency_key' => $this->idempotency_key,
            ],
            'subtotal' => (float) $this->subtotal,
            'total' => (float) $this->total,
            'customer_name' => (string) ($this->customer_name ?: ($this->customer?->name ?? '')),
            'customer_email' => (string) ($this->customer_email ?: ($this->customer?->email ?? '')),
            'customer_phone' => (string) ($this->customer_phone ?: ($this->customer?->phone ?? '')),
            'customer_whatsapp' => (string) ($this->customer_whatsapp ?? ''),
            'customer_address' => (string) ($this->customer_address ?? ''),
            'customer_notes' => (string) ($this->customer_notes ?? ''),
            'shipping_address' => is_array($this->shipping_address) ? $this->shipping_address : null,
            'invoice_adjustments' => $this->invoice_adjustments,
            'adjustments_total' => (float) $this->adjustments_total,
            'total_with_adjustments' => (float) $this->total_with_adjustments,
            'paid_amount' => (float) $this->paid_amount,
            'due_amount' => (float) $this->due_amount,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer?->id !== null ? (string) $this->customer->id : null,
                    'name' => $this->customer?->name,
                    'email' => $this->customer?->email,
                ];
            }),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payments' => $this->whenLoaded('payments', function () {
                return $this->payments->map(function ($payment) {
                    return [
                        'id' => (string) $payment->id,
                        'amount' => (float) $payment->amount,
                        'paid_at' => optional($payment->paid_at ?? $payment->created_at)?->toIso8601String(),
                    ];
                })->values();
            }),
        ];
    }
}
