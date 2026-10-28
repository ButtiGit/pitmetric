<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('track_sessions', function (Blueprint $table): void {
            $table->json('weather_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('track_sessions', function (Blueprint $table): void {
            $table->dropColumn('weather_snapshot');
        });
    }
};
