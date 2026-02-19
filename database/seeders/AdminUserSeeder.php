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
        $isSafeEnv = app()->environment(['local', 'testing']);
        $email = trim((string) env('ADMIN_SEED_EMAIL', 'admin@super3000.test'));
        $rawPassword = (string) env('ADMIN_SEED_PASSWORD', '');

        if (! $isSafeEnv && $rawPassword === '') {
            $this->command?->warn('AdminUserSeeder skipped: set ADMIN_SEED_PASSWORD for non-local environments.');

            return;
        }

        /** @var \App\Models\User $admin */
        $admin = User::query()->firstOrCreate(
            ['email' => $email !== '' ? $email : 'admin@super3000.test'],
            [
                'name' => 'Admin',
                'password' => $rawPassword !== '' ? $rawPassword : 'password',
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
