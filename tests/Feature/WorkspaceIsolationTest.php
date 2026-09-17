<?php

use App\Models\Circuit;
use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentTracker;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\Document;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\EventScheduleItem;
use App\Models\EventTask;
use App\Models\Expense;
use App\Models\MaintenanceSchedule;
use App\Models\MaintenanceWorkOrder;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\TeamInvitation;
use App\Models\TechnicalSetup;
use App\Models\UsageMetricType;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CreateConfigurationVersionService;
use App\Services\FinalizeSessionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * @return array<string, mixed>
 */
function createWorkspaceIsolationForeignFixture(User $owner): array
{
    $workspace = $owner->workspaces()->firstOrFail();
    $vehicle = Vehicle::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Foreign Workspace Vehicle',
    ]);
    $type = ComponentType::create(['name' => 'Foreign Engine Type']);
    $component = Component::create([
        'component_type_id' => $type->id,
        'name' => 'Foreign Workspace Engine',
        'status' => 'active',
    ]);
    $configuration = Configuration::create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Foreign Workspace Configuration',
        'status' => 'active',
    ]);
    $version = app(CreateConfigurationVersionService::class)->create($configuration, $owner, [$component->id]);
    $installation = ComponentInstallation::query()->where('component_id', $component->id)->firstOrFail();

    $circuit = Circuit::create(['name' => 'Foreign Workspace Circuit']);
    $layout = $circuit->layouts()->create([
        'name' => 'Foreign Layout',
        'length_meters' => 1000,
        'is_active' => true,
    ]);
    $driver = Driver::create([
        'display_name' => 'Foreign Workspace Driver',
        'status' => 'active',
    ]);
    $event = RaceEvent::create([
        'circuit_layout_id' => $layout->id,
        'name' => 'Foreign Workspace Weekend',
        'start_date' => '2026-09-20',
        'end_date' => '2026-09-21',
        'status' => 'planned',
        'created_by' => $owner->id,
    ]);
    $entry = EventEntry::create([
        'event_id' => $event->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicle->id,
        'configuration_version_id' => $version->id,
    ]);
    $scheduleItem = EventScheduleItem::create([
        'event_id' => $event->id,
        'event_entry_id' => $entry->id,
        'label' => 'Foreign Workspace Heat',
        'session_type' => 'heat',
        'starts_at' => '2026-09-20 10:00:00',
        'duration_minutes' => 10,
        'status' => 'planned',
        'created_by' => $owner->id,
    ]);
    $task = EventTask::create([
        'event_id' => $event->id,
        'event_entry_id' => $entry->id,
        'title' => 'Foreign Workspace Task',
        'priority' => 'normal',
        'status' => 'todo',
        'created_by' => $owner->id,
    ]);
    $setup = TechnicalSetup::create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Foreign Workspace Setup',
        'values' => ['brake_bias_pct' => 54],
        'status' => 'active',
        'created_by' => $owner->id,
    ]);

    $metric = UsageMetricType::query()->where('key', 'runtime')->firstOrFail();
    $tracker = ComponentTracker::create([
        'component_id' => $component->id,
        'usage_metric_type_id' => $metric->id,
        'is_active' => true,
    ]);
    $maintenanceSchedule = MaintenanceSchedule::create([
        'component_tracker_id' => $tracker->id,
        'name' => 'Foreign Workspace Service',
        'interval_value' => 3600,
        'warning_value' => 600,
        'is_active' => true,
    ]);
    $workOrder = MaintenanceWorkOrder::create([
        'maintenance_schedule_id' => $maintenanceSchedule->id,
        'title' => 'Foreign Workspace Work Order',
        'priority' => 'normal',
        'status' => 'todo',
        'created_by' => $owner->id,
    ]);
    $expense = Expense::create([
        'amount_cents' => 5000,
        'currency' => 'EUR',
        'category' => 'test',
        'description' => 'Foreign Workspace Expense',
        'occurred_at' => now(),
        'created_by' => $owner->id,
    ]);

    $path = 'workspaces/'.$workspace->id.'/documents/foreign-workspace.pdf';
    Storage::disk('local')->put($path, 'private');
    $document = Document::create([
        'uploaded_by' => $owner->id,
        'name' => 'Foreign Workspace Document',
        'original_name' => 'foreign-workspace.pdf',
        'disk' => 'local',
        'path' => $path,
        'mime_type' => 'application/pdf',
        'size_bytes' => 7,
    ]);

    $session = Session::create([
        'vehicle_id' => $vehicle->id,
        'configuration_version_id' => $version->id,
        'circuit_layout_id' => $layout->id,
        'session_type' => 'practice',
        'started_at' => '2026-09-20 09:00:00',
        'completed_laps' => 1,
        'duration_seconds' => 60,
        'status' => 'draft',
        'created_by' => $owner->id,
        'notes' => 'Foreign Workspace Session',
    ]);

    return compact(
        'workspace',
        'vehicle',
        'component',
        'configuration',
        'version',
        'installation',
        'layout',
        'driver',
        'event',
        'entry',
        'scheduleItem',
        'task',
        'setup',
        'tracker',
        'maintenanceSchedule',
        'workOrder',
        'expense',
        'document',
        'session',
    );
}

