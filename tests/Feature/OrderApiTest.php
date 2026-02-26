<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_create_order_requires_authentication(): void
    {
        $product = Product::factory()->create([
            'stock_qty' => 5,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v2/orders', [
            'items' => [
                ['productId' => $product->id, 'quantity' => 1],
            ],
            'checkout' => [
                'shippingAddress' => 'Cairo, Nasr City, Street 1, Building 3',
            ],
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('status', false);
    }

    public function test_create_order_successfully_decrements_stock(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'price' => 100,
            'stock_qty' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAsCustomerApi($customer)
            ->withHeaders(['Idempotency-Key' => 'idem-order-success'])
            ->postJson('/api/v2/orders', [
                'items' => [
                    ['productId' => $product->id, 'quantity' => 2],
                ],
                'phone' => '01011112222',
                'whatsapp' => '01033334444',
                'email' => 'checkout@example.com',
                'city' => 'Cairo',
                'address' => 'Cairo, Nasr City, Street 1, Building 3',
                'notes' => 'Do not call before arrival',
                'billing_payment_method' => 'cash',
                'checkout' => [
                    'shippingAddress' => 'Cairo, Nasr City, Street 1, Building 3',
                    'billingAddress' => 'Cairo, Nasr City, Billing Desk 5',
                    'notes' => 'Ring the bell',
                ],
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('status', true);
        $response->assertJsonPath('meta.message', 'Order created');
        $response->assertJsonPath('data.currency', 'EGP');
        $response->assertJsonPath('data.financials.subtotal', 200);
        $response->assertJsonPath('data.financials.total', 200);
        $response->assertJsonPath('data.financials.paid_amount', 0);
        $response->assertJsonPath('data.financials.due_amount', 200);
        $response->assertJsonPath('data.contact_snapshot.phone', '01011112222');
        $response->assertJsonPath('data.items.0.product.unit_label', 'Unit');

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'status' => Order::STATUS_PENDING,
            'customer_notes' => 'Do not call before arrival',
        ]);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'phone' => '01011112222',
            'city' => 'Cairo',
            'whatsapp' => '01033334444',
            'email' => 'checkout@example.com',
            'address' => 'Cairo, Nasr City, Street 1, Building 3',
        ]);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'qty' => 2]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_qty' => 3]);
        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'billing_payment_method' => 'cash',
        ]);

        $storedOrder = Order::query()->latest('id')->firstOrFail();
        $this->assertIsArray($storedOrder->billing_address);
        $this->assertSame('Cairo, Nasr City, Billing Desk 5', $storedOrder->billing_address['address_line_1'] ?? null);
    }

    public function test_create_order_uses_customer_profile_defaults_when_checkout_fields_are_missing(): void
    {
        $customer = Customer::factory()->create([
            'email' => 'profile@example.test',
            'phone' => '01077778888',
            'whatsapp' => '01099990000',
            'city' => 'Mansoura',
            'address' => 'Main Street, Building 10',
        ]);

        $product = Product::factory()->create([
            'price' => 75,
            'stock_qty' => 3,
            'is_active' => true,
        ]);

        $response = $this->actingAsCustomerApi($customer)
            ->withHeaders(['Idempotency-Key' => 'idem-order-profile-defaults'])
            ->postJson('/api/v2/orders', [
                'items' => [
                    ['productId' => $product->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.contact_snapshot.email', 'profile@example.test');
        $response->assertJsonPath('data.contact_snapshot.phone', '01077778888');
        $response->assertJsonPath('data.contact_snapshot.whatsapp', '01099990000');
        $response->assertJsonPath('data.contact_snapshot.city', 'Mansoura');
        $response->assertJsonPath('data.contact_snapshot.address', 'Main Street, Building 10');
    }

    public function test_create_order_syncs_basic_customer_profile_fields_from_checkout_payload(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Old Name',
            'email' => 'old-profile@example.test',
            'phone' => '01000000001',
            'whatsapp' => null,
            'city' => null,
            'address' => null,
        ]);

        $product = Product::factory()->create([
            'price' => 60,
            'stock_qty' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAsCustomerApi($customer)
            ->withHeaders(['Idempotency-Key' => 'idem-sync-customer-profile'])
            ->postJson('/api/v2/orders', [
                'items' => [
                    ['productId' => $product->id, 'quantity' => 1],
                ],
                'name' => 'New Name',
                'email' => 'new-profile@example.test',
                'phone' => '01011110000',
                'whatsapp' => '01022220000',
                'city' => 'Alexandria',
                'address' => 'Smouha, Block 7',
                'shipping_address' => [
                    'address_line_1' => 'Temporary Delivery Point',
                    'city' => 'Cairo',
                ],
            ]);

        $response->assertStatus(201);

        $customer->refresh();
        $this->assertSame('New Name', $customer->name);
        $this->assertSame('new-profile@example.test', $customer->email);
        $this->assertSame('01011110000', $customer->phone);
        $this->assertSame('01022220000', $customer->whatsapp);
        $this->assertSame('Alexandria', $customer->city);
        $this->assertSame('Smouha, Block 7', $customer->address);

        $order = Order::query()->latest('id')->firstOrFail();
        $this->assertSame('Temporary Delivery Point', $order->shipping_address['address_line_1'] ?? null);
        $this->assertSame('Cairo', $order->shipping_address['city'] ?? null);
    }

    public function test_create_order_keeps_customer_default_address_when_only_exceptional_shipping_is_sent(): void
    {
        $customer = Customer::factory()->create([
            'city' => 'Mansoura',
            'address' => 'Main Street, Building 10',
        ]);

        $product = Product::factory()->create([
            'price' => 45,
            'stock_qty' => 4,
            'is_active' => true,
        ]);

        $response = $this->actingAsCustomerApi($customer)
            ->withHeaders(['Idempotency-Key' => 'idem-exceptional-shipping-only'])
            ->postJson('/api/v2/orders', [
                'items' => [
                    ['productId' => $product->id, 'quantity' => 1],
                ],
                'shipping_address' => [
                    'address_line_1' => 'Temporary Site 9',
                    'city' => 'Cairo',
                ],
            ]);

        $response->assertStatus(201);

        $customer->refresh();
        $this->assertSame('Main Street, Building 10', $customer->address);
        $this->assertSame('Mansoura', $customer->city);

        $order = Order::query()->latest('id')->firstOrFail();
        $this->assertSame('Temporary Site 9', $order->shipping_address['address_line_1'] ?? null);
        $this->assertSame('Cairo', $order->shipping_address['city'] ?? null);
    }

    public function test_create_order_returns_409_when_stock_is_insufficient(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'stock_qty' => 1,
            'is_active' => true,
        ]);

        $response = $this->actingAsCustomerApi($customer)
            ->withHeaders(['Idempotency-Key' => 'idem-insufficient-stock'])
            ->postJson('/api/v2/orders', [
                'items' => [
                    ['productId' => $product->id, 'quantity' => 5],
                ],
                'checkout' => [
                    'shippingAddress' => 'Cairo, Nasr City, Street 1, Building 3',
                ],
            ]);

        $response->assertStatus(409);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('meta.message', 'Insufficient stock');
    }

    public function test_create_order_requires_idempotency_key_on_v2(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'stock_qty' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAsCustomerApi($customer)->postJson('/api/v2/orders', [
            'items' => [
                ['productId' => $product->id, 'quantity' => 1],
            ],
            'checkout' => [
                'shippingAddress' => 'Cairo, Nasr City, Street 1, Building 3',
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('meta.message', 'Idempotency-Key is required');
    }

    public function test_create_order_is_idempotent_when_same_key_is_retried(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'price' => 50,
            'stock_qty' => 10,
            'is_active' => true,
        ]);

        $payload = [
            'items' => [
                ['productId' => $product->id, 'quantity' => 1],
            ],
            'checkout' => [
                'shippingAddress' => 'Cairo, Nasr City, Street 1, Building 3',
            ],
        ];

        $first = $this->actingAsCustomerApi($customer)
            ->withHeaders(['Idempotency-Key' => 'idem-retry-1'])
            ->postJson('/api/v2/orders', $payload);

        $first->assertStatus(201);

        $second = $this->actingAsCustomerApi($customer)
            ->withHeaders(['Idempotency-Key' => 'idem-retry-1'])
            ->postJson('/api/v2/orders', $payload);

        $second->assertStatus(200);
        $second->assertJsonPath('meta.message', 'Order already exists');

        $this->assertSame(1, Order::query()->where('customer_id', $customer->id)->count());
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_qty' => 9]);
    }

    public function test_customer_cannot_access_another_customer_order(): void
    {
        $ownerCustomer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();

        $order = Order::factory()->create([
            'user_id' => null,
            'customer_id' => $ownerCustomer->id,
        ]);

        $response = $this->actingAsCustomerApi($otherCustomer)->getJson('/api/v2/orders/' . $order->id);

        $response->assertStatus(403);
        $response->assertJsonPath('status', false);
        $response->assertJsonPath('meta.message', 'Forbidden');
    }

    public function test_orders_index_returns_only_authenticated_customer_orders(): void
    {
        $customerA = Customer::factory()->create(['email' => 'a@example.com']);
        $customerB = Customer::factory()->create(['email' => 'b@example.com']);

        $orderForA = Order::factory()->create([
            'user_id' => null,
            'customer_id' => $customerA->id,
        ]);

        $orderForB = Order::factory()->create([
            'user_id' => null,
            'customer_id' => $customerB->id,
        ]);

        $response = $this->actingAsCustomerApi($customerA)->getJson('/api/v2/orders');

        $response->assertStatus(200);
        $response->assertJsonPath('status', true);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'financials' => ['subtotal', 'adjustments_total', 'total', 'paid_amount', 'due_amount'],
                    'contact_snapshot' => ['name', 'email', 'phone', 'city', 'whatsapp', 'address', 'notes'],
                ],
            ],
        ]);

        $data = $response->json('data');
        $orderIds = collect($data)->pluck('id')->all();

        $this->assertContains((string) $orderForA->id, $orderIds);
        $this->assertNotContains((string) $orderForB->id, $orderIds);
    }

    public function test_customer_receives_notification_on_status_change(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id, 'status' => Order::STATUS_PENDING]);

        // ensure no notifications initially
        $this->assertCount(0, $customer->notifications);

        // change status
        $order->status = Order::STATUS_SHIPPED;
        $order->save();

        $customer->refresh();
        $this->assertNotEmpty($customer->notifications);
        $payload = $customer->notifications->first()->data;
        $this->assertEquals('order_status_changed', $payload['type']);
        $this->assertEquals(Order::STATUS_PENDING, $payload['from']);
        $this->assertEquals(Order::STATUS_SHIPPED, $payload['to']);
    }
}
