<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement(['created', 'updated', 'deleted']),
            'entity_type' => $this->faker->randomElement(['product', 'order', 'customer']),
            'entity_id' => $this->faker->numberBetween(1, 100),
            'meta' => ['ip' => $this->faker->ipv4()],
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}
