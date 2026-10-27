<?php

use App\Models\Circuit;
use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentTracker;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\EventScheduleItem;
use App\Models\MaintenanceSchedule;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\UsageMetricType;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CreateConfigurationVersionService;

it('lets a team mechanic record one race session and update component maintenance only once', function () {
    $owner = User::factory()->withDatabaseAccess()->create();
    $workspace = $owner->workspaces()->firstOrFail();
    $this->actingAs($owner);

    $kart = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'name' => 'PN Rent Kart 01']);
    $componentType = ComponentType::create(['name' => 'Chain']);
    $chain = Component::create([
        'component_type_id' => $componentType->id,
        'name' => 'Chain 01',
        'status' => 'active',
    ]);
    ComponentInstallation::create([
        'vehicle_id' => $kart->id,
        'component_id' => $chain->id,
        'created_by' => $owner->id,
        'installed_at' => now(),
    ]);

    $configuration = Configuration::create([
        'vehicle_id' => $kart->id,
        'name' => 'Race setup',
        'status' => 'active',
    ]);
    $version = app(CreateConfigurationVersionService::class)->create($configuration, $owner, [$chain->id]);

    $distance = UsageMetricType::query()->where('key', 'distance')->firstOrFail();
    $tracker = ComponentTracker::create([
        'component_id' => $chain->id,
        'usage_metric_type_id' => $distance->id,
        'is_active' => true,
    ]);
    MaintenanceSchedule::create([
        'component_tracker_id' => $tracker->id,
        'name' => 'Chain replacement',
        'interval_value' => 10000,
        'warning_value' => 3000,
        'is_active' => true,
    ]);

    $circuit = Circuit::create(['name' => 'PN Rent Circuit']);
    $layout = $circuit->layouts()->create([
        'name' => 'Main layout',
        'length_meters' => 1000,
        'is_active' => true,
    ]);
    $driver = Driver::create(['display_name' => 'Pilot Driver', 'status' => 'active']);
    $event = RaceEvent::create([
        'name' => 'PN Rent Test Weekend',
        'circuit_layout_id' => $layout->id,
        'start_date' => now()->toDateString(),
        'end_date' => now()->toDateString(),
        'status' => 'planned',
        'created_by' => $owner->id,
    ]);
    $entry = EventEntry::create([
        'event_id' => $event->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $kart->id,
        'configuration_version_id' => $version->id,
    ]);
    $scheduleItem = EventScheduleItem::create([
        'event_id' => $event->id,
        'event_entry_id' => $entry->id,
        'label' => 'Practice 1',
        'session_type' => 'practice',
        'starts_at' => now(),
        'status' => 'planned',
        'created_by' => $owner->id,
    ]);

    $mechanic = User::factory()->withDatabaseAccess()->create();
    $ownWorkspaceId = $mechanic->workspaces()->firstOrFail()->id;
    $workspace->users()->attach($mechanic, [
        'role' => 'mechanic_engineer',
        'status' => 'active',
        'joined_at' => now(),
    ]);
    $this->actingAs($mechanic)
        ->post(route('team.switch', $workspace))
        ->assertRedirect();

    $payload = [
        'event_id' => $event->id,
        'event_entry_id' => $entry->id,
        'schedule_item_id' => $scheduleItem->id,
        'configuration_version_id' => $version->id,
        'session_type' => 'practice',
        'started_at' => now()->toDateTimeString(),
        'completed_laps' => 8,
        'duration_minutes' => 10,
    ];

    $this->post(route('sessions.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertSessionHas('maintenance_attention', 1);

    $session = Session::query()->firstOrFail();
    expect($session->status)->toBe('finalized')
        ->and($session->event_entry_id)->toBe($entry->id)
        ->and($session->created_by)->toBe($mechanic->id)
        ->and($session->workspace_id)->toBe($workspace->id)
        ->and($session->workspace_id)->not->toBe($ownWorkspaceId)
        ->and($scheduleItem->fresh()->session_id)->toBe($session->id)
        ->and($scheduleItem->fresh()->status)->toBe('completed')
        ->and($tracker->usageEntries()->count())->toBe(1)
        ->and((int) $tracker->usageEntries()->sum('value'))->toBe(8000);

    $this->post(route('sessions.store'), $payload)->assertSessionHasErrors('schedule_item_id');

    expect(Session::query()->count())->toBe(1)
        ->and($tracker->usageEntries()->count())->toBe(1)
        ->and((int) $tracker->usageEntries()->sum('value'))->toBe(8000);
});
