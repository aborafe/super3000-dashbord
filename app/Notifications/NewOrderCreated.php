<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewOrderCreated extends Notification
{
    use Queueable;

    public function __construct(public Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
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
            'type' => 'new_order_created',
            'title' => __('New order created'),
            'message' => __('Order :no was created.', ['no' => $this->order->order_no]),
            'order_id' => $this->order->id,
            'order_no' => $this->order->order_no,
            'route' => 'admin.orders.show',
            'route_params' => [
                'order' => $this->order->id,
            ],
        ];
    }
}
