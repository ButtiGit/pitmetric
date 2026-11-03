<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_entries', fn (Blueprint $t) => $t->unique(['id', 'event_id', 'driver_id', 'workspace_id'], 'entry_contract_context_uq'));
        Schema::create('season_contracts', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $t->foreignId('driver_id');
            $t->foreignId('vehicle_id');
            $t->foreignId('championship_id')->nullable();
            $t->string('season', 40);
            $t->date('starts_on');
            $t->date('ends_on');
            $t->unsignedInteger('initial_races');
            $t->string('status', 20)->default('active');
            $t->unsignedInteger('revision')->default(1);
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['id', 'workspace_id'], 'season_contract_tenant_uq');
            $t->unique(['id', 'driver_id', 'workspace_id'], 'season_contract_driver_uq');
            $t->index(['workspace_id', 'status', 'starts_on', 'ends_on'], 'season_contract_dates_idx');
            $t->foreign(['driver_id', 'workspace_id'], 'contract_driver_fk')->references(['id', 'workspace_id'])->on('drivers')->restrictOnDelete();
            $t->foreign(['vehicle_id', 'workspace_id'], 'contract_vehicle_fk')->references(['id', 'workspace_id'])->on('vehicles')->restrictOnDelete();
            $t->foreign(['championship_id', 'workspace_id'], 'contract_championship_fk')->references(['id', 'workspace_id'])->on('championships')->restrictOnDelete();
        });
        Schema::create('contract_participations', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $t->foreignId('season_contract_id');
            $t->foreignId('event_entry_id');
            $t->foreignId('event_id');
            $t->foreignId('driver_id');
            $t->foreignId('vehicle_id');
            $t->foreignId('championship_id')->nullable();
            $t->foreignId('round_id')->nullable();
            $t->string('status', 20)->default('planned');
            $t->unsignedInteger('revision')->default(1);
            $t->text('notes')->nullable();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('confirmed_at')->nullable();
            $t->string('confirmed_name')->nullable();
            $t->timestamps();
            $t->unique(['id', 'workspace_id'], 'contract_participation_tenant_uq');
            $t->unique(['event_entry_id', 'workspace_id'], 'contract_entry_once_uq');
            $t->unique(['season_contract_id', 'event_id'], 'contract_event_once_uq');
            $t->unique(['season_contract_id', 'round_id'], 'contract_round_once_uq');
            $t->index(['workspace_id', 'season_contract_id', 'status'], 'contract_consumption_idx');
            $t->foreign(['season_contract_id', 'driver_id', 'workspace_id'], 'participation_contract_fk')->references(['id', 'driver_id', 'workspace_id'])->on('season_contracts')->cascadeOnDelete();
            $t->foreign(['event_entry_id', 'event_id', 'driver_id', 'workspace_id'], 'participation_entry_fk')->references(['id', 'event_id', 'driver_id', 'workspace_id'])->on('event_entries')->restrictOnDelete();
            $t->foreign(['vehicle_id', 'workspace_id'], 'participation_vehicle_fk')->references(['id', 'workspace_id'])->on('vehicles')->restrictOnDelete();
            $t->foreign(['round_id', 'championship_id', 'workspace_id'], 'participation_round_fk')->references(['id', 'championship_id', 'workspace_id'])->on('championship_rounds')->restrictOnDelete();
        });
        Schema::create('contract_extensions', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $t->foreignId('season_contract_id');
            $t->unsignedInteger('added_races');
            $t->date('ends_on')->nullable();
            $t->text('reason');
            $t->string('actor_name');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->foreign(['season_contract_id', 'workspace_id'], 'extension_contract_fk')->references(['id', 'workspace_id'])->on('season_contracts')->cascadeOnDelete();
        });
        Schema::create('contract_changes', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $t->foreignId('season_contract_id');
            $t->foreignId('contract_participation_id')->nullable();
            $t->foreign(['contract_participation_id', 'workspace_id'], 'change_participation_fk')->references(['id', 'workspace_id'])->on('contract_participations')->restrictOnDelete();
            $t->string('kind', 40);
            $t->integer('delta')->default(0);
            $t->text('reason');
            $t->string('actor_name');
            $t->json('before_values')->nullable();
            $t->json('after_values')->nullable();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->index(['workspace_id', 'season_contract_id', 'id'], 'contract_history_idx');
            $t->foreign(['season_contract_id', 'workspace_id'], 'change_contract_fk')->references(['id', 'workspace_id'])->on('season_contracts')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_changes');
        Schema::dropIfExists('contract_extensions');
        Schema::dropIfExists('contract_participations');
        Schema::dropIfExists('season_contracts');
        Schema::table('event_entries', fn (Blueprint $t) => $t->dropUnique('entry_contract_context_uq'));
    }
};
