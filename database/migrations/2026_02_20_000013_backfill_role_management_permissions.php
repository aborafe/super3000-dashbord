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

        if (! class_exists(Permission::class) || ! class_exists(Role::class)) {
            return;
        }

        $guard = 'web';
        $requiredPermissions = [
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
        ];

        foreach ($requiredPermissions as $permissionName) {
            Permission::query()->firstOrCreate([
                'name' => $permissionName,
                'guard_name' => $guard,
            ]);
        }

        $adminRole = Role::query()
            ->where('name', 'admin')
            ->where('guard_name', $guard)
            ->first();

        if ($adminRole) {
            $adminRole->givePermissionTo($requiredPermissions);
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

