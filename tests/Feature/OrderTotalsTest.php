<?php

namespace Tests\Feature;

use App\Models\Customer;
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

    public function test_admin_can_update_order_status_to_approved(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $customer = Customer::query()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
        ]);

        $order = \App\Models\Order::query()->create([
            'order_no' => 'ORD-TEST-001',
            'customer_id' => $customer->id,
            'status' => \App\Models\Order::STATUS_PENDING,
            'subtotal' => 0,
            'total' => 0,
        ]);

        $response = $this->actingAs($user)->patch(
            route('admin.sales.orders.status', ['locale' => 'en', 'order' => $order]),
            ['status' => \App\Models\Order::STATUS_APPROVED],
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => \App\Models\Order::STATUS_APPROVED,
        ]);
    }

    public function test_admin_cannot_skip_order_status_transition(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $customer = Customer::query()->create([
            'name' => 'Test Customer',
            'email' => 'customer2@example.com',
        ]);

        $order = \App\Models\Order::query()->create([
            'order_no' => 'ORD-TEST-002',
            'customer_id' => $customer->id,
            'status' => \App\Models\Order::STATUS_PENDING,
            'subtotal' => 0,
            'total' => 0,
        ]);

        $response = $this->actingAs($user)->patch(
            route('admin.sales.orders.status', ['locale' => 'en', 'order' => $order]),
            ['status' => \App\Models\Order::STATUS_SHIPPED],
        );

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => \App\Models\Order::STATUS_PENDING,
        ]);
    }

    public function test_admin_cannot_cancel_after_shipping(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $customer = Customer::query()->create([
            'name' => 'Test Customer',
            'email' => 'customer3@example.com',
        ]);

        $order = \App\Models\Order::query()->create([
            'order_no' => 'ORD-TEST-003',
            'customer_id' => $customer->id,
            'status' => \App\Models\Order::STATUS_SHIPPED,
            'subtotal' => 0,
            'total' => 0,
        ]);

        $response = $this->actingAs($user)->patch(
            route('admin.sales.orders.status', ['locale' => 'en', 'order' => $order]),
            ['status' => \App\Models\Order::STATUS_CANCELLED],
        );

        $response->assertSessionHasErrors('status');
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => \App\Models\Order::STATUS_SHIPPED,
        ]);
    }
}
