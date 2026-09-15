<?php

use Illuminate\Support\Facades\Schema;

it('creates the managed PitMetric schema through deterministic migrations', function () {
    foreach ([
        'workspaces',
        'workspace_user',
        'team_invitations',
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
        'drivers',
        'events',
        'event_entries',
        'event_tasks',
        'event_notes',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeTrue();
    }

    expect(Schema::hasColumn('track_sessions', 'event_id'))->toBeTrue()
        ->and(Schema::hasColumn('track_sessions', 'event_entry_id'))->toBeTrue()
        ->and(Schema::hasColumn('expenses', 'event_id'))->toBeTrue()
        ->and(Schema::hasColumn('maintenance_records', 'event_id'))->toBeTrue()
        ->and(Schema::hasColumns('workspace_user', ['role', 'status', 'joined_at']))->toBeTrue();
});

it('keeps database activation in the migrated user schema', function () {
    expect(Schema::hasColumn('users', 'database_access_enabled'))->toBeTrue()
        ->and(Schema::hasColumn('users', 'manager_access_enabled'))->toBeFalse();
});

it('does not keep a runtime schema repair service', function () {
    expect(file_exists(app_path('Services/DatabaseSchema.php')))->toBeFalse();

    foreach ([
        '2026_09_12_130000_create_workspaces_and_vehicles_tables.php',
        '2026_09_13_190000_add_manager_access_to_users_table.php',
        '2026_09_13_200000_replace_manager_access_with_database_access.php',
    ] as $migration) {
        expect(file_get_contents(database_path('migrations/'.$migration)))->not->toContain('DatabaseSchema');
    }
});
