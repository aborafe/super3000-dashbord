<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrdersRealtimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createAdminUser(): User
    {
        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    public function test_orders_live_endpoint_returns_table_rows_payload(): void
    {
        $admin = $this->createAdminUser();
        $customer = Customer::factory()->create(['name' => 'Realtime Customer']);
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => Order::STATUS_PENDING,
            'subtotal' => 125,
            'total' => 125,
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.orders.live', [
            'locale' => 'en',
            'per_page' => 10,
        ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'rows_html',
            'latest_order_id',
            'new_orders_count',
            'signature',
            'fetched_at',
        ]);
        $response->assertJsonPath('latest_order_id', (int) $order->id);
        $this->assertStringContainsString((string) $order->order_no, (string) $response->json('rows_html'));
    }
}

