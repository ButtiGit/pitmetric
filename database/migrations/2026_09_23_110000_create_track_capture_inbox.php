<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('track_captures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('kind', 40)->default('lap_time');
            $table->string('circuit_name', 120)->nullable();
            $table->foreignId('circuit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('circuit_layout_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('lap_time_ms')->nullable();
            $table->timestamp('occurred_at');
            $table->string('status', 30)->default('ready');
            $table->timestamp('resolved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'occurred_at']);
            $table->index(['workspace_id', 'status']);
        });

        Schema::create('follow_up_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('kind', 60);
            $table->string('subject_key', 160);
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('target_route', 80)->nullable();
            $table->json('context')->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index(['workspace_id', 'kind', 'subject_key'], 'follow_up_subject_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_up_tasks');
        Schema::dropIfExists('track_captures');
    }
};
