<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoOrdersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->first();
        $partners = Partner::query()->take(3)->get();
        $products = Product::query()->take(3)->get();

        if ($partners->isEmpty() || $products->isEmpty()) {
            return;
        }

        foreach (range(1, 10) as $i) {
            $partner = $partners->random();

            $order = Order::query()->create([
                'order_no' => sprintf('ORD-%04d', $i),
                'partner_id' => $partner->id,
                'status' => Order::STATUS_COMPLETED,
                'payment_status' => Order::PAYMENT_PAID,
                'created_by' => $user?->id,
            ]);

            $itemsCount = random_int(1, 3);
            $items = [];

            for ($line = 0; $line < $itemsCount; $line++) {
                $product = $products->random();
                $qty = random_int(1, 5);
                $price = $product->price;
                $cost = $product->cost;
                $lineTotal = $qty * $price;

                $items[] = OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'price' => $price,
                    'cost' => $cost,
                    'line_total' => $lineTotal,
                ]);

                // Decrease product stock for realism
                $product->decrement('stock', $qty);
            }

            // Recalculate MVP totals
            $order->load('items');
            $order->recalculateTotals();
            $order->save();
        }
    }
}
