<?php

use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\Session;
use App\Models\SetupSnapshot;
use App\Models\TechnicalSetup;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\CreateConfigurationVersionService;
use App\Services\PrintDocumentService;
use App\Services\WorkspaceContext;
use Illuminate\Support\Facades\Http;

function printableFixture(User $user): array
{
    $workspace = $user->workspaces()->firstOrFail();
    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->id, 'category' => 'kart', 'name' => 'Print kart', 'identifier' => 'KART-27']);
    $type = ComponentType::firstOrCreate(['name' => 'Print engine type']);
    $component = Component::create(['component_type_id' => $type->id, 'name' => 'Mounted print engine', 'serial_number' => 'SERIAL-27', 'status' => 'active']);
    ComponentInstallation::create(['component_id' => $component->id, 'vehicle_id' => $vehicle->id, 'installed_at' => now(), 'created_by' => $user->id]);
    $configuration = Configuration::create(['vehicle_id' => $vehicle->id, 'name' => 'Print build', 'status' => 'active']);
    $version = app(CreateConfigurationVersionService::class)->create($configuration, $user, [$component->id]);
    $values = ['camber_mode' => 'notches', 'camber_front_position' => '2', 'caster_front_deg' => 0, 'rear_width_mode' => 'both', 'track_width_rear_mm' => 1390, 'rear_axle_length_mm' => 1040, 'tyre_pressure_fl' => 0.8, 'gear_ratio' => 4];
    $setup = TechnicalSetup::create(['vehicle_id' => $vehicle->id, 'name' => 'Original print setup', 'values' => $values, 'status' => 'active', 'created_by' => $user->id]);
    $session = Session::create(['vehicle_id' => $vehicle->id, 'configuration_version_id' => $version->id, 'session_type' => 'practice', 'status' => 'finalized', 'started_at' => '2026-11-01 10:00:00', 'created_by' => $user->id]);
    SetupSnapshot::create(['session_id' => $session->id, 'technical_setup_id' => $setup->id, 'vehicle_id' => $vehicle->id, 'configuration_version_id' => $version->id, 'name' => $setup->name, 'values' => $values, 'captured_at' => $session->started_at, 'created_by' => $user->id]);

    return compact('vehicle', 'setup', 'session', 'component');
}

test('print sheets contain full identities and only currently installed components', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $f = printableFixture($user);
    $this->get(route('prints.vehicle', $f['vehicle']))->assertOk()->assertSee('KART-27')->assertSee('SERIAL-27')->assertSee('Mounted print engine')->assertHeader('Cache-Control', 'no-store, private');
    $f['vehicle']->componentInstallations()->update(['removed_at' => now()]);
    $this->get(route('prints.vehicle', $f['vehicle']))->assertOk()->assertDontSee('Mounted print engine');
});

test('session print uses the original snapshot after its profile is modified and archived', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $f = printableFixture($user);
    $f['setup']->update(['name' => 'Later setup', 'values' => ['camber_front_deg' => 9.876]]);
    $f['setup']->delete();
    $this->get(route('prints.session', $f['session']))->assertOk()->assertSee('Original print setup')->assertDontSee('Later setup')->assertDontSee('9.876')->assertSee('2 tacche')->assertSee('1390 mm')->assertSee('1040 mm')->assertSee('4 :1');
});

test('setup prints preserve units sides and zeros without exposing inactive geometry values', function () {
    $rows = app(PrintDocumentService::class)->setupRows(['camber_mode' => 'holes', 'geometry_side_mode' => 'separate', 'camber_front_left_position' => '2', 'camber_front_right_position' => '3', 'camber_front_deg' => 9.876, 'caster_front_left_deg' => 0, 'rear_width_mode' => 'axle', 'rear_axle_length_mm' => 1040, 'track_width_rear_mm' => 9999], true);
    $values = array_column($rows, 'value');
    expect($values)->toContain('SX 2 fori · DX 3 fori', 'SX 0 °', 'Assale 1040 mm')->not->toContain('9.876 °', '9999 mm');
});

test('pressure prints keep each measurement separate and do not fill missing tyres from setup targets', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $f = printableFixture($user);
    $cold = $f['session']->tyreReadings()->create(['phase' => 'cold', 'pressures' => ['fl' => 0.81], 'measured_at' => '2026-11-01 10:00:00', 'created_by' => $user->id]);
    $hot = $f['session']->tyreReadings()->create(['phase' => 'hot', 'pressures' => ['fr' => 1.12], 'measured_at' => '2026-11-01 10:20:00', 'created_by' => $user->id]);
    $this->get(route('prints.tyres', $f['session']))->assertOk()->assertSeeInOrder(['data-print-reading="'.$cold->id.'"', '0.81', 'data-print-reading="'.$hot->id.'"', '1.12'], false)->assertDontSee('0.80');
});

