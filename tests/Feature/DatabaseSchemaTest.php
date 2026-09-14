<?php

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
