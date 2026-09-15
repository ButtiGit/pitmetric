<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_user', function (Blueprint $table): void {
            $table->string('role', 32)->default('owner')->after('user_id');
            $table->string('status', 24)->default('active')->after('role');
            $table->timestamp('joined_at')->nullable()->after('status');
            $table->index(['user_id', 'status']);
            $table->index(['workspace_id', 'role', 'status']);
        });

        DB::table('workspace_user')
            ->whereNull('joined_at')
            ->update([
                'role' => 'owner',
                'status' => 'active',
                'joined_at' => DB::raw('COALESCE(created_at, CURRENT_TIMESTAMP)'),
            ]);

        Schema::create('team_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->string('role', 32);
            $table->string('status', 24)->default('pending');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
            $table->index(['email', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_invitations');

        Schema::table('workspace_user', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['workspace_id', 'role', 'status']);
            $table->dropColumn(['role', 'status', 'joined_at']);
        });
    }
};
