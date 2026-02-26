<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\User;
use App\Notifications\OrderPaymentRecorded;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

class CustomerLedgerService
{
    private const MONEY_EPSILON = 0.00001;

    /**
     * @return array{
     *   summary: array<string, mixed>,
     *   orders: array<int, array<string, mixed>>,
     *   payments: array<int, array<string, mixed>>
     * }
     */
    public function buildCustomerLedger(Customer $customer, int $ordersLimit = 100, int $paymentsLimit = 100): array
    {
        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->withSum('paymentAllocations as allocated_amount', 'amount')
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit($ordersLimit)
            ->get();

        $payments = Payment::query()
            ->where('customer_id', $customer->id)
            ->where('status', 'paid')
            ->with('order:id,order_no')
            ->withSum('allocations as allocated_amount', 'amount')
            ->orderByDesc(DB::raw('COALESCE(paid_at, created_at)'))
            ->orderByDesc('id')
            ->limit($paymentsLimit)
            ->get();

        $invoiceTotal = (float) $orders->sum(fn (Order $order): float => (float) $order->total);
        $paidTotal = (float) $payments->sum(fn (Payment $payment): float => (float) ($payment->getAttribute('amount') ?? 0));
        $balance = round($paidTotal - $invoiceTotal, 2);

        $orderRows = $orders->map(function (Order $order): array {
            $paid = round((float) ($order->allocated_amount ?? 0), 2);
            $due = max(0, round((float) $order->total - $paid, 2));

            return [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'status' => $order->normalized_status,
                'total' => (float) $order->total,
                'paid' => $paid,
                'due' => $due,
                'created_at' => $order->created_at,
            ];
        })->values()->all();

        $paymentRows = $payments->map(function (Payment $payment): array {
            $amount = (float) ($payment->getAttribute('amount') ?? 0);
            $method = (string) ($payment->getAttribute('method') ?? '');
            $status = (string) ($payment->getAttribute('status') ?? '');
            $source = (string) ($payment->getAttribute('source') ?? '');
            $notes = $payment->getAttribute('notes');
            $createdAt = $payment->getAttribute('created_at');
            $allocated = round((float) ($payment->allocated_amount ?? 0), 2);
            $unallocated = max(0, round($amount - $allocated, 2));

            return [
                'id' => $payment->id,
                'order_no' => $payment->order?->order_no,
                'method' => $method,
                'status' => $status,
                'source' => $source,
                'amount' => $amount,
                'allocated' => $allocated,
                'unallocated' => $unallocated,
                'paid_at' => $payment->paid_at ?? $createdAt,
                'notes' => is_string($notes) ? $notes : null,
            ];
        })->values()->all();

        return [
            'summary' => [
                'invoice_total' => round($invoiceTotal, 2),
                'paid_total' => round($paidTotal, 2),
                'balance' => $balance,
                'debtor_amount' => $balance < 0 ? abs($balance) : 0.0,
                'creditor_amount' => $balance > 0 ? $balance : 0.0,
                'state' => $balance < 0 ? 'debtor' : ($balance > 0 ? 'creditor' : 'balanced'),
            ],
            'orders' => $orderRows,
            'payments' => $paymentRows,
        ];
    }

    public function postCustomerPayment(
        Customer $customer,
        float $amount,
        string $method,
        ?CarbonInterface $paidAt = null,
        ?string $notes = null,
        ?int $createdBy = null
    ): Payment {
        $payment = DB::transaction(function () use ($customer, $amount, $method, $paidAt, $notes, $createdBy): Payment {
            $lockedCustomer = Customer::query()->lockForUpdate()->findOrFail($customer->id);

            $payment = Payment::query()->create([
                'order_id' => null,
                'customer_id' => $lockedCustomer->id,
                'amount' => round($amount, 2),
                'method' => $method,
                'status' => 'paid',
                'source' => 'customer_account',
                'notes' => $notes,
                'created_by' => $createdBy,
                'paid_at' => $paidAt ?? now(),
            ]);

            $this->allocateFIFO($lockedCustomer, $payment);

            return $payment->fresh(['customer', 'order', 'allocations']);
        });

        $this->dispatchOrderPaymentNotifications($payment);

        return $payment;
    }

