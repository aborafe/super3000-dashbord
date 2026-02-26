<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_staff_cannot_access_products_create(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $user->assignRole('staff');

        $response = $this->actingAs($user)->get(route('admin.catalog.products.create', ['locale' => 'en']));

        $response->assertForbidden();
    }

    public function test_staff_can_access_products_index(): void
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create();
        $user->assignRole('staff');

        $response = $this->actingAs($user)->get(route('admin.catalog.products.index', ['locale' => 'en']));

        $response->assertOk();
    }
}
