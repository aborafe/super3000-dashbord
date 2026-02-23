<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => ucfirst($this->faker->words(3, true)),
            'sku' => strtoupper($this->faker->unique()->bothify('SKU-####')),
            'price' => $this->faker->randomFloat(2, 5, 500),
            'stock_qty' => $this->faker->numberBetween(0, 200),
            'is_active' => $this->faker->boolean(90),
            'description' => $this->faker->optional()->paragraph(),
            'cover_image' => null,
        ];
    }
}
