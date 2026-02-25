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
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasTable('products')) {
            return;
        }

        $databaseName = DB::getDatabaseName();
        if (! is_string($databaseName) || $databaseName === '') {
            return;
        }

        $idColumn = DB::table('information_schema.COLUMNS')
            ->select(['COLUMN_NAME', 'EXTRA'])
            ->where('TABLE_SCHEMA', $databaseName)
            ->where('TABLE_NAME', 'products')
            ->where('COLUMN_NAME', 'id')
            ->first();

        if (! $idColumn) {
            return;
        }

        $primaryKeyColumns = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select(['COLUMN_NAME'])
            ->where('TABLE_SCHEMA', $databaseName)
            ->where('TABLE_NAME', 'products')
            ->where('CONSTRAINT_NAME', 'PRIMARY')
            ->orderBy('ORDINAL_POSITION')
            ->pluck('COLUMN_NAME')
            ->all();

        if ($primaryKeyColumns === []) {
            DB::statement('ALTER TABLE `products` ADD PRIMARY KEY (`id`)');
        }

        $hasAutoIncrement = str_contains(strtolower((string) $idColumn->EXTRA), 'auto_increment');
        if (! $hasAutoIncrement) {
            DB::statement('ALTER TABLE `products` MODIFY COLUMN `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is a production hotfix and is intentionally non-reversible.
    }
};
