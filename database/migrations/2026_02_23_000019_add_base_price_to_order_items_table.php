<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->decimal('base_price', 15, 2)->nullable()->after('price');
            $table->index('base_price');
        });

        DB::table('order_items')
            ->whereNull('base_price')
            ->update([
                'base_price' => DB::raw('price'),
            ]);
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropIndex(['base_price']);
            $table->dropColumn('base_price');
        });
    }
};