it('keeps route bound resources isolated to the selected team even for multi team members', function () {
    Storage::fake('local');

    $actor = User::factory()->withDatabaseAccess()->create();
    $currentWorkspace = $actor->workspaces()->firstOrFail();

    $foreignOwner = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($foreignOwner);
    $foreign = createWorkspaceIsolationForeignFixture($foreignOwner);

    $foreignMember = User::factory()->create();
    $foreign['workspace']->users()->attach($foreignMember, [
        'role' => 'driver',
        'status' => 'active',
        'joined_at' => now(),
    ]);
    $invitation = TeamInvitation::create([
        'workspace_id' => $foreign['workspace']->id,
        'invited_by' => $foreignOwner->id,
        'email' => 'foreign-invite@example.test',
        'role' => 'driver',
        'status' => 'pending',
        'expires_at' => now()->addDays(7),
    ]);

    $foreign['workspace']->users()->attach($actor, [
        'role' => 'manager',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $this->actingAs($actor)
        ->post(route('team.switch', $currentWorkspace))
        ->assertRedirect();

    $this->get(route('garage.index'))
        ->assertOk()
        ->assertDontSee('Foreign Workspace Vehicle');
    $this->get(route('components.index'))
        ->assertOk()
        ->assertDontSee('Foreign Workspace Engine');
    $this->get(route('configurations.index'))
        ->assertOk()
        ->assertDontSee('Foreign Workspace Configuration');
    $this->get(route('setups.index'))
        ->assertOk()
        ->assertDontSee('Foreign Workspace Setup');
    $this->get(route('events.index'))
        ->assertOk()
        ->assertDontSee('Foreign Workspace Weekend');
    $this->get(route('sessions.index'))
        ->assertOk()
        ->assertDontSee('Foreign Workspace Vehicle');
    $this->get(route('maintenance.index'))
        ->assertOk()
        ->assertDontSee('Foreign Workspace Work Order');
    $this->get(route('expenses.index'))
        ->assertOk()
        ->assertDontSee('Foreign Workspace Expense');
    $this->get(route('control-center.index'))
        ->assertOk()
        ->assertDontSee('Foreign Workspace Document');

    $this->get(route('events.show', $foreign['event']))->assertNotFound();
    $this->get(route('insights.events.report', $foreign['event']))->assertNotFound();
    $this->get(route('insights.events.report.csv', $foreign['event']))->assertNotFound();

    $this->put(route('garage.update', $foreign['vehicle']), [
        'name' => 'Hijacked vehicle',
        'category' => 'kart',
        'status' => 'active',
    ])->assertNotFound();
    $this->delete(route('garage.destroy', $foreign['vehicle']))->assertNotFound();
    $this->delete(route('components.destroy', $foreign['component']))->assertNotFound();
    $this->patch(route('component-installations.remove', $foreign['installation']), [
        'removed_at' => '2026-09-20 11:00:00',
    ])->assertNotFound();
    $this->post(route('configurations.versions.store', $foreign['configuration']), [])->assertNotFound();
    $this->delete(route('configurations.destroy', $foreign['configuration']))->assertNotFound();
    $this->put(route('setups.update', $foreign['setup']), ['name' => 'Hijacked setup'])->assertNotFound();
    $this->delete(route('setups.destroy', $foreign['setup']))->assertNotFound();
    $this->patch(route('events.status', $foreign['event']), ['status' => 'completed'])->assertNotFound();
    $this->post(route('events.entries.store', $foreign['event']), [])->assertNotFound();
    $this->post(route('events.schedule.store', $foreign['event']), [])->assertNotFound();
    $this->post(route('events.tasks.store', $foreign['event']), [])->assertNotFound();
    $this->post(route('events.notes.store', $foreign['event']), [])->assertNotFound();
    $this->post(route('events.expenses.store', $foreign['event']), [])->assertNotFound();
    $this->patch(route('events.schedule.update', $foreign['scheduleItem']), [])->assertNotFound();
    $this->delete(route('events.schedule.destroy', $foreign['scheduleItem']))->assertNotFound();
    $this->patch(route('events.tasks.update', $foreign['task']), [])->assertNotFound();
    $this->patch(route('maintenance.work-orders.update', $foreign['workOrder']), [])->assertNotFound();
    $this->post(route('maintenance.work-orders.complete', $foreign['workOrder']), [])->assertNotFound();
    $this->post(route('maintenance.complete', $foreign['maintenanceSchedule']), [])->assertNotFound();
    $this->delete(route('expenses.destroy', $foreign['expense']))->assertNotFound();
    $this->get(route('control-center.documents.download', $foreign['document']))->assertNotFound();
    $this->delete(route('control-center.documents.destroy', $foreign['document']))->assertNotFound();
    $this->delete(route('team.invitations.destroy', $invitation))->assertNotFound();
    $this->patch(route('team.members.update', $foreignMember), [
        'role' => 'viewer',
        'status' => 'active',
    ])->assertNotFound();

    $this->assertDatabaseHas('vehicles', [
        'id' => $foreign['vehicle']->id,
        'name' => 'Foreign Workspace Vehicle',
        'deleted_at' => null,
    ]);
    $this->assertDatabaseHas('expenses', [
        'id' => $foreign['expense']->id,
        'description' => 'Foreign Workspace Expense',
    ]);
    $this->assertDatabaseHas('documents', [
        'id' => $foreign['document']->id,
        'name' => 'Foreign Workspace Document',
    ]);
});

it('rejects foreign nested identifiers inside valid current team writes', function () {
    Storage::fake('local');

    $actor = User::factory()->withDatabaseAccess()->create();
    $currentWorkspace = $actor->workspaces()->firstOrFail();
    $this->actingAs($actor);

    $ownVehicle = Vehicle::factory()->create([
        'workspace_id' => $currentWorkspace->id,
        'name' => 'Current Workspace Vehicle',
    ]);
    $ownType = ComponentType::create(['name' => 'Current Engine Type']);
    $ownComponent = Component::create([
        'component_type_id' => $ownType->id,
        'name' => 'Current Workspace Engine',
        'status' => 'active',
    ]);
    $ownConfiguration = Configuration::create([
        'vehicle_id' => $ownVehicle->id,
        'name' => 'Current Workspace Configuration',
        'status' => 'active',
    ]);
    $ownVersion = app(CreateConfigurationVersionService::class)->create($ownConfiguration, $actor, [$ownComponent->id]);
    $ownCircuit = Circuit::create(['name' => 'Current Workspace Circuit']);
    $ownLayout = $ownCircuit->layouts()->create([
        'name' => 'Current Layout',
        'length_meters' => 900,
        'is_active' => true,
    ]);
    $ownDriver = Driver::create([
        'display_name' => 'Current Workspace Driver',
        'status' => 'active',
    ]);
    $ownEvent = RaceEvent::create([
        'circuit_layout_id' => $ownLayout->id,
        'name' => 'Current Workspace Weekend',
        'start_date' => '2026-09-20',
        'end_date' => '2026-09-21',
        'status' => 'planned',
        'created_by' => $actor->id,
    ]);
    $ownEntry = EventEntry::create([
        'event_id' => $ownEvent->id,
        'driver_id' => $ownDriver->id,
        'vehicle_id' => $ownVehicle->id,
        'configuration_version_id' => $ownVersion->id,
    ]);

    $foreignOwner = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($foreignOwner);
    $foreign = createWorkspaceIsolationForeignFixture($foreignOwner);

    $this->actingAs($actor)
        ->post(route('team.switch', $currentWorkspace))
        ->assertRedirect();

    $this->post(route('component-installations.store'), [
        'component_id' => $foreign['component']->id,
        'vehicle_id' => $ownVehicle->id,
        'installed_at' => '2026-09-20 08:00:00',
    ])->assertNotFound();

    $this->post(route('configurations.store'), [
        'vehicle_id' => $foreign['vehicle']->id,
        'name' => 'Cross workspace vehicle configuration',
    ])->assertNotFound();

    $this->post(route('configurations.store'), [
        'vehicle_id' => $ownVehicle->id,
        'name' => 'Cross workspace component configuration',
        'component_ids' => [$foreign['component']->id],
    ])->assertSessionHasErrors('component_ids');

    $this->post(route('configurations.versions.store', $ownConfiguration), [
        'component_ids' => [$foreign['component']->id],
    ])->assertSessionHasErrors('component_ids');

    $this->post(route('setups.store'), [
        'vehicle_id' => $foreign['vehicle']->id,
        'name' => 'Cross workspace setup',
    ])->assertNotFound();

    $this->post(route('events.store'), [
        'circuit_layout_id' => $foreign['layout']->id,
        'name' => 'Cross workspace event',
        'start_date' => '2026-09-20',
        'end_date' => '2026-09-21',
    ])->assertNotFound();

    $this->post(route('events.entries.store', $ownEvent), [
        'driver_id' => $foreign['driver']->id,
        'vehicle_id' => $ownVehicle->id,
        'configuration_version_id' => $ownVersion->id,
    ])->assertNotFound();

    $this->post(route('events.entries.store', $ownEvent), [
        'driver_id' => $ownDriver->id,
        'vehicle_id' => $foreign['vehicle']->id,
        'configuration_version_id' => $ownVersion->id,
    ])->assertNotFound();

    $this->post(route('events.entries.store', $ownEvent), [
        'driver_id' => $ownDriver->id,
        'vehicle_id' => $ownVehicle->id,
        'configuration_version_id' => $foreign['version']->id,
    ])->assertNotFound();

    $this->post(route('events.schedule.store', $ownEvent), [
        'event_entry_id' => $foreign['entry']->id,
        'session_type' => 'practice',
        'starts_at' => '2026-09-20 09:00:00',
    ])->assertNotFound();

    $this->post(route('events.tasks.store', $ownEvent), [
        'event_entry_id' => $foreign['entry']->id,
        'title' => 'Cross workspace task',
        'priority' => 'normal',
    ])->assertNotFound();

    $this->post(route('events.notes.store', $ownEvent), [
        'event_entry_id' => $foreign['entry']->id,
        'kind' => 'technical',
        'body' => 'Cross workspace note',
        'occurred_at' => '2026-09-20 09:00:00',
    ])->assertNotFound();

    $this->post(route('sessions.store'), [
        'configuration_version_id' => $foreign['version']->id,
        'session_type' => 'practice',
        'started_at' => '2026-09-20 10:00:00',
    ])->assertNotFound();

    $this->post(route('sessions.store'), [
        'configuration_version_id' => $ownVersion->id,
        'technical_setup_id' => $foreign['setup']->id,
        'session_type' => 'practice',
        'started_at' => '2026-09-20 10:00:00',
    ])->assertNotFound();

    $this->post(route('maintenance.store'), [
        'component_tracker_id' => $foreign['tracker']->id,
        'name' => 'Cross workspace maintenance',
        'interval_display' => 1,
    ])->assertNotFound();

    $this->post(route('maintenance.work-orders.store'), [
        'maintenance_schedule_id' => $foreign['maintenanceSchedule']->id,
        'title' => 'Cross workspace work order',
        'priority' => 'normal',
    ])->assertNotFound();

    $this->post(route('control-center.documents.store'), [
        'document' => UploadedFile::fake()->create('current.pdf', 10, 'application/pdf'),
        'name' => 'Cross workspace attachment',
        'attachable' => 'vehicle:'.$foreign['vehicle']->id,
    ])->assertSessionHasErrors('attachable_id');

    expect(Configuration::query()->where('name', 'Cross workspace component configuration')->exists())->toBeFalse()
        ->and(EventEntry::query()->where('event_id', $ownEvent->id)->count())->toBe(1)
        ->and($ownEntry->exists)->toBeTrue();
});

it('does not finalize a session from another workspace', function () {
    Storage::fake('local');

    $actor = User::factory()->withDatabaseAccess()->create();
    $foreignOwner = User::factory()->withDatabaseAccess()->create();

    $this->actingAs($foreignOwner);
    $foreign = createWorkspaceIsolationForeignFixture($foreignOwner);

    $this->actingAs($actor);

    expect(fn () => app(FinalizeSessionService::class)->finalize($foreign['session']))
        ->toThrow(ModelNotFoundException::class);

    $this->assertDatabaseHas('track_sessions', [
        'id' => $foreign['session']->id,
        'status' => 'draft',
        'finalized_at' => null,
    ]);
});

it('enforces the selected workspace and writable role in the vehicle policy', function () {
    $owner = User::factory()->withDatabaseAccess()->create();
    $workspace = $owner->workspaces()->firstOrFail();
    $this->actingAs($owner);
    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id]);

    $viewer = User::factory()->create();
    $workspace->users()->attach($viewer, [
        'role' => 'viewer',
        'status' => 'active',
        'joined_at' => now(),
    ]);

    expect(Gate::forUser($viewer)->allows('view', $vehicle))->toBeTrue()
        ->and(Gate::forUser($viewer)->allows('create', Vehicle::class))->toBeFalse()
        ->and(Gate::forUser($viewer)->allows('update', $vehicle))->toBeFalse()
        ->and(Gate::forUser($viewer)->allows('delete', $vehicle))->toBeFalse();
});

it('rejects switching to a workspace without an active membership', function () {
    $actor = User::factory()->withDatabaseAccess()->create();
    $outsider = User::factory()->withDatabaseAccess()->create();
    $outsiderWorkspace = $outsider->workspaces()->firstOrFail();

    $this->actingAs($actor)
        ->post(route('team.switch', $outsiderWorkspace))
        ->assertForbidden();
});
