<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
use App\Notifications\NewOrderCreated;
use App\Notifications\OrderStatusChanged;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\Notification;

class OrderObserver
{
    public function created(Order $order): void
    {
        $this->notifyAdmins(new NewOrderCreated($order));
    }

    public function updated(Order $order): void
    {
        $changes = $order->getChanges();
        // always notify admins of updates
        if (! empty($changes)) {
            $details = array_keys($changes);
            ActivityLogger::log('updated', 'order', $order->id, ['changes' => $details]);
        }

        if ($order->wasChanged('status')) {
            $from = \App\Models\Order::normalizeStatus((string) $order->getOriginal('status'));
            $to = \App\Models\Order::normalizeStatus((string) $order->status);

            if ($from === $to) {
                return;
            }

            $notification = new OrderStatusChanged($order, $from, $to);
            $this->notifyAdmins($notification);

            if ($order->customer) {
                $order->customer->notify($notification);
            }
        } elseif (! empty($changes)) {
            // generic update notification to customer
            if ($order->customer) {
                $order->customer->notify(new \App\Notifications\AdminMessageNotification(
                    __('Order updated'),
                    __('Your order :no has been updated.', ['no' => $order->order_no])
                ));
            }
        }
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