    public function postOrderPayment(
        Order $order,
        float $amount,
        string $method,
        ?CarbonInterface $paidAt = null,
        ?string $notes = null,
        ?int $createdBy = null
    ): Payment {
        $payment = DB::transaction(function () use ($order, $amount, $method, $paidAt, $notes, $createdBy): Payment {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->normalized_status === Order::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'amount' => __('Cannot register payment for a cancelled order.'),
                ]);
            }

            $payment = Payment::query()->create([
                'order_id' => $lockedOrder->id,
                'customer_id' => $lockedOrder->customer_id,
                'amount' => round($amount, 2),
                'method' => $method,
                'status' => 'paid',
                'source' => 'order_edit',
                'notes' => $notes,
                'created_by' => $createdBy,
                'paid_at' => $paidAt ?? now(),
            ]);

            $this->allocateOrderFirstThenCredit($lockedOrder, $payment);

            return $payment->fresh(['customer', 'order', 'allocations']);
        });

        $this->dispatchOrderPaymentNotifications($payment);

        return $payment;
    }

    public function allocateFIFO(Customer $customer, Payment $payment): float
    {
        $remaining = $this->paymentRemainingAmount($payment);
        if ($remaining <= self::MONEY_EPSILON) {
            return 0.0;
        }

        $orders = $this->openOrdersQuery((int) $customer->id)->get();

        return $this->allocateAcrossOrders($payment, $orders, $remaining, 'fifo');
    }

    public function allocateOrderFirstThenCredit(Order $order, Payment $payment): float
    {
        $remaining = $this->paymentRemainingAmount($payment);
        if ($remaining <= self::MONEY_EPSILON) {
            return 0.0;
        }

        return $this->allocateAcrossOrders($payment, collect([$order]), $remaining, 'order_first');
    }

    public function autoApplyCreditOnNewDue(Customer $customer): void
    {
        DB::transaction(function () use ($customer): void {
            $lockedCustomer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $this->applyOpenCredits($lockedCustomer);
        });
    }

    public function releaseCancelledOrderAllocations(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $customerId = (int) $lockedOrder->customer_id;

            PaymentAllocation::query()
                ->where('order_id', $lockedOrder->id)
                ->lockForUpdate()
                ->delete();

            if ($customerId <= 0) {
                return;
            }

            $customer = Customer::query()->lockForUpdate()->find($customerId);
            if (! $customer) {
                return;
            }

            $this->applyOpenCredits($customer);
        });
    }

    private function applyOpenCredits(Customer $customer): void
    {
        $creditPayments = Payment::query()
            ->where('customer_id', $customer->id)
            ->where('status', 'paid')
            ->lockForUpdate()
            ->orderByRaw('COALESCE(paid_at, created_at)')
            ->orderBy('id')
            ->get();

        foreach ($creditPayments as $payment) {
            $remaining = $this->paymentRemainingAmount($payment);
            if ($remaining <= self::MONEY_EPSILON) {
                continue;
            }

            $openOrders = $this->openOrdersQuery((int) $customer->id)->get();
            if ($openOrders->isEmpty()) {
                return;
            }

            $this->allocateAcrossOrders($payment, $openOrders, $remaining, 'auto_credit');
        }
    }

    /**
     * @param Collection<int, Order> $orders
     */
    private function allocateAcrossOrders(Payment $payment, Collection $orders, float $remaining, string $allocationType): float
    {
        foreach ($orders as $order) {
            if ($remaining <= self::MONEY_EPSILON) {
                break;
            }

            $due = $this->orderOutstandingAmount($order->id);
            if ($due <= self::MONEY_EPSILON) {
                continue;
            }

            $allocationAmount = min($remaining, $due);
            if ($allocationAmount <= self::MONEY_EPSILON) {
                continue;
            }

            $this->createOrIncrementAllocation($payment, $order, $allocationAmount, $allocationType);
            $remaining = round($remaining - $allocationAmount, 2);
        }

        return max(0, round($remaining, 2));
    }

    private function createOrIncrementAllocation(Payment $payment, Order $order, float $amount, string $allocationType): void
    {
        $allocation = PaymentAllocation::query()
            ->where('payment_id', $payment->id)
            ->where('order_id', $order->id)
            ->lockForUpdate()
            ->first();

        if ($allocation) {
            $allocation->amount = round((float) $allocation->amount + $amount, 2);
            $allocation->allocation_type = $allocationType;
            $allocation->allocated_at = $allocation->allocated_at ?? ($payment->paid_at ?? now());
            $allocation->save();

            return;
        }

        PaymentAllocation::query()->create([
            'payment_id' => $payment->id,
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'amount' => round($amount, 2),
            'allocated_at' => $payment->paid_at ?? now(),
            'allocation_type' => $allocationType,
        ]);
    }

    private function paymentRemainingAmount(Payment $payment): float
    {
        $allocated = (float) $payment->allocations()->sum('amount');

        return max(0, round((float) ($payment->getAttribute('amount') ?? 0) - $allocated, 2));
    }

    private function orderOutstandingAmount(int $orderId): float
    {
        $orderTotal = (float) Order::query()
            ->whereKey($orderId)
            ->value('total');

        $allocated = (float) PaymentAllocation::query()
            ->where('order_id', $orderId)
            ->sum('amount');

        return max(0, round($orderTotal - $allocated, 2));
    }

    private function openOrdersQuery(int $customerId)
    {
        return Order::query()
            ->where('customer_id', $customerId)
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->orderBy('created_at')
            ->orderBy('id')
            ->lockForUpdate();
    }

    private function dispatchOrderPaymentNotifications(Payment $payment): void
    {
        if ((string) ($payment->getAttribute('status') ?? '') !== 'paid') {
            return;
        }

        $payment->loadMissing(['allocations', 'customer', 'order']);
        $allocationsByOrder = $payment->allocations
            ->groupBy('order_id')
            ->map(fn (Collection $rows): float => round((float) $rows->sum('amount'), 2))
            ->filter(fn (float $amount): bool => $amount > self::MONEY_EPSILON);

        if ($allocationsByOrder->isEmpty()) {
            return;
        }

        $orders = Order::query()
            ->with('customer')
            ->withSum('paymentAllocations as allocated_amount', 'amount')
            ->whereIn('id', $allocationsByOrder->keys()->map(fn ($id): int => (int) $id)->all())
            ->get()
            ->keyBy('id');

        foreach ($allocationsByOrder as $orderId => $appliedAmount) {
            /** @var Order|null $order */
            $order = $orders->get((int) $orderId);
            if (! $order) {
                continue;
            }

            $dueAfter = max(0, round((float) $order->total - (float) ($order->allocated_amount ?? 0), 2));
            $dueBefore = max(0, round($dueAfter + (float) $appliedAmount, 2));
            $notification = new OrderPaymentRecorded(
                $order,
                $payment,
                (float) $appliedAmount,
                $dueBefore,
                $dueAfter
            );

            if ($order->customer) {
                $order->customer->notifyNow($notification);
            }

            $this->notifyAdminsNow($notification);
        }
    }

    private function notifyAdminsNow(object $notification): void
    {
        try {
            $admins = User::role('admin')->get();
        } catch (RoleDoesNotExist) {
            return;
        }

        if ($admins->isEmpty()) {
            return;
        }

        Notification::sendNow($admins, $notification);
    }
}
