<?php

use App\Models\Circuit;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\EventEntry;
use App\Models\Expense;
use App\Models\RaceEvent;
use App\Models\Session;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CreateConfigurationVersionService;
use App\Services\PerformanceIntelligenceService;

it('turns finalized session history and expenses into safe operational intelligence', function () {
    $user = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $workspace = $user->workspaces()->firstOrFail();
    $this->actingAs($user);

    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Kart Intelligence']);
    $configuration = Configuration::create(['vehicle_id' => $vehicle->id, 'name' => 'Race package', 'status' => 'active']);
    $version = app(CreateConfigurationVersionService::class)->create($configuration, $user, []);
    $circuit = Circuit::create(['name' => 'Intelligence Circuit']);
    $layout = $circuit->layouts()->create(['name' => 'Main', 'length_meters' => 1200, 'is_active' => true]);
    $driver = Driver::create(['display_name' => 'Driver Intelligence', 'racing_number' => '27', 'status' => 'active']);
    $event = RaceEvent::create([
        'circuit_layout_id' => $layout->id,
        'name' => 'Intelligence Weekend',
        'start_date' => '2026-09-12',
        'end_date' => '2026-09-13',
        'status' => 'completed',
        'championship' => 'Regional Karting',
        'created_by' => $user->id,
    ]);
    $entry = EventEntry::create([
        'event_id' => $event->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicle->id,
        'configuration_version_id' => $version->id,
        'entry_number' => '27',
    ]);

    $sessionOne = Session::create([
        'event_id' => $event->id,
        'event_entry_id' => $entry->id,
        'vehicle_id' => $vehicle->id,
        'configuration_version_id' => $version->id,
        'circuit_layout_id' => $layout->id,
        'session_type' => 'heat',
        'started_at' => '2026-09-13 10:00:00',
        'completed_laps' => 10,
        'duration_seconds' => 1200,
        'status' => 'finalized',
        'created_by' => $user->id,
    ]);
    Session::create([
        'event_id' => $event->id,
        'event_entry_id' => $entry->id,
        'vehicle_id' => $vehicle->id,
        'configuration_version_id' => $version->id,
        'circuit_layout_id' => $layout->id,
        'session_type' => 'final',
        'started_at' => '2026-09-13 14:00:00',
        'completed_laps' => 99,
        'duration_seconds' => 1800,
        'distance_override_meters' => 5000,
        'status' => 'finalized',
        'created_by' => $user->id,
    ]);

    Expense::create([
        'event_id' => $event->id,
        'amount_cents' => 30000,
        'currency' => 'EUR',
        'category' => 'track',
        'description' => 'Track session',
        'occurred_at' => '2026-09-13 10:00:00',
        'related_type' => 'session',
        'related_id' => $sessionOne->id,
        'created_by' => $user->id,
    ]);
    Expense::create([
        'event_id' => $event->id,
        'amount_cents' => 12000,
        'currency' => 'EUR',
        'category' => 'event_entry',
        'description' => 'Entry fee',
        'occurred_at' => '2026-09-12 08:00:00',
        'related_type' => 'event_entry',
        'related_id' => $entry->id,
        'created_by' => $user->id,
    ]);
    Expense::create([
        'event_id' => $event->id,
        'amount_cents' => 8000,
        'currency' => 'EUR',
        'category' => 'travel',
        'description' => 'Shared travel',
        'occurred_at' => '2026-09-12 07:00:00',
        'created_by' => $user->id,
    ]);

    $intelligence = app(PerformanceIntelligenceService::class)->dashboard();

    expect($intelligence['summary']['sessions'])->toBe(2)
        ->and($intelligence['summary']['laps'])->toBe(109)
        ->and($intelligence['summary']['distance_meters'])->toBe(17000)
        ->and($intelligence['summary']['duration_seconds'])->toBe(3000)
        ->and($intelligence['summary']['cost_cents'])->toBe(50000)
        ->and($intelligence['summary']['cost_per_km_cents'])->toBe(2941)
        ->and($intelligence['summary']['cost_per_hour_cents'])->toBe(60000)
        ->and($intelligence['drivers']->first()['name'])->toBe('Driver Intelligence')
        ->and($intelligence['vehicles']->first()['name'])->toBe('Kart Intelligence');
});

