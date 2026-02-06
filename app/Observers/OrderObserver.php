<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
use App\Notifications\NewOrderCreated;
use App\Notifications\OrderStatusChanged;
use Illuminate\Support\Facades\Notification;

class OrderObserver
{
    public function created(Order $order): void
    {
        $this->notifyAdmins(new NewOrderCreated($order));
    }

    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        $from = (string) $order->getOriginal('status');
        $to = (string) $order->status;

        $this->notifyAdmins(new OrderStatusChanged($order, $from, $to));
    }

    protected function notifyAdmins(object $notification): void
    {
        $admins = User::role('admin')->get();

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, $notification);
    }
}
