<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\CustomerLedgerService;
use App\Services\InventoryService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderInvoiceEditorTest extends TestCase
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

    public function test_admin_can_update_invoice_customer_and_address_details(): void
    {
        $admin = $this->createAdminUser();
        $order = Order::factory()->create([
            'customer_id' => Customer::factory()->create()->id,
            'subtotal' => 100,
            'total' => 100,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.orders.details', [
            'locale' => 'en',
            'order' => $order->id,
        ]), [
            'customer_name' => 'Edited Customer',
            'customer_email' => 'edited@example.test',
            'customer_phone' => '01012345678',
            'shipping' => [
                'address_line_1' => '12 Shipping Road',
                'city' => 'Cairo',
                'postal_code' => '12345',
                'country' => 'Egypt',
            ],
        ]);

        $response->assertRedirect(route('admin.orders.show', ['locale' => 'en', 'order' => $order->id]));

        $order->refresh();
        $order->load('customer');

        $this->assertSame('Edited Customer', (string) $order->customer?->name);
        $this->assertSame('edited@example.test', (string) $order->customer?->email);
        $this->assertSame('01012345678', (string) $order->customer?->phone);
        $this->assertSame('12 Shipping Road', $order->shipping_address['address_line_1'] ?? null);
    }

    public function test_order_details_update_returns_json_for_autosave_requests(): void
    {
        $admin = $this->createAdminUser();
        $order = Order::factory()->create([
            'customer_id' => Customer::factory()->create()->id,
        ]);

        $response = $this->actingAs($admin)
            ->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->post(route('admin.orders.details', [
                'locale' => 'en',
                'order' => $order->id,
            ]), [
                '_method' => 'PATCH',
                'customer_name' => 'Autosave Customer',
                'customer_email' => 'autosave@example.test',
                'customer_phone' => '01099999999',
                'shipping' => [
                    'address_line_1' => 'Auto Street',
                    'city' => 'Alex',
                ],
            ]);

        $response->assertOk();
        $response->assertJsonFragment(['message' => 'Invoice and customer details updated.']);
        $this->assertDatabaseHas('customers', [
            'id' => $order->customer_id,
            'name' => 'Autosave Customer',
            'email' => 'autosave@example.test',
        ]);
    }

    public function test_admin_can_open_order_invoice_editor_page(): void
    {
        $admin = $this->createAdminUser();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['is_active' => true]);
        $order = Order::factory()->create(['customer_id' => $customer->id]);

        $order->items()->create([
            'product_id' => $product->id,
            'qty' => 1,
            'price' => 22,
            'cost' => 22,
            'line_total' => 22,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.orders.show', [
            'locale' => 'en',
            'order' => $order->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('Invoice items');
        $response->assertSeeText('Customer details');
    }

    public function test_admin_can_edit_invoice_items_add_new_and_remove_existing_with_recalculation(): void
    {
        $admin = $this->createAdminUser();
        $inventory = app(InventoryService::class);
        $defaultWarehouseId = $inventory->resolveDefaultWarehouseId();
        $customer = Customer::factory()->create();
        $productOne = Product::factory()->create(['is_active' => true, 'stock_qty' => 0]);
        $productTwo = Product::factory()->create(['is_active' => true, 'stock_qty' => 0]);
        $productThree = Product::factory()->create(['is_active' => true, 'stock_qty' => 0]);

        $inventory->moveStock($productOne->id, $defaultWarehouseId, 20, 'Test seed stock');
        $inventory->moveStock($productTwo->id, $defaultWarehouseId, 10, 'Test seed stock');
        $inventory->moveStock($productThree->id, $defaultWarehouseId, 5, 'Test seed stock');

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => Order::STATUS_PENDING,
            'subtotal' => 40,
            'total' => 40,
        ]);

        $itemOne = $order->items()->create([
            'product_id' => $productOne->id,
            'qty' => 2,
            'price' => 10,
            'cost' => 10,
            'line_total' => 20,
        ]);

        $itemTwo = $order->items()->create([
            'product_id' => $productTwo->id,
            'qty' => 1,
            'price' => 20,
            'cost' => 20,
            'line_total' => 20,
        ]);

        // Simulate stock deductions applied when the order was originally created.
        $inventory->moveStock($productOne->id, $defaultWarehouseId, -2, 'Test initial order consumption');
        $inventory->moveStock($productTwo->id, $defaultWarehouseId, -1, 'Test initial order consumption');

        $response = $this->actingAs($admin)->patch(route('admin.orders.items', [
            'locale' => 'en',
            'order' => $order->id,
        ]), [
            'items' => [
                [
                    'id' => $itemOne->id,
                    'product_id' => $productOne->id,
                    'qty' => 3,
                    'price' => 12.50,
                ],
                [
                    'product_id' => $productThree->id,
                    'qty' => 2,
                    'price' => 7.00,
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.orders.show', ['locale' => 'en', 'order' => $order->id]));

        $order->refresh();

        $this->assertDatabaseMissing('order_items', ['id' => $itemTwo->id]);
        $this->assertDatabaseHas('order_items', [
            'id' => $itemOne->id,
            'qty' => 3,
            'line_total' => 37.5,
        ]);
        $this->assertEquals(51.5, (float) $order->subtotal);
        $this->assertEquals(51.5, (float) $order->total);
        $this->assertSame(17, $inventory->availableInWarehouse($productOne->id, $defaultWarehouseId));
        $this->assertSame(10, $inventory->availableInWarehouse($productTwo->id, $defaultWarehouseId));
        $this->assertSame(3, $inventory->availableInWarehouse($productThree->id, $defaultWarehouseId));
    }

    public function test_admin_cannot_edit_invoice_items_after_shipping(): void
    {
        $admin = $this->createAdminUser();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['is_active' => true]);

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => Order::STATUS_SHIPPED,
            'subtotal' => 25,
            'total' => 25,
        ]);

        $item = $order->items()->create([
            'product_id' => $product->id,
            'qty' => 1,
            'price' => 25,
            'cost' => 25,
            'line_total' => 25,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.orders.items', [
            'locale' => 'en',
            'order' => $order->id,
        ]), [
            'items' => [
                [
                    'id' => $item->id,
                    'product_id' => $product->id,
                    'qty' => 2,
                    'price' => 25,
                ],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('items');

        $this->assertDatabaseHas('order_items', [
            'id' => $item->id,
            'qty' => 1,
            'line_total' => 25,
        ]);
    }

    public function test_print_page_uses_invoice_snapshot_details(): void
    {
        $admin = $this->createAdminUser();
        $customer = Customer::factory()->create([
            'name' => 'Snapshot Customer',
            'email' => 'snapshot@example.test',
            'phone' => '0500000000',
        ]);
        $product = Product::factory()->create(['is_active' => true]);

        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'shipping_address' => [
                'address_line_1' => '221B Baker Street',
                'city' => 'London',
            ],
            'subtotal' => 30,
            'total' => 30,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'qty' => 1,
            'price' => 30,
            'cost' => 30,
            'line_total' => 30,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.invoices.print', [
            'locale' => 'en',
            'order' => $order->id,
        ]));

        $response->assertOk();
        $response->assertSeeText('Snapshot Customer');
        $response->assertSeeText('221B Baker Street');
        $response->assertSeeText('snapshot@example.test');
        $response->assertSeeText('0500000000');
    }

    public function test_admin_can_record_customer_payment_from_customer_page_with_fifo_allocation(): void
    {
        $admin = $this->createAdminUser();
        $customer = Customer::factory()->create();
        $olderOrder = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => Order::STATUS_PENDING,
            'subtotal' => 100,
            'total' => 100,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
        $newerOrder = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => Order::STATUS_PENDING,
            'subtotal' => 80,
            'total' => 80,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.sales.customers.payments.store', [
            'locale' => 'en',
            'customer' => $customer->id,
        ]), [
            'amount' => 120,
            'method' => 'cash',
            'paid_at' => now()->toDateString(),
            'notes' => 'ledger payment',
        ]);

        $response->assertRedirect(route('admin.sales.customers.edit', [
            'locale' => 'en',
            'customer' => $customer->id,
        ]));

        $payment = Payment::query()->latest('id')->firstOrFail();

        $this->assertSame('customer_account', $payment->source);
        $this->assertSame((int) $customer->id, (int) $payment->customer_id);
        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'order_id' => $olderOrder->id,
            'amount' => 100,
        ]);
        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'order_id' => $newerOrder->id,
            'amount' => 20,
        ]);
    }

    public function test_admin_can_record_partial_payment_from_order_edit_page(): void
    {
        $admin = $this->createAdminUser();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => Order::STATUS_PENDING,
            'subtotal' => 100,
            'total' => 100,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.orders.payments.store', [
            'locale' => 'en',
            'order' => $order->id,
        ]), [
            'amount' => 40,
            'method' => 'transfer',
            'paid_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('admin.orders.show', [
            'locale' => 'en',
            'order' => $order->id,
        ]));

        $payment = Payment::query()->latest('id')->firstOrFail();

        $this->assertSame('order_edit', $payment->source);
        $this->assertSame((int) $order->id, (int) $payment->order_id);
        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'amount' => 40,
        ]);
    }

    public function test_partial_order_payment_creates_customer_notification_event(): void
    {
        $admin = $this->createAdminUser();
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => Order::STATUS_PENDING,
            'subtotal' => 100,
            'total' => 100,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.orders.payments.store', [
            'locale' => 'en',
            'order' => $order->id,
        ]), [
            'amount' => 40,
            'method' => 'transfer',
            'paid_at' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('admin.orders.show', [
            'locale' => 'en',
            'order' => $order->id,
        ]));

        $notification = $customer->fresh()->notifications()->latest()->first();
        $this->assertNotNull($notification);

        $payload = $notification->data;
        $this->assertSame('order_payment_recorded', (string) ($payload['type'] ?? ''));
        $this->assertSame((int) $order->id, (int) ($payload['order_id'] ?? 0));
        $this->assertSame($order->order_no, (string) ($payload['order_no'] ?? ''));
        $this->assertEquals(40, (float) ($payload['payment_amount'] ?? 0));
        $this->assertEquals(60, (float) ($payload['due_after'] ?? 0));
        $this->assertTrue((bool) ($payload['is_partial'] ?? false));
    }

    public function test_customer_ledger_tab_reflects_debtor_and_creditor_states(): void
    {
        $admin = $this->createAdminUser();
        $customer = Customer::factory()->create();
        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => Order::STATUS_PENDING,
            'subtotal' => 100,
            'total' => 100,
        ]);

        $debtorResponse = $this->actingAs($admin)->get(route('admin.sales.customers.edit', [
            'locale' => 'en',
            'customer' => $customer->id,
        ]));
        $debtorResponse->assertOk();
        $debtorResponse->assertSeeText('Debtor');
        $debtorResponse->assertSee('text-danger', false);

        app(CustomerLedgerService::class)->postCustomerPayment($customer, 150, 'cash');

        $creditorResponse = $this->actingAs($admin)->get(route('admin.sales.customers.edit', [
            'locale' => 'en',
            'customer' => $customer->id,
        ]));
        $creditorResponse->assertOk();
        $creditorResponse->assertSeeText('Creditor');
        $creditorResponse->assertSee('text-success', false);
    }
}