it('renders intelligence comparisons and an exportable weekend report', function () {
    $user = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $workspace = $user->workspaces()->firstOrFail();
    $this->actingAs($user);

    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'name' => 'Report Kart']);
    $configuration = Configuration::create(['vehicle_id' => $vehicle->id, 'name' => 'Report package', 'status' => 'active']);
    $version = app(CreateConfigurationVersionService::class)->create($configuration, $user, []);
    $circuit = Circuit::create(['name' => 'Report Circuit']);
    $layout = $circuit->layouts()->create(['name' => 'GP', 'length_meters' => 1000, 'is_active' => true]);
    $driver = Driver::create(['display_name' => 'Report Driver', 'status' => 'active']);
    $event = RaceEvent::create([
        'circuit_layout_id' => $layout->id,
        'name' => 'Report Weekend',
        'start_date' => '2026-09-12',
        'end_date' => '2026-09-13',
        'status' => 'completed',
        'created_by' => $user->id,
    ]);
    $entry = EventEntry::create([
        'event_id' => $event->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicle->id,
        'configuration_version_id' => $version->id,
    ]);
    $session = Session::create([
        'event_id' => $event->id,
        'event_entry_id' => $entry->id,
        'vehicle_id' => $vehicle->id,
        'configuration_version_id' => $version->id,
        'circuit_layout_id' => $layout->id,
        'session_type' => 'race',
        'started_at' => '2026-09-13 15:00:00',
        'completed_laps' => 20,
        'duration_seconds' => 1800,
        'status' => 'finalized',
        'created_by' => $user->id,
    ]);
    Expense::create([
        'event_id' => $event->id,
        'amount_cents' => 25000,
        'currency' => 'EUR',
        'category' => 'track',
        'description' => 'Race cost',
        'occurred_at' => '2026-09-13 15:00:00',
        'related_type' => 'session',
        'related_id' => $session->id,
        'created_by' => $user->id,
    ]);

    $this->get(route('insights.index'))
        ->assertOk()
        ->assertSee('Intelligence & Reports')
        ->assertSee('Report Driver')
        ->assertSee('Report Kart')
        ->assertSee('Report Weekend')
        ->assertSee('12,50 €')
        ->assertSee('500,00 €');

    $this->get(route('insights.events.report', $event))
        ->assertOk()
        ->assertSee('PITMETRIC WEEKEND REPORT')
        ->assertSee('Report Weekend')
        ->assertSee('Report Driver')
        ->assertSee('Report Kart')
        ->assertSee('250,00 €');

    $this->get(route('insights.events.report.csv', $event))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertDownload();
});

it('keeps intelligence reports isolated to the active workspace', function () {
    $first = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $this->actingAs($first);

    $firstEvent = RaceEvent::create([
        'name' => 'First Workspace Weekend',
        'start_date' => '2026-09-12',
        'end_date' => '2026-09-13',
        'status' => 'planned',
        'created_by' => $first->id,
    ]);

    $second = User::factory()->withDatabaseAccess()->create(['email_verified_at' => now()]);
    $this->actingAs($second);
    $secondEvent = RaceEvent::create([
        'name' => 'Second Workspace Weekend',
        'start_date' => '2026-09-19',
        'end_date' => '2026-09-20',
        'status' => 'planned',
        'created_by' => $second->id,
    ]);

    $this->actingAs($first)
        ->get(route('insights.index'))
        ->assertOk()
        ->assertSee('First Workspace Weekend')
        ->assertDontSee('Second Workspace Weekend');

    $this->get(route('insights.events.report', $secondEvent))
        ->assertNotFound();

    expect($firstEvent->workspace_id)->not->toBe($secondEvent->workspace_id);
});
