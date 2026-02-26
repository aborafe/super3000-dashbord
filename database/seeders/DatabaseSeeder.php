<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
        ]);

        if (! app()->environment(['local', 'testing'])) {
            $this->command?->info('Skipping demo/reference seeders outside local/testing environment.');

            return;
        }

        $this->call([
            ReferenceDataSeeder::class,
            DemoOrdersSeeder::class,
            ActivityLogSeeder::class,
        ]);
    }
}
