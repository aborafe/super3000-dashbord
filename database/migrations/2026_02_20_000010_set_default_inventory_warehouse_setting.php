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
        if (! Schema::hasTable('settings') || ! Schema::hasTable('warehouses')) {
            return;
        }

        $existing = DB::table('settings')
            ->where('key', 'inventory.default_warehouse_id')
            ->value('value');

        if ($existing !== null && $existing !== '') {
            return;
        }

        $warehouseId = DB::table('warehouses')
            ->where('is_active', 1)
            ->orderBy('id')
            ->value('id');

        if (! $warehouseId) {
            $warehouseId = DB::table('warehouses')->insertGetId([
                'name' => 'Default Warehouse',
                'location' => 'System',
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('settings')->updateOrInsert(
            ['key' => 'inventory.default_warehouse_id'],
            ['value' => (string) $warehouseId, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')
            ->where('key', 'inventory.default_warehouse_id')
            ->delete();
    }
};
