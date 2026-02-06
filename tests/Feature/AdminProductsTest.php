<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function createAdminUser(): User
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_view_products_index(): void
    {
        $user = $this->createAdminUser();

        $response = $this->actingAs($user)->get(route('admin.products.index'));

        $response->assertOk();
        $response->assertSeeText('Products');
    }

    public function test_admin_can_create_product(): void
    {
        $user = $this->createAdminUser();
        $category = Category::factory()->create();

        $payload = [
            'name_en' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => 10,
            'cost' => 5,
            'stock' => 3,
            'status' => 'active',
            'category_id' => $category->id,
        ];

        $response = $this->actingAs($user)->post(route('admin.products.store'), $payload);

        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('products', [
            'sku' => 'TEST-001',
            'name_en' => 'Test Product',
        ]);
    }
}
