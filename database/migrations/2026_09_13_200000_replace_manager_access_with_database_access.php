<?php

use App\Services\DatabaseSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        app(DatabaseSchema::class)->ensureDatabaseAccess();
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'manager_access_enabled')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('manager_access_enabled')->default(true);
            });
        }

        if (Schema::hasColumn('users', 'database_access_enabled')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('database_access_enabled');
            });
        }
    }
};
