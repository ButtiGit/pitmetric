<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

function seedTenantIntegrityGraph(string $suffix): array
{
    $now = now();
    $userId = DB::table('users')->insertGetId([
        'name' => 'Tenant '.$suffix,
        'email' => 'tenant-'.$suffix.'@example.test',
        'email_verified_at' => $now,
        'password' => 'test-password',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $workspaceId = DB::table('workspaces')->insertGetId([
        'name' => 'Workspace '.$suffix,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    DB::table('workspace_user')->insert([
        'workspace_id' => $workspaceId,
        'user_id' => $userId,
        'role' => 'owner',
        'status' => 'active',
        'joined_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $vehicleId = DB::table('vehicles')->insertGetId([
        'workspace_id' => $workspaceId,
        'name' => 'Vehicle '.$suffix,
        'category' => 'kart',
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $componentTypeId = DB::table('component_types')->insertGetId([
        'workspace_id' => $workspaceId,
        'name' => 'Engine '.$suffix,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $componentId = DB::table('components')->insertGetId([
        'workspace_id' => $workspaceId,
        'component_type_id' => $componentTypeId,
        'name' => 'Component '.$suffix,
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $metricId = (int) DB::table('usage_metric_types')->where('key', 'runtime')->value('id');
    $trackerId = DB::table('component_trackers')->insertGetId([
        'component_id' => $componentId,
        'usage_metric_type_id' => $metricId,
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $maintenanceScheduleId = DB::table('maintenance_schedules')->insertGetId([
        'workspace_id' => $workspaceId,
        'component_tracker_id' => $trackerId,
        'name' => 'Service '.$suffix,
        'interval_value' => 3600,
        'is_active' => true,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $configurationId = DB::table('configurations')->insertGetId([
        'workspace_id' => $workspaceId,
        'vehicle_id' => $vehicleId,
        'name' => 'Race '.$suffix,
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $configurationVersionId = DB::table('configuration_versions')->insertGetId([
        'configuration_id' => $configurationId,
        'version_number' => 1,
        'created_by' => $userId,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $driverId = DB::table('drivers')->insertGetId([
        'workspace_id' => $workspaceId,
        'display_name' => 'Driver '.$suffix,
        'status' => 'active',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $eventId = DB::table('events')->insertGetId([
        'workspace_id' => $workspaceId,
        'name' => 'Weekend '.$suffix,
        'start_date' => $now->toDateString(),
        'end_date' => $now->toDateString(),
        'status' => 'planned',
        'created_by' => $userId,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $eventEntryId = DB::table('event_entries')->insertGetId([
        'workspace_id' => $workspaceId,
        'event_id' => $eventId,
        'driver_id' => $driverId,
        'vehicle_id' => $vehicleId,
        'configuration_version_id' => $configurationVersionId,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $sessionId = DB::table('track_sessions')->insertGetId([
        'workspace_id' => $workspaceId,
        'event_id' => $eventId,
        'event_entry_id' => $eventEntryId,
        'vehicle_id' => $vehicleId,
        'configuration_version_id' => $configurationVersionId,
        'session_type' => 'practice',
        'started_at' => $now,
        'status' => 'finalized',
        'finalized_at' => $now,
        'created_by' => $userId,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $technicalSetupId = DB::table('technical_setups')->insertGetId([
        'workspace_id' => $workspaceId,
        'vehicle_id' => $vehicleId,
        'name' => 'Dry '.$suffix,
        'values' => json_encode([], JSON_THROW_ON_ERROR),
        'status' => 'active',
        'created_by' => $userId,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $setupSnapshotId = DB::table('setup_snapshots')->insertGetId([
        'workspace_id' => $workspaceId,
        'session_id' => $sessionId,
        'technical_setup_id' => $technicalSetupId,
        'vehicle_id' => $vehicleId,
        'configuration_version_id' => $configurationVersionId,
        'name' => 'Snapshot '.$suffix,
        'values' => json_encode([], JSON_THROW_ON_ERROR),
        'captured_at' => $now,
        'created_by' => $userId,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $workOrderId = DB::table('maintenance_work_orders')->insertGetId([
        'workspace_id' => $workspaceId,
        'maintenance_schedule_id' => $maintenanceScheduleId,
        'title' => 'Inspect '.$suffix,
        'priority' => 'normal',
        'status' => 'todo',
        'created_by' => $userId,
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    return [
        'workspace' => $workspaceId,
        'vehicle' => $vehicleId,
        'component_type' => $componentTypeId,
        'component' => $componentId,
        'maintenance_schedule' => $maintenanceScheduleId,
        'event' => $eventId,
        'driver' => $driverId,
        'event_entry' => $eventEntryId,
        'session' => $sessionId,
        'technical_setup' => $technicalSetupId,
        'setup_snapshot' => $setupSnapshotId,
        'work_order' => $workOrderId,
    ];
}

it('rejects a component type from another workspace at database level', function () {
    $first = seedTenantIntegrityGraph('component-a');
    $second = seedTenantIntegrityGraph('component-b');

    expect(fn () => DB::table('components')->where('id', $first['component'])->update([
        'component_type_id' => $second['component_type'],
    ]))->toThrow(QueryException::class);
});

it('rejects vehicle and component installation links from another workspace', function () {
    $first = seedTenantIntegrityGraph('install-a');
    $second = seedTenantIntegrityGraph('install-b');
    $now = now();

    expect(fn () => DB::table('component_installations')->insert([
        'workspace_id' => $first['workspace'],
        'vehicle_id' => $second['vehicle'],
        'component_id' => $first['component'],
        'created_by' => DB::table('workspace_user')->where('workspace_id', $first['workspace'])->value('user_id'),
        'installed_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('component_installations')->insert([
        'workspace_id' => $first['workspace'],
        'vehicle_id' => $first['vehicle'],
        'component_id' => $second['component'],
        'created_by' => DB::table('workspace_user')->where('workspace_id', $first['workspace'])->value('user_id'),
        'installed_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
    ]))->toThrow(QueryException::class);
});

it('rejects event entry resources from another workspace at database level', function () {
    $first = seedTenantIntegrityGraph('entry-a');
    $second = seedTenantIntegrityGraph('entry-b');

    expect(fn () => DB::table('event_entries')->where('id', $first['event_entry'])->update([
        'vehicle_id' => $second['vehicle'],
    ]))->toThrow(QueryException::class);
});

it('rejects technical setup and snapshot vehicle links from another workspace', function () {
    $first = seedTenantIntegrityGraph('setup-a');
    $second = seedTenantIntegrityGraph('setup-b');

    expect(fn () => DB::table('technical_setups')->where('id', $first['technical_setup'])->update([
        'vehicle_id' => $second['vehicle'],
    ]))->toThrow(QueryException::class);

    expect(fn () => DB::table('setup_snapshots')->where('id', $first['setup_snapshot'])->update([
        'vehicle_id' => $second['vehicle'],
    ]))->toThrow(QueryException::class);
});

it('rejects maintenance work orders linked to another workspace schedule', function () {
    $first = seedTenantIntegrityGraph('work-a');
    $second = seedTenantIntegrityGraph('work-b');

    expect(fn () => DB::table('maintenance_work_orders')->where('id', $first['work_order'])->update([
        'maintenance_schedule_id' => $second['maintenance_schedule'],
    ]))->toThrow(QueryException::class);
});
