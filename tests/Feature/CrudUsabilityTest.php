<?php

use App\Models\Circuit;
use App\Models\Component;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\RaceEvent;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CreateConfigurationVersionService;

beforeEach(function () {
    $this->operator = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($this->operator);
});

test('component corrections preserve purchase cost and usage trackers', function () {
    $this->post(route('components.store'), ['name' => 'Original engine', 'type_name' => 'Engine', 'metric_key' => 'runtime', 'purchase_cost' => 100])->assertSessionHasNoErrors();
    $component = Component::query()->firstOrFail();
    $this->put(route('components.update', $component), ['name' => 'Corrected engine', 'serial_number' => 'A-12', 'purchase_cost_cents' => 1])->assertSessionHasNoErrors();
    expect($component->fresh()->name)->toBe('Corrected engine')->and($component->fresh()->purchase_cost_cents)->toBe(10000)->and($component->trackers()->count())->toBe(1)->and((int) Expense::query()->sum('amount_cents'))->toBe(10000);
});

test('manual expense corrections use exact cents and protect linked costs', function () {
    $this->post(route('expenses.store'), ['amount' => 12.34, 'description' => 'Fuel', 'category' => 'fuel', 'occurred_at' => now()->toDateTimeString()])->assertSessionHasNoErrors();
    $expense = Expense::query()->firstOrFail();
    $data = ['amount' => '23.45', 'description' => 'Fuel corrected', 'category' => 'fuel', 'occurred_at' => now()->toDateTimeString()];
    $this->put(route('expenses.update', $expense), $data)->assertSessionHasNoErrors();
    expect($expense->fresh()->amount_cents)->toBe(2345);
    $this->get(route('expenses.index'))->assertSuccessful()->assertSee('Edit expense')->assertDontSee('@else', false)->assertDontSee('Managed by source');
    $this->put(route('expenses.update', $expense), [...$data, 'amount' => '0.001'])->assertSessionHasErrors('amount');
    $expense->update(['related_type' => 'session', 'related_id' => 123]);
    $this->put(route('expenses.update', $expense), $data)->assertSessionHasErrors('expense');
});

test('layout corrections protect historical length and support archival', function () {
    $this->post(route('circuits.store'), ['name' => 'Circuit', 'layout_name' => 'Full', 'length_meters' => 1000])->assertSessionHasNoErrors();
    $circuit = Circuit::query()->firstOrFail();
    $layout = $circuit->layouts()->firstOrFail();
    $this->put(route('circuits.update', $circuit), ['name' => 'Circuit corrected', 'country' => 'Italy'])->assertSessionHasNoErrors();
    $this->put(route('circuits.layouts.update', [$circuit, $layout]), ['name' => 'Full', 'length_meters' => 1100, 'is_active' => 1])->assertSessionHasNoErrors();
    RaceEvent::create(['name' => 'Test event', 'circuit_layout_id' => $layout->id, 'start_date' => now(), 'end_date' => now(), 'status' => 'planned', 'created_by' => $this->operator->id]);
    $this->put(route('circuits.layouts.update', [$circuit, $layout]), ['name' => 'Full', 'length_meters' => 1200, 'is_active' => 1])->assertSessionHasErrors('length_meters');
    $this->put(route('circuits.layouts.update', [$circuit, $layout]), ['name' => 'Full', 'length_meters' => 1100, 'is_active' => 0])->assertSessionHasNoErrors();
    $this->post(route('circuits.layouts.store', $circuit), ['name' => 'New full', 'length_meters' => 1200])->assertSessionHasNoErrors();
    expect($layout->fresh()->length_meters)->toBe(1100)->and($layout->fresh()->is_active)->toBeFalse()->and($circuit->layouts()->count())->toBe(2);
});

test('edit endpoints reject foreign workspaces and read only roles', function () {
    $vehicle = Vehicle::factory()->create(['workspace_id' => $this->operator->workspaces()->firstOrFail()->id]);
    $type = ComponentType::create(['name' => 'Engine']);
    $component = Component::create(['component_type_id' => $type->id, 'name' => 'Engine', 'status' => 'active']);
    $configuration = Configuration::create(['vehicle_id' => $vehicle->id, 'name' => 'Build', 'status' => 'active']);
    $expense = Expense::create(['amount_cents' => 1000, 'currency' => 'EUR', 'category' => 'fuel', 'description' => 'Fuel', 'occurred_at' => now(), 'created_by' => $this->operator->id]);
    $circuit = Circuit::create(['name' => 'Circuit']);
    $driver = Driver::create(['display_name' => 'Driver']);
    $layout = $circuit->layouts()->create(['name' => 'Full', 'length_meters' => 1000, 'is_active' => true]);
    $routes = [['components.update', $component], ['configurations.update', $configuration], ['expenses.update', $expense], ['circuits.update', $circuit], ['drivers.update', $driver]];
    $stranger = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($stranger);
    foreach ($routes as [$route, $model]) {
        $this->put(route($route, $model), ['name' => 'Intrusion'])->assertNotFound();
    }
    $this->put(route('circuits.layouts.update', [$circuit, $layout]), [])->assertNotFound();
    $viewer = User::factory()->withDatabaseAccess()->create();
    $team = $this->operator->workspaces()->firstOrFail();
    $team->users()->attach($viewer, ['role' => 'viewer', 'status' => 'active', 'joined_at' => now()]);
    $this->actingAs($viewer)->post(route('team.switch', $team))->assertRedirect();
    foreach ($routes as [$route, $model]) {
        $this->put(route($route, $model), ['name' => 'Intrusion'])->assertForbidden();
    }
    $this->get(route('components.index'))->assertSuccessful()->assertDontSee('id="create-component"', false)->assertDontSee('id="edit-component-', false)->assertSee('Read-only access');
});

