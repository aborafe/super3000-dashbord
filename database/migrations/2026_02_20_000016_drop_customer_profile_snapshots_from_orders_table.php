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

        $columnsToDrop = [];

        foreach ([
            'customer_name',
            'customer_email',
            'customer_phone',
            'customer_city',
            'customer_whatsapp',
            'customer_address',
        ] as $column) {
            if (Schema::hasColumn('orders', $column)) {
                $columnsToDrop[] = $column;
            }
        }

        if ($columnsToDrop === []) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) use ($columnsToDrop): void {
            $table->dropColumn($columnsToDrop);
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
            if (! Schema::hasColumn('orders', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('customer_id');
            }

            if (! Schema::hasColumn('orders', 'customer_email')) {
                $table->string('customer_email')->nullable()->after('customer_name');
            }

            if (! Schema::hasColumn('orders', 'customer_phone')) {
                $table->string('customer_phone', 50)->nullable()->after('customer_email');
            }

            if (! Schema::hasColumn('orders', 'customer_city')) {
                $table->string('customer_city', 120)->nullable()->after('customer_phone');
            }

            if (! Schema::hasColumn('orders', 'customer_whatsapp')) {
                $table->string('customer_whatsapp', 50)->nullable()->after('customer_city');
            }

            if (! Schema::hasColumn('orders', 'customer_address')) {
                $table->text('customer_address')->nullable()->after('customer_whatsapp');
            }
        });
    }
};
