<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telemetry_imports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('track_sessions')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('circuit_layout_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_vendor', 40)->default('generic');
            $table->string('source_format', 20);
            $table->string('original_filename', 255);
            $table->string('storage_path', 500);
            $table->char('sha256', 64);
            $table->json('channel_keys')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('sample_count')->default(0);
            $table->unsignedInteger('lap_count')->default(0);
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->foreignId('imported_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('imported_at');
            $table->timestamps();
            $table->index(['workspace_id', 'imported_at']);
            $table->index(['workspace_id', 'session_id']);
            $table->index(['workspace_id', 'driver_id']);
            $table->index(['workspace_id', 'vehicle_id']);
            $table->index(['workspace_id', 'circuit_layout_id']);
        });

        Schema::create('timing_laps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('track_sessions')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('circuit_layout_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('telemetry_import_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('lap_number');
            $table->unsignedBigInteger('lap_time_ms');
            $table->json('sector_times_ms')->nullable();
            $table->unsignedBigInteger('started_offset_ms')->nullable();
            $table->unsignedBigInteger('ended_offset_ms')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->string('source', 30)->default('import');
            $table->timestamps();
            $table->index(['workspace_id', 'lap_time_ms']);
            $table->index(['workspace_id', 'session_id', 'lap_number']);
            $table->index(['workspace_id', 'driver_id', 'lap_time_ms']);
            $table->index(['workspace_id', 'vehicle_id', 'lap_time_ms']);
            $table->index(['workspace_id', 'circuit_layout_id', 'lap_time_ms']);
        });

        Schema::create('telemetry_samples', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('telemetry_import_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->unsignedInteger('lap_number')->nullable();
            $table->unsignedBigInteger('elapsed_ms');
            $table->decimal('distance_meters', 13, 3)->nullable();
            $table->decimal('latitude', 11, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('speed_kmh', 9, 3)->nullable();
            $table->json('channels')->nullable();
            $table->timestamps();
            $table->unique(['telemetry_import_id', 'sequence']);
            $table->index(['telemetry_import_id', 'elapsed_ms']);
            $table->index(['telemetry_import_id', 'lap_number', 'elapsed_ms']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telemetry_samples');
        Schema::dropIfExists('timing_laps');
        Schema::dropIfExists('telemetry_imports');
    }
};
