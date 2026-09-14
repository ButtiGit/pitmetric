<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'manager_access_enabled')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('manager_access_enabled')->default(true);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'manager_access_enabled')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('manager_access_enabled');
        });
    }
};
