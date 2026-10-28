<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_tyre_readings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('track_sessions')->cascadeOnDelete();
            $table->string('phase', 8);
            $table->json('pressures');
            $table->timestamp('measured_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['workspace_id', 'session_id', 'measured_at'], 'session_tyre_readings_history_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_tyre_readings');
    }
};
