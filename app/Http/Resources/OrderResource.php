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
        $subtotal = (float) $this->subtotal;
        $total = (float) $this->total_with_adjustments;
        $adjustmentsTotal = (float) $this->adjustments_total;
        $paidAmount = (float) $this->paid_amount;
        $dueAmount = (float) $this->due_amount;
        $itemsDiscountTotal = (float) $this->items_discount_total;
        $invoiceAdjustments = is_array($this->invoice_adjustments ?? null) ? $this->invoice_adjustments : null;
        $shippingAddress = is_array($this->shipping_address) ? $this->shipping_address : [];
        $customerName = (string) ($this->customer?->name ?? '');
        $customerEmail = (string) ($this->customer?->email ?? '');
        $customerPhone = (string) ($this->customer?->phone ?? '');
        $customerCity = (string) ($this->customer?->city ?? ($shippingAddress['city'] ?? ''));
        $customerWhatsapp = (string) ($this->customer?->whatsapp ?? '');
        $customerAddress = (string) ($this->customer?->address ?? ($shippingAddress['address_line_1'] ?? ''));
        $customerNotes = (string) ($this->customer_notes ?? '');

        return [
            'id' => (string) $this->id,
            'order_no' => $this->order_no,
            'status' => \App\Models\Order::normalizeStatus((string) $this->status),
            'tracking' => [
                'order_no' => $this->order_no,
                'idempotency_key' => $this->idempotency_key,
            ],
            'subtotal' => $subtotal,
            'total' => (float) $this->total,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => $customerPhone,
            'customer_city' => $customerCity,
            'customer_whatsapp' => $customerWhatsapp,
            'customer_address' => $customerAddress,
            'customer_notes' => $customerNotes,
            'shipping_address' => $shippingAddress !== [] ? $shippingAddress : null,
            'billing_address' => is_array($this->billing_address) ? $this->billing_address : null,
            'billing_payment_method' => $this->billing_payment_method !== null
                ? (string) $this->billing_payment_method
                : null,
            'invoice_adjustments' => $invoiceAdjustments,
            'adjustments_total' => $adjustmentsTotal,
            'total_with_adjustments' => $total,
            'paid_amount' => $paidAmount,
            'due_amount' => $dueAmount,
            'discount_total' => $itemsDiscountTotal,
            'currency' => currency_code(),
            'financials' => [
                'subtotal' => $subtotal,
                'adjustments_total' => $adjustmentsTotal,
                'discount_total' => $itemsDiscountTotal,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
            ],
            'contact_snapshot' => [
                'name' => $customerName,
                'email' => $customerEmail,
                'phone' => $customerPhone,
                'city' => $customerCity,
                'whatsapp' => $customerWhatsapp,
                'address' => $customerAddress,
                'notes' => $customerNotes,
            ],
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
                        'method' => (string) ($payment->method ?? ''),
                        'status' => (string) ($payment->status ?? ''),
                        'paid_at' => optional($payment->paid_at ?? $payment->created_at)?->toIso8601String(),
                    ];
                })->values();
            }),
        ];
    }
}
