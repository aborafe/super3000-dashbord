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

        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('customer_id');
            }

            if (! Schema::hasColumn('orders', 'customer_email')) {
                $table->string('customer_email')->nullable()->after('customer_name');
            }

            if (! Schema::hasColumn('orders', 'customer_phone')) {
                $table->string('customer_phone')->nullable()->after('customer_email');
            }

            if (! Schema::hasColumn('orders', 'customer_whatsapp')) {
                $table->string('customer_whatsapp')->nullable()->after('customer_phone');
            }

            if (! Schema::hasColumn('orders', 'customer_address')) {
                $table->text('customer_address')->nullable()->after('customer_whatsapp');
            }

            if (! Schema::hasColumn('orders', 'customer_notes')) {
                $table->text('customer_notes')->nullable()->after('customer_address');
            }

            if (! Schema::hasColumn('orders', 'shipping_address')) {
                $table->json('shipping_address')->nullable()->after('total');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            $dropColumns = [];

            foreach ([
                'customer_whatsapp',
                'customer_address',
                'customer_notes',
            ] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};

