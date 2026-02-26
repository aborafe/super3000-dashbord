<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (
            ! Schema::hasTable('products')
            || ! Schema::hasTable('product_stocks')
            || ! Schema::hasTable('warehouses')
            || ! Schema::hasTable('settings')
        ) {
            return;
        }

        $defaultWarehouseId = DB::table('settings')
            ->where('key', 'inventory.default_warehouse_id')
            ->value('value');

        $defaultWarehouseId = is_numeric($defaultWarehouseId) ? (int) $defaultWarehouseId : 0;

        if ($defaultWarehouseId <= 0 || ! DB::table('warehouses')->where('id', $defaultWarehouseId)->exists()) {
            $defaultWarehouseId = (int) DB::table('warehouses')
                ->where('is_active', 1)
                ->orderBy('id')
                ->value('id');
        }

        if ($defaultWarehouseId <= 0) {
            $defaultWarehouseId = (int) DB::table('warehouses')->insertGetId([
                'name' => 'Default Warehouse',
                'location' => 'System',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('settings')->updateOrInsert(
            ['key' => 'inventory.default_warehouse_id'],
            ['value' => (string) $defaultWarehouseId, 'updated_at' => now(), 'created_at' => now()]
        );

        DB::table('products')
            ->select('id', 'stock_qty')
            ->orderBy('id')
            ->chunkById(200, function ($products) use ($defaultWarehouseId): void {
                foreach ($products as $product) {
                    $masterQty = (int) DB::table('product_stocks')
                        ->where('product_id', $product->id)
                        ->sum('qty');

                    if ($masterQty <= 0 && (int) $product->stock_qty > 0) {
                        DB::table('product_stocks')->updateOrInsert(
                            [
                                'product_id' => $product->id,
                                'warehouse_id' => $defaultWarehouseId,
                            ],
                            [
                                'qty' => (int) $product->stock_qty,
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );

                        $masterQty = (int) DB::table('product_stocks')
                            ->where('product_id', $product->id)
                            ->sum('qty');
                    }

                    if ((int) $product->stock_qty !== $masterQty) {
                        DB::table('products')
                            ->where('id', $product->id)
                            ->update([
                                'stock_qty' => $masterQty,
                                'updated_at' => now(),
                            ]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: this migration reconciles data and is intentionally irreversible.
    }
};
