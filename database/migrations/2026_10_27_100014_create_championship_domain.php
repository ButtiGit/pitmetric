<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('championships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name', 140);
            $table->string('season', 40);
            $table->string('category', 80)->nullable();
            $table->string('status', 20)->default('planned');
            $table->text('notes')->nullable();
            $table->json('points');
            $table->unsignedInteger('pole_bonus')->default(0);
            $table->unsignedInteger('fastest_lap_bonus')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['id', 'workspace_id'], 'championship_tenant_uq');
            $table->index(['workspace_id', 'season']);
        });
        Schema::create('championship_rounds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('championship_id');
            $table->foreignId('event_id')->nullable();
            $table->unsignedInteger('number');
            $table->string('name', 140);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('venue', 140)->nullable();
            $table->string('status', 20)->default('planned');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['championship_id', 'number']);
            $table->unique(['id', 'championship_id', 'workspace_id'], 'round_championship_tenant_uq');
            $table->foreign(['championship_id', 'workspace_id'], 'round_championship_fk')->references(['id', 'workspace_id'])->on('championships')->cascadeOnDelete();
            $table->foreign(['event_id', 'workspace_id'], 'round_event_fk')->references(['id', 'workspace_id'])->on('events')->restrictOnDelete();
        });
        Schema::create('championship_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('championship_id');
            $table->foreignId('driver_id');
            $table->string('entry_number', 20)->nullable();
            $table->string('team_name', 120)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->unique(['championship_id', 'driver_id']);
            $table->unique(['id', 'championship_id', 'workspace_id'], 'participant_championship_tenant_uq');
            $table->foreign(['championship_id', 'workspace_id'], 'participant_championship_fk')->references(['id', 'workspace_id'])->on('championships')->cascadeOnDelete();
            $table->foreign(['driver_id', 'workspace_id'], 'participant_driver_fk')->references(['id', 'workspace_id'])->on('drivers')->restrictOnDelete();
        });
        Schema::create('championship_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('championship_id');
            $table->foreignId('round_id');
            $table->foreignId('participant_id');
            $table->unsignedInteger('position')->nullable();
            $table->string('status', 20)->default('classified');
            $table->boolean('pole')->default(false);
            $table->boolean('fastest_lap')->default(false);
            $table->integer('adjustment')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['round_id', 'participant_id']);
            $table->foreign(['round_id', 'championship_id', 'workspace_id'], 'result_round_fk')->references(['id', 'championship_id', 'workspace_id'])->on('championship_rounds')->cascadeOnDelete();
            $table->foreign(['participant_id', 'championship_id', 'workspace_id'], 'result_participant_fk')->references(['id', 'championship_id', 'workspace_id'])->on('championship_participants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('championship_results');
        Schema::dropIfExists('championship_participants');
        Schema::dropIfExists('championship_rounds');
        Schema::dropIfExists('championships');
    }
};
