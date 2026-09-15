<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_notes', function (Blueprint $table): void {
            $table->string('kind', 32)->default('technical')->after('event_entry_id');
            $table->index(['event_id', 'kind'], 'evt_notes_event_kind_idx');
        });

        Schema::create('event_schedule_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('event_entry_id')->nullable()->constrained('event_entries')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('track_sessions')->nullOnDelete();
            $table->string('label', 120)->nullable();
            $table->string('session_type', 30);
            $table->timestamp('starts_at');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->string('status', 24)->default('planned');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['event_id', 'starts_at'], 'evt_sched_event_start_idx');
            $table->index(['event_id', 'status'], 'evt_sched_event_status_idx');
            $table->unique('session_id', 'evt_sched_session_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_schedule_items');

        Schema::table('event_notes', function (Blueprint $table): void {
            $table->dropIndex('evt_notes_event_kind_idx');
            $table->dropColumn('kind');
        });
    }
};
