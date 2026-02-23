<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
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
                    'tier_pricing',
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
        $response->assertJsonPath('data.0.tier_pricing.0.min_qty', 1);
        $response->assertJsonPath('meta.filters.applied_filter', 'all');
        $response->assertJsonPath('meta.filters.applied_sort', 'newest');
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

    public function test_product_listing_supports_popular_filter_and_best_seller_sorting(): void
    {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create();
        Role::findOrCreate('admin', 'web');

        $topSeller = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 50,
            'stock_qty' => 100,
            'is_active' => true,
        ]);

        $midSeller = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 40,
            'stock_qty' => 100,
            'is_active' => true,
        ]);

        $notPopular = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 30,
            'stock_qty' => 100,
            'is_active' => true,
        ]);

        $approvedOrder = Order::factory()->create(['status' => Order::STATUS_APPROVED]);
        $approvedOrder->items()->create([
            'product_id' => $topSeller->id,
            'qty' => 5,
            'price' => $topSeller->price,
            'cost' => $topSeller->price,
            'line_total' => (float) $topSeller->price * 5,
        ]);
        $approvedOrder->items()->create([
            'product_id' => $midSeller->id,
            'qty' => 2,
            'price' => $midSeller->price,
            'cost' => $midSeller->price,
            'line_total' => (float) $midSeller->price * 2,
        ]);

        $deliveredOrder = Order::factory()->create(['status' => Order::STATUS_DELIVERED]);
        $deliveredOrder->items()->create([
            'product_id' => $topSeller->id,
            'qty' => 3,
            'price' => $topSeller->price,
            'cost' => $topSeller->price,
            'line_total' => (float) $topSeller->price * 3,
        ]);

        $cancelledOrder = Order::factory()->create(['status' => Order::STATUS_CANCELLED]);
        $cancelledOrder->items()->create([
            'product_id' => $notPopular->id,
            'qty' => 10,
            'price' => $notPopular->price,
            'cost' => $notPopular->price,
            'line_total' => (float) $notPopular->price * 10,
        ]);

        $response = $this->actingAsCustomerApi($customer)->getJson('/api/v2/products?filter=popular&per_page=20');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.filters.applied_filter', 'popular');
        $response->assertJsonPath('meta.filters.applied_sort', 'best_selling');
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.id', (string) $topSeller->id);
        $response->assertJsonPath('data.1.id', (string) $midSeller->id);
        $response->assertJsonPath('data.0.best_seller', true);
        $response->assertJsonPath('data.1.best_seller', true);
    }

    public function test_product_listing_supports_price_filter_sorting(): void
    {
        $customer = Customer::factory()->create();
        $category = Category::factory()->create();

        $expensive = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 300,
            'stock_qty' => 100,
            'is_active' => true,
        ]);

        $cheap = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 20,
            'stock_qty' => 100,
            'is_active' => true,
        ]);

        $medium = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 120,
            'stock_qty' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAsCustomerApi($customer)->getJson('/api/v2/products?filter=price&per_page=20');

        $response->assertStatus(200);
        $response->assertJsonPath('meta.filters.applied_filter', 'price');
        $response->assertJsonPath('meta.filters.applied_sort', 'price_asc');
        $response->assertJsonPath('data.0.id', (string) $cheap->id);
        $response->assertJsonPath('data.1.id', (string) $medium->id);
        $response->assertJsonPath('data.2.id', (string) $expensive->id);
    }
}
