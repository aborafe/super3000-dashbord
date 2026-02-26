<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $schema = DB::getSchemaBuilder();

        if (! $schema->hasTable('permissions') || ! $schema->hasTable('roles')) {
            return;
        }

        $guard = 'web';

        $permission = Permission::query()->firstOrCreate([
            'name' => 'payments.create',
            'guard_name' => $guard,
        ]);

        foreach (['admin', 'manager'] as $roleName) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', $guard)
                ->first();

            if (! $role) {
                continue;
            }

            $role->givePermissionTo($permission->name);
        }

        if (class_exists(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive.
    }
};

