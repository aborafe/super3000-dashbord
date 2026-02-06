<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var \App\Models\User $admin */
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@super3000.test'],
            [
                'name' => 'Admin',
                'password' => 'password',
            ]
        );

        if (class_exists(Role::class)) {
            $role = Role::query()->where('name', 'admin')->first();

            if ($role) {
                $admin->assignRole($role);
            }
        }
    }
}
