<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trackside_captures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('capture_type', 40)->default('lap_time');
            $table->timestamp('captured_at');
            $table->unsignedBigInteger('lap_time_ms')->nullable();
            $table->string('circuit_name', 120)->nullable();
            $table->string('driver_name', 120)->nullable();
            $table->string('vehicle_name', 120)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('circuit_layout_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('track_sessions')->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status', 'captured_at']);
            $table->index(['workspace_id', 'circuit_name']);
            $table->index(['workspace_id', 'capture_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trackside_captures');
    }
};
