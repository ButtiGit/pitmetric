<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->timestamps();
        });

        Schema::create('workspace_user', function (Blueprint $table): void {
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['workspace_id', 'user_id']);
        });

        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('category', 30);
            $table->string('manufacturer', 100)->nullable();
            $table->string('model', 100)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('identifier', 100)->nullable();
            $table->string('status', 30)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['workspace_id', 'status']);
        });

        $now = now();

        DB::table('users')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->each(function (object $user) use ($now): void {
                $baseName = trim((string) $user->name);
                $workspaceId = DB::table('workspaces')->insertGetId([
                    'name' => ($baseName !== '' ? $baseName : 'Personal').' Workspace',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('workspace_user')->insert([
                    'workspace_id' => $workspaceId,
                    'user_id' => $user->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('workspace_user');
        Schema::dropIfExists('workspaces');
    }
};
