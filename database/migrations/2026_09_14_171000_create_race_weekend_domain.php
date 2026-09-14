<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('display_name', 120);
            $table->string('racing_number', 20)->nullable();
            $table->string('licence_reference', 80)->nullable();
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['workspace_id', 'status']);
        });

        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('circuit_layout_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 140);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 30)->default('planned');
            $table->string('championship', 120)->nullable();
            $table->string('round_label', 80)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['workspace_id', 'start_date']);
            $table->index(['workspace_id', 'status']);
        });

        Schema::create('event_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('configuration_version_id')->constrained()->restrictOnDelete();
            $table->string('entry_number', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['event_id', 'vehicle_id']);
            $table->index(['event_id', 'driver_id']);
        });

        Schema::create('event_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('event_entry_id')->nullable()->constrained('event_entries')->nullOnDelete();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->string('priority', 20)->default('normal');
            $table->string('status', 30)->default('todo');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['event_id', 'status']);
            $table->index(['workspace_id', 'status']);
            $table->index('due_at');
        });

        Schema::create('event_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('event_entry_id')->nullable()->constrained('event_entries')->nullOnDelete();
            $table->text('body');
            $table->timestamp('occurred_at');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['event_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_notes');
        Schema::dropIfExists('event_tasks');
        Schema::dropIfExists('event_entries');
        Schema::dropIfExists('events');
        Schema::dropIfExists('drivers');
    }
};
