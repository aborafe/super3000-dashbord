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

        $notificationView = Permission::query()->firstOrCreate([
            'name' => 'notifications.view',
            'guard_name' => $guard,
        ]);

        $notificationSend = Permission::query()->firstOrCreate([
            'name' => 'notifications.send',
            'guard_name' => $guard,
        ]);

        $rolePermissions = [
            'admin' => [$notificationView->name, $notificationSend->name],
            'manager' => [$notificationView->name, $notificationSend->name],
            'staff' => [$notificationView->name],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', $guard)
                ->first();

            if (! $role) {
                continue;
            }

            $role->givePermissionTo($permissions);
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

