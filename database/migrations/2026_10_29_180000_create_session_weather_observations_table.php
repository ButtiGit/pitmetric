<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_weather_observations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->constrained('track_sessions')->cascadeOnDelete();
            $table->json('values');
            $table->boolean('is_reset')->default(false);
            $table->timestamp('measured_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['workspace_id', 'session_id', 'id'], 'session_weather_observations_history_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_weather_observations');
    }
};
