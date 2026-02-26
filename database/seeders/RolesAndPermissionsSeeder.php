<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear cached roles and permissions when PermissionRegistrar is available
        if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }

        $permissions = [
            'dashboard.view',

            'products.view',
            'products.create',
            'products.update',
            'products.delete',

            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',

            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',

            'orders.view',
            'orders.update',
            'orders.change_status',

            'notifications.view',
            'notifications.send',

            'payments.view',
            'payments.create',

            'inventory.view',
            'inventory.create',

            'warehouses.view',
            'warehouses.create',
            'warehouses.update',
            'warehouses.delete',

            'users.view',
            'users.create',
            'users.update',

            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',

            'activity_logs.view',

            'reports.view',
            'settings.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Define base roles
        $roles = [
            'admin' => $permissions,
            'manager' => [
                'dashboard.view',
                'products.view', 'products.create', 'products.update',
                'categories.view', 'categories.create', 'categories.update',
                'customers.view', 'customers.create', 'customers.update',
                'orders.view', 'orders.update', 'orders.change_status',
                'notifications.view', 'notifications.send',
                'payments.view', 'payments.create',
                'inventory.view', 'inventory.create',
                'warehouses.view', 'warehouses.create', 'warehouses.update',
                'reports.view',
                'activity_logs.view',
            ],
            'staff' => [
                'dashboard.view',
                'products.view',
                'categories.view',
                'customers.view',
                'orders.view',
                'notifications.view',
                'payments.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            /** @var \Spatie\Permission\Models\Role $role */
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($rolePermissions);
        }
    }
}
