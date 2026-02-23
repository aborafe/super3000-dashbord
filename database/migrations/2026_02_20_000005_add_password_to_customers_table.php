<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        if (! Schema::hasColumn('customers', 'password')) {
            Schema::table('customers', function (Blueprint $table): void {
                $table->string('password')->nullable()->after('phone');
            });
        }

        DB::table('customers')
            ->select(['id', 'phone', 'password'])
            ->whereNull('password')
            ->whereNotNull('phone')
            ->orderBy('id')
            ->chunkById(100, function ($customers): void {
                foreach ($customers as $customer) {
                    $phone = trim((string) $customer->phone);

                    if ($phone === '') {
                        continue;
                    }

                    $normalizedPhone = preg_replace('/\D+/', '', $phone) ?? '';
                    $credential = $normalizedPhone !== '' ? $normalizedPhone : $phone;

                    DB::table('customers')
                        ->where('id', $customer->id)
                        ->update([
                            'password' => Hash::make($credential),
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('customers') || ! Schema::hasColumn('customers', 'password')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn('password');
        });
    }
};
