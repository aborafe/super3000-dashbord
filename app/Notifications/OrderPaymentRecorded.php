<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderPaymentRecorded extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order,
        public Payment $payment,
        public float $appliedAmount,
        public float $dueBefore,
        public float $dueAfter,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    public function toArray(object $notifiable): array
    {
        return $this->payload();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $paidAt = $this->payment->paid_at ?? $this->payment->created_at;
        $isPartial = $this->dueAfter > 0.0;
        $amountText = money($this->appliedAmount);
        $dueText = money($this->dueAfter);

        return [
            'type' => 'order_payment_recorded',
            'title' => __('Payment update'),
            'message' => $isPartial
                ? __('Payment of :amount was recorded for order :no. Remaining due: :due.', [
                    'amount' => $amountText,
                    'no' => $this->order->order_no,
                    'due' => $dueText,
                ])
                : __('Payment of :amount was recorded for order :no. Invoice is fully paid.', [
                    'amount' => $amountText,
                    'no' => $this->order->order_no,
                ]),
            'order_id' => $this->order->id,
            'order_no' => $this->order->order_no,
            'payment_id' => $this->payment->id,
            'payment_amount' => round($this->appliedAmount, 2),
            'payment_method' => (string) ($this->payment->method ?? ''),
            'payment_status' => (string) ($this->payment->status ?? ''),
            'paid_at' => optional($paidAt)?->toIso8601String(),
            'due_before' => round($this->dueBefore, 2),
            'due_after' => round($this->dueAfter, 2),
            'is_partial' => $isPartial,
            // Keep route as route-name (not URL path) to avoid RouteNotFoundException
            // in templates that call route($payload['route'], ...).
            'route' => 'admin.orders.show',
            'admin_route' => 'admin.orders.show',
            'route_params' => [
                'order' => $this->order->id,
            ],
        ];
    }
}
