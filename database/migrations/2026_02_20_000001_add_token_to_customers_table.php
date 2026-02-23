<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('customers', 'token')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table): void {
            $table->string('token', 64)->nullable()->after('is_active');
            $table->index('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('customers', 'token')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex(['token']);
            $table->dropColumn('token');
        });
    }
};
