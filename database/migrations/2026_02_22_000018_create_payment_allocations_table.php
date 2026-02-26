<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasTable('orders')) {
            return;
        }

        Schema::create('payment_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamp('allocated_at')->nullable();
            $table->enum('allocation_type', ['fifo', 'order_first', 'auto_credit', 'migration'])->default('migration');
            $table->timestamps();

            $table->unique(['payment_id', 'order_id'], 'payment_allocations_payment_order_unique');
            $table->index(['customer_id', 'order_id'], 'payment_allocations_customer_order_index');
        });

        $this->backfillHistoricalAllocations();
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }

    private function backfillHistoricalAllocations(): void
    {
        $payments = DB::table('payments')
            ->select(['id', 'order_id', 'customer_id', 'amount', 'status', 'paid_at', 'created_at'])
            ->whereNotNull('order_id')
            ->whereNotNull('customer_id')
            ->orderByRaw('COALESCE(paid_at, created_at)')
            ->orderBy('id')
            ->get();

        if ($payments->isEmpty()) {
            return;
        }

        $orderIds = $payments
            ->pluck('order_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($orderIds === []) {
            return;
        }

        /** @var array<int, float> $orderTotals */
        $orderTotals = DB::table('orders')
            ->whereIn('id', $orderIds)
            ->pluck('total', 'id')
            ->map(fn ($value): float => (float) $value)
            ->all();

        $allocatedByOrder = [];

        /** @var Collection<int, object{id:int,order_id:int,customer_id:int,amount:string,status:string,paid_at:?string,created_at:string}> $payments */
        foreach ($payments as $payment) {
            if ((string) $payment->status !== 'paid') {
                continue;
            }

            $orderId = (int) $payment->order_id;
            $orderTotal = (float) ($orderTotals[$orderId] ?? 0);
            $alreadyAllocated = (float) ($allocatedByOrder[$orderId] ?? 0);
            $orderRemaining = max(0, $orderTotal - $alreadyAllocated);
            $paymentAmount = max(0, (float) $payment->amount);
            $allocatable = min($paymentAmount, $orderRemaining);

            if ($allocatable <= 0) {
                continue;
            }

            DB::table('payment_allocations')->insert([
                'payment_id' => (int) $payment->id,
                'order_id' => $orderId,
                'customer_id' => (int) $payment->customer_id,
                'amount' => round($allocatable, 2),
                'allocated_at' => $payment->paid_at ?? $payment->created_at,
                'allocation_type' => 'migration',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $allocatedByOrder[$orderId] = round($alreadyAllocated + $allocatable, 2);
        }
    }
};

