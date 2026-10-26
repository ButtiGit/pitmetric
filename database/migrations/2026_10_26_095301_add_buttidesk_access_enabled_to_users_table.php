<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'buttidesk_access_enabled')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('buttidesk_access_enabled')->default(false);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'buttidesk_access_enabled')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('buttidesk_access_enabled');
            });
        }
    }
};
