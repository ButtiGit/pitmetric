<?php

use App\Models\Vehicle;
use App\Services\DatabaseSchema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

it('repairs the managed PitMetric schema idempotently', function () {
    Schema::dropIfExists('vehicles');
    Schema::dropIfExists('workspace_user');
    Schema::dropIfExists('workspaces');

    if (Schema::hasColumn('users', 'database_access_enabled')) {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('database_access_enabled');
        });
    }

    if (! Schema::hasColumn('users', 'manager_access_enabled')) {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('manager_access_enabled')->default(true);
        });
    }

    $schema = app(DatabaseSchema::class);
    $schema->ensure();
    $schema->ensure();

    expect(Schema::hasTable('workspaces'))->toBeTrue()
        ->and(Schema::hasTable('workspace_user'))->toBeTrue()
        ->and(Schema::hasTable('vehicles'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'database_access_enabled'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'manager_access_enabled'))->toBeFalse();
});

it('repairs workspace tables that already exist with an incomplete schema', function () {
    Schema::dropIfExists('vehicles');
    Schema::dropIfExists('workspace_user');
    Schema::dropIfExists('workspaces');

    Schema::create('workspaces', function (Blueprint $table): void {
        $table->id();
    });

    Schema::create('workspace_user', function (Blueprint $table): void {
        $table->id();
    });

    Schema::create('vehicles', function (Blueprint $table): void {
        $table->id();
    });

    $schema = app(DatabaseSchema::class);
    $schema->ensureWorkspaceDomain();
    $schema->ensureWorkspaceDomain();

    expect(Schema::hasColumn('workspaces', 'name'))->toBeTrue()
        ->and(Schema::hasColumn('workspaces', 'created_at'))->toBeTrue()
        ->and(Schema::hasColumn('workspaces', 'updated_at'))->toBeTrue()
        ->and(Schema::hasColumn('workspace_user', 'workspace_id'))->toBeTrue()
        ->and(Schema::hasColumn('workspace_user', 'user_id'))->toBeTrue()
        ->and(Schema::hasColumn('workspace_user', 'created_at'))->toBeTrue()
        ->and(Schema::hasColumn('workspace_user', 'updated_at'))->toBeTrue()
        ->and(Schema::hasColumn('vehicles', 'workspace_id'))->toBeTrue()
        ->and(Schema::hasColumn('vehicles', 'name'))->toBeTrue()
        ->and(Schema::hasColumn('vehicles', 'category'))->toBeTrue()
        ->and(Schema::hasColumn('vehicles', 'manufacturer'))->toBeTrue()
        ->and(Schema::hasColumn('vehicles', 'model'))->toBeTrue()
        ->and(Schema::hasColumn('vehicles', 'year'))->toBeTrue()
        ->and(Schema::hasColumn('vehicles', 'identifier'))->toBeTrue()
        ->and(Schema::hasColumn('vehicles', 'status'))->toBeTrue()
        ->and(Schema::hasColumn('vehicles', 'notes'))->toBeTrue()
        ->and(Schema::hasColumn('vehicles', 'created_at'))->toBeTrue()
        ->and(Schema::hasColumn('vehicles', 'updated_at'))->toBeTrue()
        ->and(Schema::hasColumn('vehicles', 'deleted_at'))->toBeTrue()
        ->and(Vehicle::query()->count())->toBe(0);
});
