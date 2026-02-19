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
        $this->addSoftDeleteColumn('customers');
        $this->addSoftDeleteColumn('products');
        $this->addSoftDeleteColumn('categories');

        $this->addCustomerEmailAndPhoneIndexes();
        $this->addNotificationUnreadIndexes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropCustomerEmailAndPhoneIndexes();
        $this->dropNotificationUnreadIndexes();

        $this->dropSoftDeleteColumn('customers');
        $this->dropSoftDeleteColumn('products');
        $this->dropSoftDeleteColumn('categories');
    }

    private function addSoftDeleteColumn(string $table): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'deleted_at')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->softDeletes();
        });
    }

    private function dropSoftDeleteColumn(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'deleted_at')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint): void {
            $blueprint->dropSoftDeletes();
        });
    }

    private function addCustomerEmailAndPhoneIndexes(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        $emailHasDuplicates = $this->hasDuplicates('customers', 'email');
        $phoneHasDuplicates = $this->hasDuplicates('customers', 'phone');

        if (Schema::hasColumn('customers', 'email')) {
            if ($emailHasDuplicates) {
                $this->addIndex('customers', ['email'], 'customers_email_index');
            } else {
                $this->addUnique('customers', ['email'], 'customers_email_unique');
            }
        }

        if (Schema::hasColumn('customers', 'phone')) {
            if ($phoneHasDuplicates) {
                $this->addIndex('customers', ['phone'], 'customers_phone_index');
            } else {
                $this->addUnique('customers', ['phone'], 'customers_phone_unique');
            }
        }
    }

    private function dropCustomerEmailAndPhoneIndexes(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        $this->dropIndex('customers', 'customers_email_unique');
        $this->dropIndex('customers', 'customers_phone_unique');
        $this->dropIndex('customers', 'customers_email_index');
        $this->dropIndex('customers', 'customers_phone_index');
    }

    private function addNotificationUnreadIndexes(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $this->addIndex(
            'notifications',
            ['notifiable_type', 'notifiable_id', 'read_at'],
            'notifications_notifiable_read_at_index'
        );

        $this->addIndex(
            'notifications',
            ['notifiable_type', 'notifiable_id', 'created_at'],
            'notifications_notifiable_created_at_index'
        );
    }

    private function dropNotificationUnreadIndexes(): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $this->dropIndex('notifications', 'notifications_notifiable_read_at_index');
        $this->dropIndex('notifications', 'notifications_notifiable_created_at_index');
    }

    private function hasDuplicates(string $table, string $column): bool
    {
        return DB::table($table)
            ->whereNotNull($column)
            ->select($column)
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->exists();
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

    private function dropIndex(string $table, string $name): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($name): void {
                $blueprint->dropIndex($name);
            });
        } catch (Throwable) {
            // Index may be absent in some environments.
        }
    }
};
