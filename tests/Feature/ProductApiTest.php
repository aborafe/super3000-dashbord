<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_listing_requires_authentication(): void
    {
        $response = $this->getJson('/api/v2/products');

        $response->assertStatus(401);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('meta.message', 'Unauthenticated');
    }

    public function test_product_listing_returns_envelope_with_data_and_meta(): void
    {
        $customer = Customer::factory()->create();
        Product::factory()->count(2)->create(['is_active' => true]);

        $response = $this->actingAsCustomerApi($customer)->getJson('/api/v2/products?per_page=20');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'sku',
                    'price',
                    'stock_qty',
                    'category_id',
                    'description',
                    'cover_image_url',
                    'images_count',
                ],
            ],
            'meta' => [
                'message',
                'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
            ],
            'errors',
        ]);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('data.0.images_count', 0);
        $response->assertJsonPath('data.0.cover_image_url', null);
    }

    public function test_customer_cannot_create_product_through_api(): void
    {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create();

        $payload = [
            'name' => 'With Images',
            'sku' => 'IMG-001',
            'price' => 5,
            'stock_qty' => 10,
            'is_active' => true,
            'category_id' => $category->id,
        ];

        $response = $this->actingAsCustomerApi($customer)->postJson('/api/v2/products', $payload);

        $response->assertStatus(405);
        $response->assertJsonPath('meta.message', 'Method not allowed');
    }

    public function test_customer_cannot_update_product_through_api(): void
    {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $payload = [
            'name' => 'Updated name',
            'sku' => $product->sku,
            'price' => 20,
            'stock_qty' => 5,
            'is_active' => false,
            'category_id' => $category->id,
        ];

        $response = $this->actingAsCustomerApi($customer)->putJson("/api/v2/products/{$product->id}", $payload);

        $response->assertStatus(405);
        $response->assertJsonPath('meta.message', 'Method not allowed');
    }
}
