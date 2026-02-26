<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Order;
use App\Services\CustomerLedgerService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomerLedgerService $ledgerService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->ledgerService = app(CustomerLedgerService::class);
    }

    public function test_unpaid_invoices_make_customer_debtor_with_negative_balance(): void
    {
        $customer = Customer::factory()->create();
        $this->createOrder($customer, 100, Order::STATUS_PENDING);
        $this->createOrder($customer, 40, Order::STATUS_APPROVED);

        $ledger = $this->ledgerService->buildCustomerLedger($customer);

        $this->assertSame('debtor', $ledger['summary']['state']);
        $this->assertEquals(-140, $ledger['summary']['balance']);
    }

    public function test_customer_payment_is_allocated_fifo_from_oldest_to_newest(): void
    {
        $customer = Customer::factory()->create();
        $olderOrder = $this->createOrder($customer, 100, Order::STATUS_PENDING, now()->subDays(2));
        $newerOrder = $this->createOrder($customer, 80, Order::STATUS_PENDING, now()->subDay());

        $payment = $this->ledgerService->postCustomerPayment($customer, 120, 'cash');

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

        $olderOrder->refresh();
        $newerOrder->refresh();

        $this->assertEquals(0, $olderOrder->due_amount);
        $this->assertEquals(60, $newerOrder->due_amount);
    }

    public function test_overpayment_creates_positive_credit_balance_and_unallocated_amount(): void
    {
        $customer = Customer::factory()->create();
        $order = $this->createOrder($customer, 100, Order::STATUS_PENDING);

        $payment = $this->ledgerService->postCustomerPayment($customer, 150, 'cash');
        $ledger = $this->ledgerService->buildCustomerLedger($customer);

        $this->assertEquals(100, (float) $payment->allocated_amount);
        $this->assertEquals(50, (float) $payment->unallocated_amount);
        $this->assertSame('creditor', $ledger['summary']['state']);
        $this->assertEquals(50, $ledger['summary']['balance']);

        $order->refresh();
        $this->assertEquals(0, $order->due_amount);
    }

    public function test_order_payment_targets_same_order_first_and_leaves_extra_as_credit(): void
    {
        $customer = Customer::factory()->create();
        $olderOrder = $this->createOrder($customer, 70, Order::STATUS_PENDING, now()->subDays(2));
        $targetOrder = $this->createOrder($customer, 50, Order::STATUS_PENDING, now()->subDay());

        $payment = $this->ledgerService->postOrderPayment($targetOrder, 60, 'transfer');

        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'order_id' => $targetOrder->id,
            'amount' => 50,
        ]);
        $this->assertDatabaseMissing('payment_allocations', [
            'payment_id' => $payment->id,
            'order_id' => $olderOrder->id,
        ]);

        $this->assertEquals(10, (float) $payment->unallocated_amount);

        $olderOrder->refresh();
        $targetOrder->refresh();
        $this->assertEquals(70, $olderOrder->due_amount);
        $this->assertEquals(0, $targetOrder->due_amount);
    }

    public function test_cancelled_orders_are_excluded_from_fifo_allocation(): void
    {
        $customer = Customer::factory()->create();
        $cancelledOrder = $this->createOrder($customer, 120, Order::STATUS_CANCELLED, now()->subDays(2));
        $activeOrder = $this->createOrder($customer, 50, Order::STATUS_PENDING, now()->subDay());

        $payment = $this->ledgerService->postCustomerPayment($customer, 50, 'cash');

        $this->assertDatabaseMissing('payment_allocations', [
            'payment_id' => $payment->id,
            'order_id' => $cancelledOrder->id,
        ]);
        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'order_id' => $activeOrder->id,
            'amount' => 50,
        ]);
    }

    public function test_cancelling_order_releases_allocations_as_customer_credit(): void
    {
        $customer = Customer::factory()->create();
        $order = $this->createOrder($customer, 100, Order::STATUS_PENDING);
        $payment = $this->ledgerService->postOrderPayment($order, 100, 'cash');

        $this->assertDatabaseHas('payment_allocations', [
            'payment_id' => $payment->id,
            'order_id' => $order->id,
        ]);

        $order->status = Order::STATUS_CANCELLED;
        $order->save();
        $this->ledgerService->releaseCancelledOrderAllocations($order);

        $this->assertDatabaseMissing('payment_allocations', [
            'payment_id' => $payment->id,
            'order_id' => $order->id,
        ]);

        $ledger = $this->ledgerService->buildCustomerLedger($customer);
        $this->assertSame('creditor', $ledger['summary']['state']);
        $this->assertEquals(100, $ledger['summary']['balance']);
    }

    private function createOrder(
        Customer $customer,
        float $total,
        string $status,
        $createdAt = null
    ): Order {
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => $status,
            'subtotal' => $total,
            'total' => $total,
            'created_at' => $createdAt ?? now(),
            'updated_at' => $createdAt ?? now(),
        ]);

        return $order;
    }
}
