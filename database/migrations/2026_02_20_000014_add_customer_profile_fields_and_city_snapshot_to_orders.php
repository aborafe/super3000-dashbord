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
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table): void {
                if (! Schema::hasColumn('customers', 'whatsapp')) {
                    $table->string('whatsapp', 50)->nullable()->after('phone');
                }

                if (! Schema::hasColumn('customers', 'city')) {
                    $table->string('city', 120)->nullable()->after('whatsapp');
                }

                if (! Schema::hasColumn('customers', 'address')) {
                    $table->text('address')->nullable()->after('city');
                }
            });
        }

        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'customer_city')) {
                if (Schema::hasColumn('orders', 'customer_phone')) {
                    $table->string('customer_city', 120)->nullable()->after('customer_phone');
                } else {
                    $table->string('customer_city', 120)->nullable();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'customer_city')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropColumn('customer_city');
            });
        }

        if (Schema::hasTable('customers')) {
            $columnsToDrop = [];

            foreach (['whatsapp', 'city', 'address'] as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $columnsToDrop[] = $column;
                }
            }

            if ($columnsToDrop !== []) {
                Schema::table('customers', function (Blueprint $table) use ($columnsToDrop): void {
                    $table->dropColumn($columnsToDrop);
                });
            }
        }
    }
};
