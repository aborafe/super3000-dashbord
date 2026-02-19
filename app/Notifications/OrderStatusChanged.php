<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public string $from,
        public string $to,
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

    protected function payload(): array
    {
        return [
            'type' => 'order_status_changed',
            'title' => __('Order status updated'),
            'message' => __('Order :no status changed from :from to :to.', [
                'no' => $this->order->order_no,
                'from' => $this->from,
                'to' => $this->to,
            ]),
            'order_id' => $this->order->id,
            'order_no' => $this->order->order_no,
            'from' => $this->from,
            'to' => $this->to,
            'route' => 'admin.orders.show',
            'route_params' => [
                'order' => $this->order->id,
            ],
        ];
    }
}
