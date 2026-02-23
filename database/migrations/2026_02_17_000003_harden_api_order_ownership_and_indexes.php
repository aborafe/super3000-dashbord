<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'user_id')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->after('order_no')->constrained()->nullOnDelete();
            });
        }

        $this->backfillOrderUsers();

        $this->createIndexIfMissing('orders', 'orders_user_id_idx', 'user_id');
        $this->createIndexIfMissing('products', 'products_category_id_idx', 'category_id');
        $this->createIndexIfMissing('order_items', 'order_items_order_id_idx', 'order_id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('order_items', 'order_items_order_id_idx');
        $this->dropIndexIfExists('products', 'products_category_id_idx');
        $this->dropIndexIfExists('orders', 'orders_user_id_idx');

        if (Schema::hasColumn('orders', 'user_id')) {
            try {
                Schema::table('orders', function (Blueprint $table): void {
                    $table->dropConstrainedForeignId('user_id');
                });
            } catch (Throwable) {
                Schema::table('orders', function (Blueprint $table): void {
                    $table->dropColumn('user_id');
                });
            }
        }
    }

    private function backfillOrderUsers(): void
    {
        if (! Schema::hasColumn('orders', 'user_id')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'UPDATE orders o
                JOIN customers c ON c.id = o.customer_id
                JOIN users u ON u.email = c.email
                SET o.user_id = u.id
                WHERE o.user_id IS NULL'
            );

            return;
        }

        $orders = DB::table('orders')
            ->whereNull('user_id')
            ->select(['id', 'customer_id'])
            ->get();

        if ($orders->isEmpty()) {
            return;
        }

        $customerEmails = DB::table('customers')
            ->whereIn('id', $orders->pluck('customer_id')->unique()->all())
            ->pluck('email', 'id');

        $userIdsByEmail = DB::table('users')
            ->whereIn('email', $customerEmails->filter()->values()->all())
            ->pluck('id', 'email');

        $orders->each(function (object $order) use ($customerEmails, $userIdsByEmail): void {
            $email = $customerEmails->get($order->customer_id);

            if (! $email) {
                return;
            }

            $userId = $userIdsByEmail->get($email);

            if (! $userId) {
                return;
            }

            DB::table('orders')
                ->where('id', $order->id)
                ->update(['user_id' => $userId]);
        });
    }

    private function createIndexIfMissing(string $table, string $indexName, string $column): void
    {
        try {
            if (DB::getDriverName() === 'sqlite') {
                DB::statement("CREATE INDEX IF NOT EXISTS {$indexName} ON {$table} ({$column})");
                return;
            }

            DB::statement("CREATE INDEX {$indexName} ON {$table} ({$column})");
        } catch (Throwable) {
            // index already exists
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        try {
            if (DB::getDriverName() === 'sqlite') {
                DB::statement("DROP INDEX IF EXISTS {$indexName}");
                return;
            }

            DB::statement("DROP INDEX {$indexName} ON {$table}");
        } catch (Throwable) {
            // index not present
        }
    }
};
