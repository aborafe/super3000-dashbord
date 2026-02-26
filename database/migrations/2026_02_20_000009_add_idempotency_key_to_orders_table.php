<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        if (! Schema::hasColumn('orders', 'idempotency_key')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->string('idempotency_key', 120)->nullable()->after('order_no');
            });
        }

        try {
            Schema::table('orders', function (Blueprint $table): void {
                $table->unique(['customer_id', 'idempotency_key'], 'orders_customer_idempotency_unique');
            });
        } catch (Throwable) {
            // Index may already exist in some environments.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        try {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropUnique('orders_customer_idempotency_unique');
            });
        } catch (Throwable) {
            // Index may not exist.
        }

        if (Schema::hasColumn('orders', 'idempotency_key')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropColumn('idempotency_key');
            });
        }
    }
};
