<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTotalsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_order_creation_recalculates_totals(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $partner = Partner::query()->create([
            'name' => 'Test Partner',
            'phone' => '01000000000',
            'role_type' => 'both',
        ]);

        $category = Category::factory()->create();
        $product = Product::query()->create([
            'name_en' => 'Test Product',
            'name_ar' => 'منتج تجريبي',
            'sku' => 'TEST-ORDER-001',
            'price' => 100,
            'cost' => 60,
            'stock' => 50,
            'status' => 'active',
            'category_id' => $category->id,
        ]);

        $payload = [
            'partner_id' => $partner->id,
            'status' => \App\Models\Order::STATUS_PENDING,
            'payment_status' => \App\Models\Order::PAYMENT_UNPAID,
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'price' => 100,
                ],
            ],
        ];

        $response = $this->actingAs($user)->post(route('admin.orders.store', ['locale' => 'en']), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'total' => 200,
            'cost_total' => 120,
            'profit' => 80,
        ]);
    }
}
