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
        if (! Schema::hasTable('customers')) {
            return;
        }

        $this->deduplicateColumn('email');
        $this->deduplicateColumn('phone');

        if (Schema::hasColumn('customers', 'email')) {
            $this->dropIndex('customers_email_index');
            $this->addUnique('customers', ['email'], 'customers_email_unique');
        }

        if (Schema::hasColumn('customers', 'phone')) {
            $this->dropIndex('customers_phone_index');
            $this->addUnique('customers', ['phone'], 'customers_phone_unique');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        $this->dropIndex('customers_email_unique');
        $this->dropIndex('customers_phone_unique');

        if (Schema::hasColumn('customers', 'email')) {
            $this->addIndex('customers', ['email'], 'customers_email_index');
        }

        if (Schema::hasColumn('customers', 'phone')) {
            $this->addIndex('customers', ['phone'], 'customers_phone_index');
        }
    }

    private function deduplicateColumn(string $column): void
    {
        if (! Schema::hasColumn('customers', $column)) {
            return;
        }

        $duplicates = DB::table('customers')
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->select($column)
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->pluck($column);

        foreach ($duplicates as $value) {
            $ids = DB::table('customers')
                ->where($column, $value)
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $duplicateIds = array_slice($ids, 1);

            foreach ($duplicateIds as $id) {
                $replacement = $column === 'email'
                    ? $this->dedupeEmail((string) $value, (int) $id)
                    : $this->dedupePhone((string) $value, (int) $id);

                DB::table('customers')
                    ->where('id', $id)
                    ->update([$column => $replacement]);
            }
        }
    }

    private function dedupeEmail(string $email, int $id): string
    {
        $parts = explode('@', $email, 2);
        $local = trim($parts[0] ?? '');
        $domain = trim($parts[1] ?? '');

        if ($local === '' || $domain === '') {
            return 'duplicate+' . $id . '@invalid.local';
        }

        $suffix = '+dup' . $id;
        $maxLocalLength = max(1, 255 - strlen($domain) - strlen($suffix) - 1);
        $local = substr($local, 0, $maxLocalLength);

        return $local . $suffix . '@' . $domain;
    }

    private function dedupePhone(string $phone, int $id): string
    {
        $suffix = '-dup' . $id;
        $maxBaseLength = max(1, 255 - strlen($suffix));

        return substr($phone, 0, $maxBaseLength) . $suffix;
    }

    /**
     * @param array<int, string> $columns
     */
    private function addUnique(string $table, array $columns, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $name): void {
                $blueprint->unique($columns, $name);
            });
        } catch (Throwable) {
            // Already exists or unsupported in current environment.
        }
    }

    /**
     * @param array<int, string> $columns
     */
    private function addIndex(string $table, array $columns, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $name): void {
                $blueprint->index($columns, $name);
            });
        } catch (Throwable) {
            // Already exists or unsupported in current environment.
        }
    }

    private function dropIndex(string $name): void
    {
        try {
            Schema::table('customers', function (Blueprint $blueprint) use ($name): void {
                $blueprint->dropIndex($name);
            });
        } catch (Throwable) {
            // Index may not exist.
        }
    }
};
