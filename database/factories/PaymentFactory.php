<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['paid', 'failed', 'pending']);

        return [
            'order_id' => Order::factory(),
            'customer_id' => static function (array $attributes) {
                $orderId = $attributes['order_id'] ?? null;
                if (is_numeric($orderId)) {
                    $customerId = Order::query()
                        ->whereKey((int) $orderId)
                        ->value('customer_id');

                    if ($customerId) {
                        return (int) $customerId;
                    }
                }

                return Customer::factory();
            },
            'method' => $this->faker->randomElement(['cash', 'card', 'transfer']),
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'status' => $status,
            'source' => $this->faker->randomElement(['order_edit', 'customer_account', 'migration']),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => null,
            'paid_at' => $status === 'paid' ? $this->faker->dateTimeBetween('-1 month', 'now') : null,
        ];
    }
}
