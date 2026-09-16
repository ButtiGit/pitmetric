<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('technical_setups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->json('values');
            $table->string('status', 24)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'vehicle_id', 'status'], 'technical_setups_scope_idx');
        });

        Schema::create('setup_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('session_id')->unique()->constrained('track_sessions')->cascadeOnDelete();
            $table->foreignId('technical_setup_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('configuration_version_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->json('values');
            $table->timestamp('captured_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['workspace_id', 'vehicle_id', 'captured_at'], 'setup_snapshots_scope_idx');
        });

        DB::table('track_sessions')
            ->orderBy('id')
            ->chunkById(100, function ($sessions): void {
                $now = now();
                $rows = [];

                foreach ($sessions as $session) {
                    $rows[] = [
                        'workspace_id' => $session->workspace_id,
                        'session_id' => $session->id,
                        'technical_setup_id' => null,
                        'vehicle_id' => $session->vehicle_id,
                        'configuration_version_id' => $session->configuration_version_id,
                        'name' => 'Legacy / unspecified setup',
                        'values' => json_encode([], JSON_THROW_ON_ERROR),
                        'captured_at' => $session->started_at ?? $now,
                        'created_by' => $session->created_by,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('setup_snapshots')->insert($rows);
                }
            }, 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('setup_snapshots');
        Schema::dropIfExists('technical_setups');
    }
};
