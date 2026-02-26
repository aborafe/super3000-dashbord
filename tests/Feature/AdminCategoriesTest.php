<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function createAdminUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_admin_can_toggle_category_status(): void
    {
        $user = $this->createAdminUser();
        $category = Category::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->patch(route('admin.catalog.categories.toggle', ['locale' => 'en', 'category' => $category->id]));
        $response->assertRedirect();
        $this->assertFalse($category->fresh()->is_active);
    }
}