test('nested layout updates reject a different parent circuit', function () {
    $first = Circuit::create(['name' => 'First']);
    $second = Circuit::create(['name' => 'Second']);
    $layout = $second->layouts()->create(['name' => 'Full', 'length_meters' => 1000, 'is_active' => true]);
    $this->put(route('circuits.layouts.update', [$first, $layout]), ['name' => 'Changed', 'length_meters' => 1200, 'is_active' => 1])->assertNotFound();
    expect($layout->fresh()->name)->toBe('Full');
});

test('validation retains the submitted dialog and input', function () {
    $this->from(route('circuits.index'))->post(route('circuits.store'), ['_pm_dialog' => 'create-circuit', 'name' => '<script>alert(1)</script>', 'layout_name' => 'Keep this layout', 'length_meters' => 0])->assertSessionHasErrors('length_meters')->assertSessionHasInput('layout_name', 'Keep this layout');
    expect(session()->getOldInput('_pm_dialog'))->toBe('create-circuit');
});

test('the failed dialog renders escaped input and field errors', function () {
    $this->followingRedirects()->from(route('circuits.index'))->post(route('circuits.store'), ['_pm_dialog' => 'create-circuit', 'name' => '<script>alert(1)</script>', 'layout_name' => 'Keep this layout', 'length_meters' => 0])
        ->assertSuccessful()->assertSee('data-pm-form-state', false)->assertSee('Keep this layout')->assertDontSee('<script>alert(1)</script>', false);
});

test('driver corrections and archival preserve the record', function () {
    $this->post(route('drivers.store'), ['display_name' => 'First name'])->assertSessionHasNoErrors();
    $driver = Driver::query()->firstOrFail();
    $this->put(route('drivers.update', $driver), ['display_name' => 'Correct name', 'racing_number' => '27'])->assertSessionHasNoErrors();
    $this->delete(route('drivers.destroy', $driver))->assertSessionHasNoErrors();
    expect(Driver::withTrashed()->findOrFail($driver->id)->display_name)->toBe('Correct name');
});

test('duplicate circuit and layout names produce validation errors instead of database errors', function () {
    $circuit = Circuit::create(['name' => 'Existing circuit']);
    $layout = $circuit->layouts()->create(['name' => 'Full', 'length_meters' => 1000, 'is_active' => true]);
    $this->post(route('circuits.store'), ['name' => 'Existing circuit', 'layout_name' => 'Full', 'length_meters' => 1000])->assertSessionHasErrors('name');
    $this->post(route('circuits.layouts.store', $circuit), ['name' => 'Full', 'length_meters' => 1200])->assertSessionHasErrors('name');
    $this->put(route('circuits.layouts.update', [$circuit, $layout]), ['name' => 'Full', 'length_meters' => 1000, 'is_active' => 1])->assertSessionHasNoErrors();
    expect(Circuit::query()->count())->toBe(1)->and($circuit->layouts()->count())->toBe(1);
});

test('configuration corrections keep their versions unchanged', function () {
    $vehicle = Vehicle::factory()->create(['workspace_id' => $this->operator->workspaces()->firstOrFail()->id]);
    $configuration = Configuration::create(['vehicle_id' => $vehicle->id, 'name' => 'Original', 'status' => 'active']);
    $version = app(CreateConfigurationVersionService::class)->create($configuration, $this->operator, []);
    $this->put(route('configurations.update', $configuration), ['name' => 'Corrected', 'description' => 'Dry conditions'])->assertSessionHasNoErrors();
    expect($configuration->fresh()->name)->toBe('Corrected')->and($configuration->versions()->count())->toBe(1)->and($version->fresh()->version_number)->toBe(1);
});

test('weekend corrections cannot exclude already scheduled activities', function () {
    $circuit = Circuit::create(['name' => 'Circuit']);
    $layout = $circuit->layouts()->create(['name' => 'Full', 'length_meters' => 1000, 'is_active' => true]);
    $event = RaceEvent::create(['name' => 'Weekend', 'circuit_layout_id' => $layout->id, 'start_date' => '2026-09-18', 'end_date' => '2026-09-20', 'status' => 'planned', 'created_by' => $this->operator->id]);
    $event->scheduleItems()->create(['name' => 'Practice', 'session_type' => 'practice', 'starts_at' => '2026-09-19 10:00:00', 'status' => 'planned', 'created_by' => $this->operator->id]);
    $data = ['name' => 'Corrected weekend', 'start_date' => '2026-09-18', 'end_date' => '2026-09-20'];
    $this->put(route('events.update', $event), $data)->assertSessionHasNoErrors();
    $this->put(route('events.update', $event), [...$data, 'end_date' => '2026-09-18'])->assertSessionHasErrors('start_date');
    expect($event->fresh()->name)->toBe('Corrected weekend')->and($event->fresh()->end_date->toDateString())->toBe('2026-09-20');
});
