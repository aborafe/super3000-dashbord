<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoOrdersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::query()->take(10)->get();
        $products = Product::query()->take(10)->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            return;
        }

        foreach (range(1, 50) as $i) {
            $customer = $customers->random();
            $status = collect([
                Order::STATUS_PENDING,
                Order::STATUS_SHIPPED,
                Order::STATUS_DELIVERED,
            ])->random();

            $order = Order::query()->create([
                'order_no' => sprintf('ORD-%05d', $i),
                'customer_id' => $customer->id,
                'status' => $status,
            ]);

            $itemsCount = random_int(1, 3);

            for ($line = 0; $line < $itemsCount; $line++) {
                $product = $products->random();
                $qty = random_int(1, 5);
                $price = $product->price;
                $lineTotal = $qty * $price;

                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'price' => $price,
                    'base_price' => $price,
                    'cost' => $price * 0.6,
                    'line_total' => $lineTotal,
                ]);
            }

            $order->load('items');
            $order->recalculateTotals();
            $order->save();

            $paymentStatus = $status === Order::STATUS_CANCELLED
                ? 'failed'
                : collect(['paid', 'pending'])->random();

            $payment = Payment::query()->create([
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'method' => collect(['cash', 'card', 'transfer'])->random(),
                'amount' => $order->total,
                'status' => $paymentStatus,
                'source' => 'migration',
                'paid_at' => $paymentStatus === 'paid' ? now()->subDays(random_int(0, 20)) : null,
            ]);

            if ($paymentStatus !== 'paid') {
                continue;
            }

            PaymentAllocation::query()->create([
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'amount' => $order->total,
                'allocated_at' => $payment->paid_at ?? $payment->created_at,
                'allocation_type' => 'migration',
            ]);
        }
    }
}