test('reports show every lap and find the best valid time with integer millisecond precision', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $f = printableFixture($user);
    for ($i = 1; $i <= 80; $i++) {
        $f['session']->timingLaps()->create(['vehicle_id' => $f['vehicle']->id, 'lap_number' => $i, 'lap_time_ms' => 60000 + $i, 'is_valid' => $i !== 1, 'source' => 'manual']);
    }
    $response = $this->get(route('prints.session', $f['session']))->assertOk()->assertSee('1:00.002')->assertSee('1:00.080');
    expect(substr_count($response->getContent(), 'data-print-lap='))->toBe(80);
});

test('weather prints distinguish manual historical and forecast data without fetching or inventing track temperature', function () {
    Http::preventStrayRequests();
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $f = printableFixture($user);
    $f['session']->weatherObservations()->create(['values' => ['track_temperature_c' => 27, 'air_temperature_c' => 20], 'measured_at' => now(), 'created_by' => $user->id]);
    $f['session']->forceFill(['weather_snapshot' => ['air_temperature_c' => 18, 'track_temperature_c' => 99], 'historical_weather_snapshot' => ['air_temperature_c' => 17, 'session_started_at' => '2026-10-30 10:00:00']])->save();
    $this->get(route('prints.session', $f['session']))->assertOk()->assertSee('27 °C')->assertSee('MET Norway')->assertSee('NASA POWER')->assertSee(__('prints.history_mismatch'))->assertDontSee('99 °C');
    $f['session']->weatherObservations()->create(['values' => [], 'is_reset' => true, 'measured_at' => now(), 'created_by' => $user->id]);
    $this->get(route('prints.session', $f['session']))->assertOk()->assertDontSee('27 °C');
    Http::assertNothingSent();
});

test('print routes protect private records and remain available to read only members', function () {
    $owner = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($owner);
    $f = printableFixture($owner);
    $routes = [route('prints.vehicle', $f['vehicle']), route('prints.setup', $f['setup']), route('prints.session', $f['session']), route('prints.tyres', $f['session'])];
    $viewer = User::factory()->withDatabaseAccess()->create();
    $owner->workspaces()->firstOrFail()->users()->attach($viewer, ['role' => 'viewer', 'status' => 'active']);
    $this->actingAs($viewer);
    foreach ($routes as $route) {
        $this->get($route)->assertNotFound();
    }
    app(WorkspaceContext::class)->select($viewer, $owner->workspaces()->firstOrFail());
    foreach ($routes as $route) {
        $this->get($route)->assertOk();
    }
    $blocked = User::factory()->create();
    $owner->workspaces()->firstOrFail()->users()->attach($blocked, ['role' => 'viewer', 'status' => 'active']);
    $this->actingAs($blocked);
    app(WorkspaceContext::class)->select($blocked, $owner->workspaces()->firstOrFail());
    foreach ($routes as $route) {
        $this->get($route)->assertForbidden();
    }
    auth()->logout();
    foreach ($routes as $route) {
        $this->get($route)->assertRedirect(route('login'));
    }
});

test('empty reports state missing data and all print links are available without editing permissions', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    $f = printableFixture($user);
    $this->get(route('prints.session', $f['session']))->assertOk()->assertSee(__('prints.no_laps'))->assertSee(__('prints.no_pressures'))->assertSee(__('prints.no_weather'));
    $this->get(route('garage.index'))->assertOk()->assertSee(route('prints.vehicle', $f['vehicle']), false);
    $this->get(route('setups.index'))->assertOk()->assertSee(route('prints.setup', $f['setup']), false);
    $this->get(route('sessions.index'))->assertOk()->assertSee(route('prints.session', $f['session']), false)->assertSee(route('prints.tyres', $f['session']), false);
    $blank = Session::create(['vehicle_id' => $f['vehicle']->id, 'configuration_version_id' => $f['session']->configuration_version_id, 'session_type' => 'practice', 'status' => 'draft', 'started_at' => now(), 'created_by' => $user->id]);
    $this->get(route('prints.session', $blank))->assertOk()->assertSee(__('prints.no_snapshot'))->assertDontSee('Original print setup');
    $f['vehicle']->update(['category' => 'car']);
    $this->get(route('prints.tyres', $f['session']))->assertNotFound();
});
