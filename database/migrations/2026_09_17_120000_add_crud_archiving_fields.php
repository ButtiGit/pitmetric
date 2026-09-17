<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('circuits', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('country');
            $table->index(['workspace_id', 'is_active']);
        });

        Schema::table('track_sessions', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->after('finalized_at');
            $table->index(['workspace_id', 'archived_at']);
        });

        Schema::table('event_entries', function (Blueprint $table): void {
            $table->timestamp('archived_at')->nullable()->after('entry_number');
            $table->index(['event_id', 'archived_at']);
        });
    }

    public function down(): void
    {
        Schema::table('event_entries', function (Blueprint $table): void {
            $table->dropIndex(['event_id', 'archived_at']);
            $table->dropColumn('archived_at');
        });

        Schema::table('track_sessions', function (Blueprint $table): void {
            $table->dropIndex(['workspace_id', 'archived_at']);
            $table->dropColumn('archived_at');
        });

        Schema::table('circuits', function (Blueprint $table): void {
            $table->dropIndex(['workspace_id', 'is_active']);
            $table->dropColumn('is_active');
        });
    }
};
