<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('track_sessions', function (Blueprint $table): void {
            $table->foreignId('event_id')->nullable()->after('workspace_id')->constrained('events')->nullOnDelete();
            $table->foreignId('event_entry_id')->nullable()->after('event_id')->constrained('event_entries')->nullOnDelete();
            $table->index(['event_id', 'started_at']);
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->foreignId('event_id')->nullable()->after('workspace_id')->constrained('events')->nullOnDelete();
            $table->index(['event_id', 'occurred_at']);
        });

        Schema::table('maintenance_records', function (Blueprint $table): void {
            $table->foreignId('event_id')->nullable()->after('workspace_id')->constrained('events')->nullOnDelete();
            $table->index(['event_id', 'performed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table): void {
            $table->dropIndex(['event_id', 'performed_at']);
            $table->dropConstrainedForeignId('event_id');
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropIndex(['event_id', 'occurred_at']);
            $table->dropConstrainedForeignId('event_id');
        });

        Schema::table('track_sessions', function (Blueprint $table): void {
            $table->dropIndex(['event_id', 'started_at']);
            $table->dropConstrainedForeignId('event_entry_id');
            $table->dropConstrainedForeignId('event_id');
        });
    }
};
