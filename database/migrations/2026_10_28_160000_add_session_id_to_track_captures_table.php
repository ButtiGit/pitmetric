<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('track_captures', function (Blueprint $table): void {
            $table->unsignedBigInteger('session_id')->nullable()->after('circuit_layout_id');
            $table->index(['workspace_id', 'session_id', 'occurred_at'], 'captures_session_activity_idx');
            $table->index(['session_id', 'workspace_id'], 'captures_session_tenant_idx');
            $table->foreign(['session_id', 'workspace_id'], 'captures_session_tenant_fk')
                ->references(['id', 'workspace_id'])->on('track_sessions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('track_captures', function (Blueprint $table): void {
            $table->dropForeign('captures_session_tenant_fk');
            $table->dropIndex('captures_session_activity_idx');
            $table->dropIndex('captures_session_tenant_idx');
            $table->dropColumn('session_id');
        });
    }
};
