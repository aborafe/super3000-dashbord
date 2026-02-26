<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table): void {
            if (Schema::hasColumn('payments', 'order_id')) {
                $table->foreignId('order_id')->nullable()->change();
            }

            if (! Schema::hasColumn('payments', 'customer_id')) {
                $table->foreignId('customer_id')
                    ->nullable()
                    ->after('order_id')
                    ->constrained('customers')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('payments', 'source')) {
                $table->string('source', 32)->default('order_edit')->after('status');
            }

            if (! Schema::hasColumn('payments', 'notes')) {
                $table->text('notes')->nullable()->after('source');
            }

            if (! Schema::hasColumn('payments', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('notes')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        $paymentsToBackfill = DB::table('payments')
            ->select(['payments.id', 'orders.customer_id'])
            ->leftJoin('orders', 'orders.id', '=', 'payments.order_id')
            ->whereNull('payments.customer_id')
            ->whereNotNull('payments.order_id')
            ->get();

        foreach ($paymentsToBackfill as $payment) {
            if (! $payment->customer_id) {
                continue;
            }

            DB::table('payments')
                ->where('id', (int) $payment->id)
                ->update(['customer_id' => (int) $payment->customer_id]);
        }

        DB::table('payments')
            ->whereNull('source')
            ->update(['source' => 'migration']);

        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['customer_id', 'status', 'paid_at'], 'payments_customer_status_paid_at_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table): void {
            try {
                $table->dropIndex('payments_customer_status_paid_at_index');
            } catch (Throwable) {
                // Index may not exist.
            }

            if (Schema::hasColumn('payments', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }

            if (Schema::hasColumn('payments', 'notes')) {
                $table->dropColumn('notes');
            }

            if (Schema::hasColumn('payments', 'source')) {
                $table->dropColumn('source');
            }

            if (Schema::hasColumn('payments', 'customer_id')) {
                $table->dropConstrainedForeignId('customer_id');
            }
        });
    }
};
