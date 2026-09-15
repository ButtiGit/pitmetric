<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('database_access_enabled')->default(false);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('manager_access_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('manager_access_enabled')->default(true);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('database_access_enabled');
        });
    }
};
