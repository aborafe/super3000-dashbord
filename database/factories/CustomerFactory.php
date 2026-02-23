<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'whatsapp' => $this->faker->optional()->phoneNumber(),
            'city' => $this->faker->optional()->city(),
            'address' => $this->faker->optional()->streetAddress(),
            'password' => Hash::make('password123'),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
