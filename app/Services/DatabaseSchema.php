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

        $this->ensureWorkspacesTable();
        $this->ensureWorkspaceUserTable();
        $this->ensureVehiclesTable();
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

    private function ensureWorkspacesTable(): void
    {
        if (! Schema::hasTable('workspaces')) {
            Schema::create('workspaces', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 120);
                $table->timestamps();
            });

            return;
        }

        $missingName = ! Schema::hasColumn('workspaces', 'name');
        $missingCreatedAt = ! Schema::hasColumn('workspaces', 'created_at');
        $missingUpdatedAt = ! Schema::hasColumn('workspaces', 'updated_at');

        if (! $missingName && ! $missingCreatedAt && ! $missingUpdatedAt) {
            return;
        }

        Schema::table('workspaces', function (Blueprint $table) use ($missingName, $missingCreatedAt, $missingUpdatedAt): void {
            if ($missingName) {
                $table->string('name', 120)->default('Personal Workspace');
            }

            if ($missingCreatedAt) {
                $table->timestamp('created_at')->nullable();
            }

            if ($missingUpdatedAt) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    private function ensureWorkspaceUserTable(): void
    {
        if (! Schema::hasTable('workspace_user')) {
            Schema::create('workspace_user', function (Blueprint $table): void {
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->primary(['workspace_id', 'user_id']);
            });

            return;
        }

        $missingWorkspaceId = ! Schema::hasColumn('workspace_user', 'workspace_id');
        $missingUserId = ! Schema::hasColumn('workspace_user', 'user_id');
        $missingCreatedAt = ! Schema::hasColumn('workspace_user', 'created_at');
        $missingUpdatedAt = ! Schema::hasColumn('workspace_user', 'updated_at');

        if (! $missingWorkspaceId && ! $missingUserId && ! $missingCreatedAt && ! $missingUpdatedAt) {
            return;
        }

        Schema::table('workspace_user', function (Blueprint $table) use ($missingWorkspaceId, $missingUserId, $missingCreatedAt, $missingUpdatedAt): void {
            // Existing installations may already contain rows. Keep repair columns nullable
            // instead of failing the whole application while Laravel boots.
            if ($missingWorkspaceId) {
                $table->unsignedBigInteger('workspace_id')->nullable();
            }

            if ($missingUserId) {
                $table->unsignedBigInteger('user_id')->nullable();
            }

            if ($missingCreatedAt) {
                $table->timestamp('created_at')->nullable();
            }

            if ($missingUpdatedAt) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    private function ensureVehiclesTable(): void
    {
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

            return;
        }

        $missingWorkspaceId = ! Schema::hasColumn('vehicles', 'workspace_id');
        $missingName = ! Schema::hasColumn('vehicles', 'name');
        $missingCategory = ! Schema::hasColumn('vehicles', 'category');
        $missingManufacturer = ! Schema::hasColumn('vehicles', 'manufacturer');
        $missingModel = ! Schema::hasColumn('vehicles', 'model');
        $missingYear = ! Schema::hasColumn('vehicles', 'year');
        $missingIdentifier = ! Schema::hasColumn('vehicles', 'identifier');
        $missingStatus = ! Schema::hasColumn('vehicles', 'status');
        $missingNotes = ! Schema::hasColumn('vehicles', 'notes');
        $missingCreatedAt = ! Schema::hasColumn('vehicles', 'created_at');
        $missingUpdatedAt = ! Schema::hasColumn('vehicles', 'updated_at');
        $missingDeletedAt = ! Schema::hasColumn('vehicles', 'deleted_at');

        if (
            ! $missingWorkspaceId
            && ! $missingName
            && ! $missingCategory
            && ! $missingManufacturer
            && ! $missingModel
            && ! $missingYear
            && ! $missingIdentifier
            && ! $missingStatus
            && ! $missingNotes
            && ! $missingCreatedAt
            && ! $missingUpdatedAt
            && ! $missingDeletedAt
        ) {
            return;
        }

        Schema::table('vehicles', function (Blueprint $table) use (
            $missingWorkspaceId,
            $missingName,
            $missingCategory,
            $missingManufacturer,
            $missingModel,
            $missingYear,
            $missingIdentifier,
            $missingStatus,
            $missingNotes,
            $missingCreatedAt,
            $missingUpdatedAt,
            $missingDeletedAt,
        ): void {
            if ($missingWorkspaceId) {
                $table->unsignedBigInteger('workspace_id')->nullable();
            }

            if ($missingName) {
                $table->string('name', 100)->default('Vehicle');
            }

            if ($missingCategory) {
                $table->string('category', 30)->default('other');
            }

            if ($missingManufacturer) {
                $table->string('manufacturer', 100)->nullable();
            }

            if ($missingModel) {
                $table->string('model', 100)->nullable();
            }

            if ($missingYear) {
                $table->unsignedSmallInteger('year')->nullable();
            }

            if ($missingIdentifier) {
                $table->string('identifier', 100)->nullable();
            }

            if ($missingStatus) {
                $table->string('status', 30)->default('active');
            }

            if ($missingNotes) {
                $table->text('notes')->nullable();
            }

            if ($missingCreatedAt) {
                $table->timestamp('created_at')->nullable();
            }

            if ($missingUpdatedAt) {
                $table->timestamp('updated_at')->nullable();
            }

            if ($missingDeletedAt) {
                $table->timestamp('deleted_at')->nullable();
            }
        });
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

        if (is_file($database)) {
            return true;
        }

        if (! str_starts_with($database, DIRECTORY_SEPARATOR)) {
            return is_file(base_path($database));
        }

        return false;
    }
}
