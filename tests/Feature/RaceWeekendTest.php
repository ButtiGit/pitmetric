<?php

use App\Models\Circuit;
use App\Models\Component;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\EventNote;
use App\Models\EventScheduleItem;
use App\Models\EventTask;
use App\Models\Expense;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CreateConfigurationVersionService;
use Illuminate\Support\Facades\Schema;

it('keeps race weekends local for non activated accounts', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertOk()
        ->assertSee('Busca - Race Weekend');

    $this->post(route('events.store'), [
        'name' => 'Server event',
        'circuit_layout_id' => 1,
        'start_date' => '2026-09-20',
        'end_date' => '2026-09-21',
    ])->assertForbidden();

    expect(RaceEvent::query()->count())->toBe(0);
});

it('runs a race weekend through Trackside schedule sessions work notes and linked costs', function () {
    $user = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $workspace = $user->workspaces()->firstOrFail();
    $this->actingAs($user);

    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Kart 27']);
    $type = ComponentType::create(['name' => 'Engine']);
    $component = Component::create(['component_type_id' => $type->id, 'name' => 'Engine 27', 'status' => 'active']);
    $configuration = Configuration::create(['vehicle_id' => $vehicle->id, 'name' => 'Busca race', 'status' => 'active']);
    $version = app(CreateConfigurationVersionService::class)->create($configuration, $user, [$component->id]);
    $circuit = Circuit::create(['name' => 'Busca Kart Planet']);
    $layout = $circuit->layouts()->create(['name' => 'Main', 'length_meters' => 1100, 'is_active' => true]);

    $this->post(route('drivers.store'), [
        'display_name' => 'Pilota A',
        'racing_number' => '27',
    ])->assertRedirect(route('events.index'));
    $driver = Driver::query()->where('display_name', 'Pilota A')->firstOrFail();

    $this->post(route('events.store'), [
        'name' => 'Busca - 12/13 settembre',
        'circuit_layout_id' => $layout->id,
        'start_date' => '2026-09-12',
        'end_date' => '2026-09-13',
        'championship' => 'Regional Karting',
        'round_label' => 'Round 5',
    ])->assertRedirect();
    $event = RaceEvent::query()->firstOrFail();

    $this->post(route('events.entries.store', $event), [
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicle->id,
        'configuration_version_id' => $version->id,
        'entry_number' => '27',
        'entry_cost' => '120.00',
    ])->assertRedirect(route('events.show', $event));
    $entry = EventEntry::query()->firstOrFail();

    $this->post(route('events.schedule.store', $event), [
        'event_entry_id' => $entry->id,
        'label' => 'Heat 1 gruppo A',
        'session_type' => 'heat',
        'starts_at' => '2026-09-13 11:30:00',
        'duration_minutes' => 14,
        'notes' => 'Pre-grid 10 minuti prima.',
    ])->assertRedirect(route('events.show', $event));
    $scheduleItem = EventScheduleItem::query()->firstOrFail();

    $this->patch(route('events.schedule.update', $scheduleItem), [
        'event_entry_id' => $entry->id,
        'label' => 'Heat 1 gruppo A',
        'session_type' => 'heat',
        'starts_at' => '2026-09-13 11:30:00',
        'duration_minutes' => 14,
        'status' => 'ready',
        'notes' => 'Pre-grid 10 minuti prima.',
    ])->assertRedirect(route('events.show', $event));

    $this->post(route('events.tasks.store', $event), [
        'event_entry_id' => $entry->id,
        'title' => 'Controllo catena dopo manche',
        'priority' => 'high',
        'due_at' => '2026-09-13 14:00:00',
    ])->assertRedirect(route('events.show', $event));
    $task = EventTask::query()->firstOrFail();

    $this->patch(route('events.tasks.update', $task), [
        'status' => 'done',
        'operation_cost' => '48.00',
        'cost_description' => 'Catena e manodopera',
    ])->assertRedirect(route('events.show', $event));

    $this->post(route('events.notes.store', $event), [
        'event_entry_id' => $entry->id,
        'kind' => 'driver_feedback',
        'body' => 'Posteriore scivola in uscita T3.',
        'occurred_at' => '2026-09-12 16:10:00',
    ])->assertRedirect(route('events.show', $event));

    $this->post(route('events.expenses.store', $event), [
        'amount' => '210.00',
        'category' => 'entry',
        'description' => 'Fuel and travel',
        'occurred_at' => '2026-09-12 08:00:00',
    ])->assertRedirect(route('events.show', $event));

    $response = $this->post(route('sessions.store'), [
        'event_id' => $event->id,
        'event_entry_id' => $entry->id,
        'schedule_item_id' => $scheduleItem->id,
        'configuration_version_id' => $version->id,
        'session_type' => 'heat',
        'started_at' => '2026-09-13 11:32:00',
        'completed_laps' => 12,
        'duration_minutes' => 14,
        'session_cost' => '35.50',
    ]);

    $session = Session::query()->firstOrFail();
    $response->assertRedirect(route('events.show', ['raceEvent' => $event, 'recorded' => $session->id]));

    expect($session->event_id)->toBe($event->id)
        ->and($session->event_entry_id)->toBe($entry->id)
        ->and($session->circuit_layout_id)->toBe($layout->id)
        ->and($session->session_type)->toBe('heat')
        ->and($scheduleItem->fresh()?->status)->toBe('completed')
        ->and($scheduleItem->fresh()?->session_id)->toBe($session->id)
        ->and($event->fresh()?->status)->toBe('active')
        ->and(EventTask::query()->where('event_id', $event->id)->count())->toBe(1)
        ->and(EventNote::query()->where('event_id', $event->id)->where('kind', 'driver_feedback')->count())->toBe(1)
        ->and(Expense::query()->where('event_id', $event->id)->sum('amount_cents'))->toBe(41350)
        ->and(Expense::query()->where('event_id', $event->id)->where('related_type', 'event_entry')->exists())->toBeTrue()
        ->and(Expense::query()->where('event_id', $event->id)->where('related_type', 'event_task')->exists())->toBeTrue()
        ->and(Expense::query()->where('event_id', $event->id)->where('related_type', 'session')->exists())->toBeTrue();

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Trackside schedule')
        ->assertSee('Heat 1 gruppo A')
        ->assertSee('Pilota A')
        ->assertSee('Kart 27')
        ->assertSee('Controllo catena dopo manche')
        ->assertSee('Posteriore scivola in uscita T3.')
        ->assertSee('413,50');
});

