<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement([
            Order::STATUS_PENDING,
            Order::STATUS_SHIPPED,
            Order::STATUS_DELIVERED,
        ]);

        return [
            'customer_id' => Customer::factory(),
            'order_no' => strtoupper($this->faker->unique()->bothify('ORD-#####')),
            'status' => $status,
            'subtotal' => 0,
            'total' => 0,
        ];
    }
}
