<?php

use App\Models\Circuit;
use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentTracker;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\Expense;
use App\Models\Session;
use App\Models\TechnicalSetup;
use App\Models\UsageMetricType;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CreateConfigurationVersionService;
use App\Services\FinalizeSessionService;
use App\Services\OperationCostService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->operator = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($this->operator);
    $this->vehicle = Vehicle::factory()->create(['workspace_id' => $this->operator->workspaces()->firstOrFail()->id]);
    $type = ComponentType::create(['name' => 'Engine']);
    $this->component = Component::create(['name' => 'Engine A', 'component_type_id' => $type->id, 'status' => 'active']);
    ComponentInstallation::create([
        'vehicle_id' => $this->vehicle->id,
        'component_id' => $this->component->id,
        'created_by' => $this->operator->id,
        'installed_at' => now(),
    ]);
    $this->configuration = Configuration::create(['vehicle_id' => $this->vehicle->id, 'name' => 'Race build', 'status' => 'active']);
    $this->version = app(CreateConfigurationVersionService::class)->create($this->configuration, $this->operator, [$this->component->id]);
});

test('a configuration cannot claim a component that is not physically installed on its vehicle', function () {
    $otherVehicle = Vehicle::factory()->create(['workspace_id' => $this->vehicle->workspace_id]);
    $other = Configuration::create(['vehicle_id' => $otherVehicle->id, 'name' => 'Other build', 'status' => 'active']);
    expect(fn () => app(CreateConfigurationVersionService::class)->create($other, $this->operator, [$this->component->id]))->toThrow(ValidationException::class);
    expect($other->versions()->count())->toBe(0)
        ->and($this->component->installations()->whereNull('removed_at')->count())->toBe(1)
        ->and($this->component->activeInstallation->vehicle_id)->toBe($this->vehicle->id);
});

test('vehicle archival requires removing installed components', function () {
    $this->delete(route('garage.destroy', $this->vehicle))->assertSessionHasErrors('vehicle');
    expect($this->vehicle->fresh()->trashed())->toBeFalse();
});

test('history remains readable after archiving vehicles configurations and components', function () {
    $this->post(route('sessions.store'), ['configuration_version_id' => $this->version->id, 'session_type' => 'test', 'started_at' => now()->toDateTimeString(), 'duration_minutes' => 10])->assertSessionHasNoErrors();
    $installation = $this->component->activeInstallation;
    $installation->update(['removed_at' => now()]);
    $this->delete(route('components.destroy', $this->component))->assertSessionHasNoErrors();
    $this->delete(route('configurations.destroy', $this->configuration))->assertSessionHasNoErrors();
    $this->delete(route('garage.destroy', $this->vehicle))->assertSessionHasNoErrors();
    $session = Session::query()->firstOrFail();
    expect($session->vehicle->id)->toBe($this->vehicle->id)
        ->and($session->configurationVersion->configuration->id)->toBe($this->configuration->id)
        ->and($session->configurationVersion->components->modelKeys())->toBe([$this->component->id])
        ->and($installation->fresh()->component->id)->toBe($this->component->id);
    foreach (['dashboard', 'sessions.index', 'configurations.index', 'maintenance.index', 'expenses.index', 'timing.index', 'telemetry.index'] as $route) {
        $this->get(route($route))->assertSuccessful();
    }
    $this->get(route('sessions.index'))->assertViewHas('versions', fn ($versions) => $versions->isEmpty());
    $this->post(route('sessions.store'), ['configuration_version_id' => $this->version->id, 'session_type' => 'test', 'started_at' => now()->toDateTimeString()])->assertNotFound();
});

test('explicit zero distance does not silently recalculate circuit distance', function () {
    $circuit = Circuit::create(['name' => 'Test circuit']);
    $layout = $circuit->layouts()->create(['name' => 'Full', 'length_meters' => 1000, 'is_active' => true]);
    $metric = UsageMetricType::query()->where('key', 'distance')->firstOrFail();
    $tracker = ComponentTracker::create(['component_id' => $this->component->id, 'usage_metric_type_id' => $metric->id, 'is_active' => true]);
    $session = Session::create(['vehicle_id' => $this->vehicle->id, 'configuration_version_id' => $this->version->id, 'circuit_layout_id' => $layout->id, 'session_type' => 'test', 'started_at' => now(), 'completed_laps' => 10, 'distance_override_meters' => 0, 'status' => 'draft', 'created_by' => $this->operator->id]);
    app(FinalizeSessionService::class)->finalize($session);
    expect((int) $tracker->usageEntries()->sum('value'))->toBe(0);
});

