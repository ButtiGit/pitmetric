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

        Schema::table('event_entries', function (Blueprint $table): void {
            $table->string('status', 30)->default('active')->after('configuration_version_id');
            $table->index(['workspace_id', 'status']);
        });

        Schema::table('event_tasks', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('event_notes', function (Blueprint $table): void {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('event_notes', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('event_tasks', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });

        Schema::table('event_entries', function (Blueprint $table): void {
            $table->dropIndex(['workspace_id', 'status']);
            $table->dropColumn('status');
        });

        Schema::table('circuits', function (Blueprint $table): void {
            $table->dropIndex(['workspace_id', 'is_active']);
            $table->dropColumn('is_active');
        });
    }
};
