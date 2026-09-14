<?php

namespace App\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class DatabaseSchema
{
    public function ensure(): void
    {
        if (! $this->canInspectDatabase() || ! Schema::hasTable('users')) {
            return;
        }

        $this->ensureWorkspaceDomain();
        $this->ensureDatabaseAccess();
    }

    public function ensureWorkspaceDomain(): void
    {
        if (! $this->canInspectDatabase() || ! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasTable('workspaces')) {
            Schema::create('workspaces', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 120);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('workspace_user')) {
            Schema::create('workspace_user', function (Blueprint $table): void {
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->primary(['workspace_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('vehicles')) {
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
        }
    }

    public function ensureDatabaseAccess(): void
    {
        if (! $this->canInspectDatabase() || ! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'database_access_enabled')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('database_access_enabled')->default(false);
            });
        }

        if (Schema::hasColumn('users', 'manager_access_enabled')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('manager_access_enabled');
            });
        }
    }

    private function canInspectDatabase(): bool
    {
        if (config('database.default') !== 'sqlite') {
            return true;
        }

        $database = config('database.connections.sqlite.database');

        if (! is_string($database) || $database === '' || $database === ':memory:') {
            return true;
        }

        return is_file($database);
    }
}