test('vehicle creation rolls back when its cost cannot be saved', function () {
    $this->mock(OperationCostService::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('Simulated cost failure'));
    $before = Vehicle::query()->count();
    $this->post(route('garage.store'), ['name' => 'Must roll back', 'category' => 'kart', 'status' => 'active', 'purchase_cost' => 100])->assertServerError();
    expect(Vehicle::query()->count())->toBe($before);
});

test('configuration version rolls back when its cost fails without changing physical state', function () {
    $this->mock(OperationCostService::class)->shouldReceive('record')->once()->andThrow(new RuntimeException('Simulated cost failure'));
    $this->post(route('configurations.versions.store', $this->configuration), ['operation_cost' => 10])->assertServerError();
    expect($this->configuration->versions()->count())->toBe(1)
        ->and($this->component->fresh()->activeInstallation)->not->toBeNull();
});

test('technical setup adjustments preserve previous preparation costs', function () {
    $this->post(route('setups.store'), ['vehicle_id' => $this->vehicle->id, 'name' => 'Dry setup', 'operation_cost' => 40])->assertSessionHasNoErrors();
    $setup = TechnicalSetup::query()->firstOrFail();
    $this->put(route('setups.update', $setup), ['name' => 'Dry revised', 'operation_cost' => 15])->assertSessionHasNoErrors();
    $this->put(route('setups.update', $setup), ['name' => 'Dry final', 'operation_cost' => 5])->assertSessionHasNoErrors();
    expect(Expense::query()->where('related_type', 'technical_setup')->count())->toBe(3)->and((int) Expense::query()->sum('amount_cents'))->toBe(6000);
});

test('a scheduled session requires its event context', function () {
    $this->post(route('sessions.store'), ['configuration_version_id' => $this->version->id, 'schedule_item_id' => 1, 'session_type' => 'test', 'started_at' => now()->toDateTimeString()])->assertSessionHasErrors('event_id');
    expect(Session::query()->count())->toBe(0);
});

test('closed installations cannot be charged and closed a second time', function () {
    $installation = $this->component->activeInstallation;
    $data = ['removed_at' => now()->addMinute()->toDateTimeString(), 'operation_cost' => 12];
    $this->patch(route('component-installations.remove', $installation), $data)->assertSessionHasNoErrors();
    $this->patch(route('component-installations.remove', $installation), $data)->assertSessionHasErrors('removed_at');
    expect(Expense::query()->where('related_type', 'component_installation_remove')->count())->toBe(1);
});

test('session pagination keeps older records reachable', function () {
    for ($i = 0; $i < 26; $i++) {
        Session::create(['vehicle_id' => $this->vehicle->id, 'configuration_version_id' => $this->version->id, 'session_type' => 'test', 'started_at' => now()->subMinutes($i), 'status' => 'draft', 'created_by' => $this->operator->id]);
    }
    $this->get(route('sessions.index'))->assertSuccessful()->assertViewHas('sessions', fn ($sessions) => $sessions->total() === 26 && $sessions->count() === 25);
    $this->get(route('sessions.index', ['page' => 2]))->assertSuccessful()->assertViewHas('sessions', fn ($sessions) => $sessions->count() === 1);
});

test('archived vehicles cannot receive new configuration versions', function () {
    $this->vehicle->delete();
    expect(fn () => app(CreateConfigurationVersionService::class)->create($this->configuration, $this->operator, [$this->component->id]))->toThrow(ValidationException::class);
    expect($this->configuration->versions()->count())->toBe(1);
});

test('archived components are excluded from new maintenance schedules', function () {
    $metric = UsageMetricType::query()->where('key', 'runtime')->firstOrFail();
    $tracker = ComponentTracker::create(['component_id' => $this->component->id, 'usage_metric_type_id' => $metric->id, 'is_active' => true]);
    $this->component->activeInstallation->update(['removed_at' => now()]);
    $this->component->delete();
    $this->post(route('maintenance.store'), ['component_tracker_id' => $tracker->id, 'name' => 'New schedule', 'interval_display' => 10])->assertNotFound();
});
