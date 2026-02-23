<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $userId = User::query()->value('id');

        ActivityLog::factory()
            ->count(20)
            ->state(fn () => ['user_id' => $userId])
            ->create();
    }
}
