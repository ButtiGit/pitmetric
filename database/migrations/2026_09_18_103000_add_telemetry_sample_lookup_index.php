<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telemetry_samples', function (Blueprint $table): void {
            $table->index(
                ['telemetry_import_id', 'lap_number', 'elapsed_ms'],
                'telemetry_samples_lap_time_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('telemetry_samples', function (Blueprint $table): void {
            $table->dropIndex('telemetry_samples_lap_time_idx');
        });
    }
};
