<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
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

        $response = $this->actingAs($user)->get(route('admin.catalog.products.index', ['locale' => 'en']));

        $response->assertOk();
        $response->assertSeeText('Products');
    }

    public function test_admin_can_create_product(): void
    {
        $user = $this->createAdminUser();
        $category = Category::factory()->create();

        Storage::fake('public');

        $payload = [
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => 10,
            'stock_qty' => 3,
            'is_active' => true,
            'category_id' => $category->id,
            'description' => 'Some description',
            'cover_image' => \Illuminate\Http\UploadedFile::fake()->image('cover.jpg'),
            'images' => [
                \Illuminate\Http\UploadedFile::fake()->image('one.png'),
            ],
        ];

        $response = $this->actingAs($user)->post(route('admin.catalog.products.store', ['locale' => 'en']), $payload);

        $response->assertRedirect(route('admin.catalog.products.index', ['locale' => 'en']));
        $this->assertDatabaseHas('products', [
            'sku' => 'TEST-001',
            'name' => 'Test Product',
            'description' => 'Some description',
        ]);

        $product = Product::where('sku', 'TEST-001')->first();
        $this->assertNotNull($product);
        $this->assertNotNull($product->cover_image);
        $this->assertTrue(Storage::disk('public')->exists((string) $product->cover_image));
        $this->assertCount(1, $product->images);
    }

    public function test_admin_can_update_product_and_images(): void
    {
        $user = $this->createAdminUser();
        $category = Category::factory()->create();
        Storage::fake('public');

        $product = Product::factory()->create([
            'category_id' => $category->id,
            'description' => 'old',
        ]);
        $product->images()->create(['image_path' => 'foo.jpg']);

        $payload = [
            'name' => 'Updated',
            'sku' => $product->sku,
            'price' => 50,
            'stock_qty' => 2,
            'is_active' => true,
            'category_id' => $category->id,
            'description' => 'new',
            'deleted_image_ids' => [$product->images->first()->id],
        ];

        $response = $this->actingAs($user)->put(route('admin.catalog.products.update', ['locale' => 'en', 'product' => $product->id]), $payload);
        $response->assertRedirect(route('admin.catalog.products.index', ['locale' => 'en']));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'description' => 'new']);
        $this->assertCount(0, $product->fresh()->images);
    }

    public function test_admin_can_toggle_product_status(): void
    {
        $user = $this->createAdminUser();
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->patch(route('admin.products.toggle', ['locale' => 'en', 'product' => $product->id]));
        $response->assertRedirect();
        $this->assertFalse($product->fresh()->is_active);
    }
}
