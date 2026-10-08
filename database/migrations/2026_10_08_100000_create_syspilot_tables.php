<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('syspilot_interventions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150);
            $table->text('request');
            $table->string('client', 120)->default('');
            $table->string('asset', 120)->default('');
            $table->string('status', 16)->default('open');
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('syspilot_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('intervention_id')->constrained('syspilot_interventions')->cascadeOnDelete();
            $table->string('phase', 120);
            $table->string('title', 250);
            $table->text('detail')->nullable();
            $table->string('state', 16)->default('todo');
            $table->text('note')->nullable();
            $table->unsignedSmallInteger('position');
            $table->timestamps();
            $table->index(['intervention_id', 'position']);
        });

        Schema::create('syspilot_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('intervention_id')->constrained('syspilot_interventions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['intervention_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('syspilot_events');
        Schema::dropIfExists('syspilot_steps');
        Schema::dropIfExists('syspilot_interventions');
    }
};
