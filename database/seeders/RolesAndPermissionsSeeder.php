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

            'orders.view',
            'orders.create',
            'orders.update',
            'orders.delete',
            'orders.change_status',

            'warehouses.view',
            'warehouses.create',
            'warehouses.update',
            'warehouses.delete',
            'warehouses.manage_stock',
            'warehouses.transfer',

            'partners.view',
            'partners.create',
            'partners.update',
            'partners.delete',

            'employees.view',
            'employees.create',
            'employees.update',
            'employees.delete',

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
                'orders.view', 'orders.create', 'orders.update', 'orders.change_status',
                'warehouses.view', 'warehouses.manage_stock', 'warehouses.transfer',
                'partners.view', 'partners.create', 'partners.update',
                'employees.view',
                'reports.view',
            ],
            'staff' => [
                'dashboard.view',
                'products.view',
                'orders.view', 'orders.create',
                'partners.view',
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