it('rejects sessions and scheduled activities outside the weekend and entries with mismatched setups', function () {
    $user = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $workspace = $user->workspaces()->firstOrFail();
    $this->actingAs($user);

    $vehicleA = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Kart A']);
    $vehicleB = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Kart B']);
    $configurationA = Configuration::create(['vehicle_id' => $vehicleA->id, 'name' => 'Setup A', 'status' => 'active']);
    $configurationB = Configuration::create(['vehicle_id' => $vehicleB->id, 'name' => 'Setup B', 'status' => 'active']);
    $versionA = app(CreateConfigurationVersionService::class)->create($configurationA, $user, []);
    $versionB = app(CreateConfigurationVersionService::class)->create($configurationB, $user, []);
    $circuit = Circuit::create(['name' => 'Test Circuit']);
    $layout = $circuit->layouts()->create(['name' => 'Race', 'length_meters' => 1000, 'is_active' => true]);
    $driver = Driver::create(['display_name' => 'Driver Test', 'status' => 'active']);
    $event = RaceEvent::create([
        'circuit_layout_id' => $layout->id,
        'name' => 'Test Weekend',
        'start_date' => '2026-09-12',
        'end_date' => '2026-09-13',
        'status' => 'planned',
        'created_by' => $user->id,
    ]);

    $this->post(route('events.entries.store', $event), [
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicleA->id,
        'configuration_version_id' => $versionB->id,
    ])->assertNotFound();

    $entry = EventEntry::create([
        'event_id' => $event->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicleA->id,
        'configuration_version_id' => $versionA->id,
    ]);

    $this->post(route('events.schedule.store', $event), [
        'event_entry_id' => $entry->id,
        'session_type' => 'final',
        'starts_at' => '2026-09-14 10:00:00',
    ])->assertSessionHasErrors('starts_at');

    $this->post(route('sessions.store'), [
        'event_id' => $event->id,
        'event_entry_id' => $entry->id,
        'configuration_version_id' => $versionA->id,
        'session_type' => 'final',
        'started_at' => '2026-09-14 10:00:00',
        'completed_laps' => 10,
    ])->assertSessionHasErrors('started_at');

    expect(Session::query()->count())->toBe(0)
        ->and(EventScheduleItem::query()->count())->toBe(0)
        ->and(Schema::hasColumn('track_sessions', 'event_id'))->toBeTrue();
});
