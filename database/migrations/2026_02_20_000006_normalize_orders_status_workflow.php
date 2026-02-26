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
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'status')) {
            return;
        }

        if (! Schema::hasColumn('orders', 'status_v2')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->string('status_v2', 32)->default('pending');
            });
        }

        DB::table('orders')->update(['status_v2' => 'pending']);

        $mapping = [
            'pending' => 'pending',
            'paid' => 'approved',
            'approved' => 'approved',
            'shipped' => 'shipped',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            'returned' => 'returned',
        ];

        foreach ($mapping as $from => $to) {
            DB::table('orders')
                ->where('status', $from)
                ->update(['status_v2' => $to]);
        }

        $this->dropOrdersStatusCustomerIndex();

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('status');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->renameColumn('status_v2', 'status');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->index(['status', 'customer_id', 'created_at'], 'orders_status_customer_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'status')) {
            return;
        }

        if (! Schema::hasColumn('orders', 'status_legacy')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->string('status_legacy', 32)->default('pending');
            });
        }

        DB::table('orders')->update(['status_legacy' => 'pending']);

        $mapping = [
            'pending' => 'pending',
            'approved' => 'paid',
            'shipped' => 'shipped',
            'delivered' => 'shipped',
            'cancelled' => 'cancelled',
            'returned' => 'cancelled',
            'paid' => 'paid',
        ];

        foreach ($mapping as $from => $to) {
            DB::table('orders')
                ->where('status', $from)
                ->update(['status_legacy' => $to]);
        }

        $this->dropOrdersStatusCustomerIndex();

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('status');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->renameColumn('status_legacy', 'status');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->index(['status', 'customer_id', 'created_at'], 'orders_status_customer_created_index');
        });
    }

    private function dropOrdersStatusCustomerIndex(): void
    {
        try {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropIndex('orders_status_customer_created_index');
            });
        } catch (Throwable) {
            // Index may not exist depending on previous schema versions.
        }
    }
};
