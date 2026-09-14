<?php

use Illuminate\Support\Facades\Schema;

it('creates the managed PitMetric schema through migrations', function () {
    foreach ([
        'workspaces',
        'workspace_user',
        'vehicles',
        'component_types',
        'components',
        'usage_metric_types',
        'component_trackers',
        'component_installations',
        'configurations',
        'configuration_versions',
        'configuration_version_components',
        'circuits',
        'circuit_layouts',
        'track_sessions',
        'session_usage_values',
        'usage_batches',
        'component_usage_entries',
        'maintenance_schedules',
        'maintenance_records',
        'tracker_reset_events',
        'expenses',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }
});

it('keeps database activation in the migrated user schema', function () {
    expect(Schema::hasColumn('users', 'database_access_enabled'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'manager_access_enabled'))->toBeFalse();
});
