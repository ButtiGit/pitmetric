<?php

use App\Models\Circuit;
use App\Models\Component;
use App\Models\ComponentType;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\FollowUpTask;
use App\Models\TechnicalSetup;
use App\Models\TrackCapture;
use App\Models\TrackCaptureReference;
use App\Models\User;
use App\Models\Vehicle;

beforeEach(function () {
    $this->operator = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($this->operator);
});

test('a lap can be captured before its circuit exists', function () {
    $this->post(route('quick-captures.lap.store'), [
        'circuit_name' => 'Lonato South',
        'lap_time' => '1:02.345',
        'notes' => 'Fresh tyres',
    ])->assertSessionHasNoErrors()->assertRedirect();

    $capture = TrackCapture::query()->firstOrFail();

    expect($capture->circuit_id)->toBeNull()
        ->and($capture->lap_time_ms)->toBe(62345)
        ->and($capture->status)->toBe('needs_attention')
        ->and($capture->formattedLapTime())->toBe('1:02.345');

    expect(FollowUpTask::query()->where('status', 'open')->count())->toBe(1);
});

test('repeated laps on the same missing circuit create one follow up alert', function () {
    foreach (['52.100', '51.950', '51.800'] as $lap) {
        $this->post(route('quick-captures.lap.store'), [
            'circuit_name' => 'Cremona Karting',
            'lap_time' => $lap,
        ])->assertSessionHasNoErrors();
    }

    expect(TrackCapture::query()->count())->toBe(3)
        ->and(FollowUpTask::query()->where('status', 'open')->count())->toBe(1);
});

test('creating the missing circuit automatically resolves its captured laps and alert', function () {
    $this->post(route('quick-captures.lap.store'), [
        'circuit_name' => 'South Garda Karting',
        'lap_time' => '47.321',
    ])->assertSessionHasNoErrors();

    $this->post(route('circuits.store'), [
        'name' => 'South Garda Karting',
        'country' => 'Italy',
        'layout_name' => 'Full',
        'length_meters' => 1200,
    ])->assertSessionHasNoErrors();

    $circuit = Circuit::query()->where('name', 'South Garda Karting')->firstOrFail();
    $layout = $circuit->layouts()->firstOrFail();
    $capture = TrackCapture::query()->firstOrFail();
    $task = FollowUpTask::query()->firstOrFail();

    expect($capture->status)->toBe('ready')
        ->and($capture->circuit_id)->toBe($circuit->id)
        ->and($capture->circuit_layout_id)->toBe($layout->id)
        ->and($capture->resolved_at)->not->toBeNull()
        ->and($task->status)->toBe('completed')
        ->and($task->completed_at)->not->toBeNull();
});

test('quick capture accepts missing driver vehicle configuration setup and component', function () {
    $this->post(route('circuits.store'), [
        'name' => 'Adria',
        'layout_name' => 'Full',
        'length_meters' => 1302,
    ])->assertSessionHasNoErrors();

    $this->post(route('quick-captures.lap.store'), [
        'circuit_name' => 'Adria',
        'lap_time' => '55.432',
        'driver_name' => 'Simone Test',
        'vehicle_name' => 'Kart 12',
        'configuration_name' => 'Race build',
        'technical_setup_name' => 'Dry 1',
        'component_names' => 'Motore A',
    ])->assertSessionHasNoErrors();

    $capture = TrackCapture::query()->firstOrFail();

    expect($capture->status)->toBe('needs_attention')
        ->and(TrackCaptureReference::query()->where('status', 'pending')->count())->toBe(5)
        ->and(FollowUpTask::query()->where('status', 'open')->count())->toBe(5);

    $workspace = $this->operator->workspaces()->firstOrFail();
    Driver::create(['display_name' => 'Simone Test']);
    $vehicle = Vehicle::factory()->create(['workspace_id' => $workspace->getKey(), 'name' => 'Kart 12']);
    Configuration::create(['vehicle_id' => $vehicle->getKey(), 'name' => 'Race build', 'status' => 'active']);
    TechnicalSetup::create([
        'vehicle_id' => $vehicle->getKey(),
        'name' => 'Dry 1',
        'values' => [],
        'status' => 'active',
        'created_by' => $this->operator->getKey(),
    ]);
    $type = ComponentType::create(['name' => 'Engine']);
    Component::create([
        'component_type_id' => $type->getKey(),
        'name' => 'Motore A',
        'status' => 'active',
    ]);

    expect(TrackCaptureReference::query()->where('status', 'pending')->count())->toBe(0)
        ->and(TrackCaptureReference::query()->where('status', 'resolved')->count())->toBe(5)
        ->and(FollowUpTask::query()->where('status', 'open')->count())->toBe(0)
        ->and(FollowUpTask::query()->where('status', 'completed')->count())->toBe(5)
        ->and($capture->fresh()->status)->toBe('ready')
        ->and($capture->fresh()->resolved_at)->not->toBeNull();
});

test('existing trackside context links immediately without creating an alert', function () {
    $this->post(route('circuits.store'), [
        'name' => 'Franciacorta',
        'layout_name' => 'Full',
        'length_meters' => 1200,
    ])->assertSessionHasNoErrors();
    $driver = Driver::create(['display_name' => 'Existing Driver']);

    $this->post(route('quick-captures.lap.store'), [
        'circuit_name' => 'Franciacorta',
        'lap_time' => '49.900',
        'driver_name' => 'Existing Driver',
    ])->assertSessionHasNoErrors();

    $reference = TrackCaptureReference::query()->firstOrFail();

    expect($reference->status)->toBe('resolved')
        ->and($reference->reference_id)->toBe($driver->getKey())
        ->and(FollowUpTask::query()->count())->toBe(0)
        ->and(TrackCapture::query()->firstOrFail()->status)->toBe('ready');
});

test('resolving the circuit does not hide other missing trackside context', function () {
    $this->post(route('quick-captures.lap.store'), [
        'circuit_name' => 'Track Later',
        'lap_time' => '59.100',
        'driver_name' => 'Driver Later',
    ])->assertSessionHasNoErrors();

    $this->post(route('circuits.store'), [
        'name' => 'Track Later',
        'layout_name' => 'Full',
        'length_meters' => 1000,
    ])->assertSessionHasNoErrors();

    $capture = TrackCapture::query()->firstOrFail();
    expect($capture->status)->toBe('needs_attention')
        ->and($capture->circuit_id)->not->toBeNull()
        ->and(FollowUpTask::query()->where('kind', 'missing_circuit')->where('status', 'completed')->count())->toBe(1)
        ->and(FollowUpTask::query()->where('kind', 'missing_driver')->where('status', 'open')->count())->toBe(1);

    Driver::create(['display_name' => 'Driver Later']);

    expect($capture->fresh()->status)->toBe('ready')
        ->and(FollowUpTask::query()->where('status', 'open')->count())->toBe(0);
});

test('repeated missing context is deduplicated in the follow up inbox', function () {
    $this->post(route('circuits.store'), [
        'name' => 'Dedup Track',
        'layout_name' => 'Full',
        'length_meters' => 900,
    ])->assertSessionHasNoErrors();

    foreach (['50.100', '49.900'] as $lap) {
        $this->post(route('quick-captures.lap.store'), [
            'circuit_name' => 'Dedup Track',
            'lap_time' => $lap,
            'driver_name' => 'Same Missing Driver',
        ])->assertSessionHasNoErrors();
    }

    expect(TrackCaptureReference::query()->where('kind', 'driver')->count())->toBe(2)
        ->and(FollowUpTask::query()->where('kind', 'missing_driver')->where('status', 'open')->count())->toBe(1);
});

test('invalid lap times are rejected without creating captures', function () {
    $this->post(route('quick-captures.lap.store'), [
        'circuit_name' => 'Test Track',
        'lap_time' => '1:99.000',
    ])->assertSessionHasErrors('lap_time');

    expect(TrackCapture::query()->count())->toBe(0);
});
