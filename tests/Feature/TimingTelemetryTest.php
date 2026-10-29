<?php

use App\Models\Circuit;
use App\Models\Configuration;
use App\Models\ConfigurationVersion;
use App\Models\Driver;
use App\Models\Session;
use App\Models\TelemetryImport;
use App\Models\TelemetrySample;
use App\Models\TimingLap;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

function makeTelemetrySession(User $user): array
{
    $workspace = $user->workspaces()->firstOrFail();
    $vehicle = Vehicle::factory()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Kart telemetry #12',
        'category' => 'kart',
    ]);
    $configuration = Configuration::query()->create([
        'vehicle_id' => $vehicle->id,
        'name' => 'Telemetry baseline',
        'status' => 'active',
    ]);
    $version = ConfigurationVersion::query()->create([
        'configuration_id' => $configuration->id,
        'version_number' => 1,
        'created_by' => $user->id,
    ]);
    $circuit = Circuit::query()->create(['name' => 'Telemetry Ring']);
    $layout = $circuit->layouts()->create([
        'name' => 'Full',
        'length_meters' => 1250,
        'is_active' => true,
    ]);
    $session = Session::query()->create([
        'vehicle_id' => $vehicle->id,
        'configuration_version_id' => $version->id,
        'circuit_layout_id' => $layout->id,
        'session_type' => 'practice',
        'started_at' => '2026-09-18 09:00:00',
        'completed_laps' => 2,
        'status' => 'finalized',
        'finalized_at' => '2026-09-18 09:10:00',
        'created_by' => $user->id,
    ]);

    return [$workspace, $vehicle, $layout, $session];
}

it('imports csv telemetry into normalized samples and timing laps', function () {
    Storage::fake('local');

    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    [, $vehicle, $layout, $session] = makeTelemetrySession($user);
    $driver = Driver::query()->create([
        'display_name' => 'Test Driver',
        'racing_number' => '12',
        'status' => 'active',
    ]);

    $csv = implode("\n", [
        'Time,Speed,Lap,Lap Time,RPM,Throttle,Brake',
        '0.000,12.5,1,60.000,5200,45,0',
        '1.000,28.0,1,60.000,6100,73,0',
        '2.000,31.0,2,59.500,6400,82,4',
        '3.000,42.5,2,59.500,7100,100,0',
    ]);

    $response = $this->post(route('telemetry.store'), [
        'session_id' => $session->id,
        'driver_id' => $driver->id,
        'source_vendor' => 'racebox',
        'telemetry_file' => UploadedFile::fake()->createWithContent('racebox.csv', $csv),
    ]);

    $response->assertSessionHasNoErrors();

    $import = TelemetryImport::query()->firstOrFail();
    expect($import->sample_count)->toBe(4)
        ->and($import->lap_count)->toBe(2)
        ->and($import->vehicle_id)->toBe($vehicle->id)
        ->and($import->circuit_layout_id)->toBe($layout->id)
        ->and($import->channel_keys)->toContain('rpm', 'throttle', 'brake')
        ->and(TelemetrySample::query()->count())->toBe(4)
        ->and(TimingLap::query()->orderBy('lap_number')->pluck('lap_time_ms')->all())->toBe([60000, 59500]);

    Storage::disk('local')->assertExists($import->storage_path);
});

it('reuses an identical telemetry upload in the same session without creating duplicate samples or laps', function () {
    Storage::fake('local');

    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    [$workspace, , , $session] = makeTelemetrySession($user);
    $csv = implode("\n", [
        'Time,Speed,Lap,Lap Time',
        '0.000,25.0,1,60.000',
        '1.000,30.0,1,60.000',
    ]);

    for ($attempt = 0; $attempt < 2; $attempt++) {
        $response = $this->post(route('telemetry.store'), [
            'session_id' => $session->id,
            'source_vendor' => 'generic',
            'telemetry_file' => UploadedFile::fake()->createWithContent('session.csv', $csv),
        ]);

        $response->assertSessionHasNoErrors();
    }

    $response->assertSessionHas('status', __('This telemetry file was already imported. The existing recording was reused.'));

    expect(TelemetryImport::query()->count())->toBe(1)
        ->and(TelemetrySample::query()->count())->toBe(2)
        ->and(TimingLap::query()->count())->toBe(1)
        ->and(Storage::disk('local')->allFiles('telemetry/'.$workspace->id))->toHaveCount(1);

    $anotherSession = $session->replicate();
    $anotherSession->started_at = now();
    $anotherSession->save();

    $this->post(route('telemetry.store'), [
        'session_id' => $anotherSession->id,
        'source_vendor' => 'generic',
        'telemetry_file' => UploadedFile::fake()->createWithContent('session.csv', $csv),
    ])->assertSessionHasNoErrors();

    expect(TelemetryImport::query()->count())->toBe(2)
        ->and(TimingLap::query()->count())->toBe(2);
});

it('keeps an existing manual lap when an import has the same lap number and exact time', function () {
    Storage::fake('local');

    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    [, $vehicle, $layout, $session] = makeTelemetrySession($user);
    $manual = TimingLap::query()->create([
        'session_id' => $session->id,
        'vehicle_id' => $vehicle->id,
        'circuit_layout_id' => $layout->id,
        'lap_number' => 1,
        'lap_time_ms' => 60000,
        'is_valid' => true,
        'source' => 'manual',
    ]);

    $this->post(route('telemetry.store'), [
        'session_id' => $session->id,
        'source_vendor' => 'generic',
        'telemetry_file' => UploadedFile::fake()->createWithContent('overlap.csv', implode("\n", [
            'Time,Speed,Lap,Lap Time',
            '0.000,25.0,1,60.000',
            '1.000,30.0,1,60.000',
            '2.000,30.0,2,59.500',
            '3.000,32.0,2,59.500',
        ])),
    ])->assertSessionHasNoErrors();

    $import = TelemetryImport::query()->sole();

    expect($import->sample_count)->toBe(4)
        ->and($import->lap_count)->toBe(1)
        ->and($manual->fresh()->source)->toBe('manual')
        ->and($manual->fresh()->telemetry_import_id)->toBeNull()
        ->and(TimingLap::query()->count())->toBe(2)
        ->and(TimingLap::query()->where('source', 'import')->sole()->lap_number)->toBe(2)
        ->and(TimingLap::query()->where('source', 'import')->sole()->lap_time_ms)->toBe(59500);
});

it('only deduplicates laps with matching session, driver, lap number and exact duration', function () {
    Storage::fake('local');

    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    [, $vehicle, $layout, $session] = makeTelemetrySession($user);
    $driver = Driver::query()->create(['display_name' => 'Telemetry Driver', 'status' => 'active']);
    $otherDriver = Driver::query()->create(['display_name' => 'Other Driver', 'status' => 'active']);

    foreach ([
        ['lap_number' => 1, 'lap_time_ms' => 60000, 'driver_id' => $otherDriver->id],
        ['lap_number' => 2, 'lap_time_ms' => 59500, 'driver_id' => $driver->id],
        ['lap_number' => 3, 'lap_time_ms' => 60000, 'driver_id' => $driver->id],
    ] as $lap) {
        TimingLap::query()->create($lap + [
            'session_id' => $session->id,
            'vehicle_id' => $vehicle->id,
            'circuit_layout_id' => $layout->id,
            'source' => 'manual',
            'is_valid' => true,
        ]);
    }

    $this->post(route('telemetry.store'), [
        'session_id' => $session->id,
        'driver_id' => $driver->id,
        'source_vendor' => 'generic',
        'telemetry_file' => UploadedFile::fake()->createWithContent('distinct.csv', implode("\n", [
            'Time,Speed,Lap,Lap Time',
            '0.000,25.0,1,60.000',
            '1.000,30.0,1,60.000',
            '2.000,30.0,2,59.000',
            '3.000,32.0,2,59.000',
        ])),
    ])->assertSessionHasNoErrors();

    expect(TelemetryImport::query()->sole()->lap_count)->toBe(2)
        ->and(TimingLap::query()->where('source', 'import')->count())->toBe(2)
        ->and(TimingLap::query()->count())->toBe(5);
});

it('deduplicates overlapping laps from different telemetry files while retaining both recordings', function () {
    Storage::fake('local');

    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    [, , , $session] = makeTelemetrySession($user);

    foreach ([
        implode("\n", ['Time,Speed,Lap,Lap Time', '0.000,25,1,60.000', '1.000,30,1,60.000']),
        implode("\n", ['Time,Speed,Lap,Lap Time', '0.000,26,1,60.000', '1.000,31,1,60.000', '2.000,32,2,59.000', '3.000,35,2,59.000']),
    ] as $csv) {
        $this->post(route('telemetry.store'), [
            'session_id' => $session->id,
            'source_vendor' => 'generic',
            'telemetry_file' => UploadedFile::fake()->createWithContent('export.csv', $csv),
        ])->assertSessionHasNoErrors();
    }

    expect(TelemetryImport::query()->count())->toBe(2)
        ->and(TelemetrySample::query()->count())->toBe(6)
        ->and(TimingLap::query()->count())->toBe(2)
        ->and(TelemetryImport::query()->orderBy('id')->pluck('lap_count')->all())->toBe([1, 1]);
});

it('imports racelogic vbo coordinates stored as total minutes', function () {
    Storage::fake('local');

    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    [, , , $session] = makeTelemetrySession($user);
    $driver = Driver::query()->create([
        'display_name' => 'VBOX Driver',
        'status' => 'active',
    ]);

    $vbo = implode("\n", [
        '[header]',
        'File created for telemetry test',
        '[column names]',
        'time latitude longitude velocity heading lap',
        '[data]',
        '090000.00 3119.24579 58.82246 50.0 90.0 1',
        '090001.00 3119.24589 58.82256 55.0 91.0 1',
    ]);

    $response = $this->post(route('telemetry.store'), [
        'session_id' => $session->id,
        'driver_id' => $driver->id,
        'source_vendor' => 'vbox',
        'telemetry_file' => UploadedFile::fake()->createWithContent('session.vbo', $vbo),
    ]);

    $response->assertSessionHasNoErrors();

    $import = TelemetryImport::query()->firstOrFail();
    $sample = TelemetrySample::query()->orderBy('sequence')->firstOrFail();

    expect($import->source_format)->toBe('vbo')
        ->and($import->sample_count)->toBe(2)
        ->and($import->lap_count)->toBe(1)
        ->and($sample->speed_kmh)->toBe(50.0);

    $this->assertEqualsWithDelta(51.9874298333, $sample->latitude, 0.0000001);
    $this->assertEqualsWithDelta(-0.9803743333, $sample->longitude, 0.0000001);
});

it('filters timing by driver without leaking other workspace data', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    [, $vehicle, $layout, $session] = makeTelemetrySession($user);
    $driver = Driver::query()->create(['display_name' => 'Visible Driver', 'status' => 'active']);

    TimingLap::query()->create([
        'session_id' => $session->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicle->id,
        'circuit_layout_id' => $layout->id,
        'lap_number' => 1,
        'lap_time_ms' => 61234,
        'is_valid' => true,
        'source' => 'manual',
    ]);

    $otherUser = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($otherUser);
    [, $otherVehicle, $otherLayout, $otherSession] = makeTelemetrySession($otherUser);
    $otherDriver = Driver::query()->create(['display_name' => 'Hidden Driver', 'status' => 'active']);
    TimingLap::query()->create([
        'session_id' => $otherSession->id,
        'driver_id' => $otherDriver->id,
        'vehicle_id' => $otherVehicle->id,
        'circuit_layout_id' => $otherLayout->id,
        'lap_number' => 1,
        'lap_time_ms' => 58000,
        'is_valid' => true,
        'source' => 'manual',
    ]);

    $this->actingAs($user)
        ->get(route('timing.index', ['driver_id' => $driver->id]))
        ->assertOk()
        ->assertSee('Visible Driver')
        ->assertSee('1:01.234')
        ->assertDontSee('Hidden Driver');
});

it('records a manual lap in the existing timing archive and numbers it after imported laps', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    [$workspace, $vehicle, $layout, $session] = makeTelemetrySession($user);
    $driver = Driver::query()->create(['display_name' => 'Manual Lap Driver', 'status' => 'active']);

    TimingLap::query()->create([
        'session_id' => $session->id,
        'driver_id' => $driver->id,
        'vehicle_id' => $vehicle->id,
        'circuit_layout_id' => $layout->id,
        'lap_number' => 2,
        'lap_time_ms' => 63456,
        'is_valid' => true,
        'source' => 'import',
    ]);

    $this->post(route('timing.laps.store'), [
        'session_id' => $session->id,
        'driver_id' => $driver->id,
        'lap_time' => '1:02,345',
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('timing.index', ['session_id' => $session->id]));

    $manualLap = TimingLap::query()->where('source', 'manual')->sole();

    expect($manualLap->workspace_id)->toBe($workspace->id)
        ->and($manualLap->session_id)->toBe($session->id)
        ->and($manualLap->vehicle_id)->toBe($vehicle->id)
        ->and($manualLap->circuit_layout_id)->toBe($layout->id)
        ->and($manualLap->driver_id)->toBe($driver->id)
        ->and($manualLap->lap_number)->toBe(3)
        ->and($manualLap->lap_time_ms)->toBe(62345)
        ->and($manualLap->is_valid)->toBeTrue()
        ->and($manualLap->telemetry_import_id)->toBeNull()
        ->and(TimingLap::query()->count())->toBe(2)
        ->and($session->fresh()->completed_laps)->toBe(2);

    $this->get(route('timing.index', ['session_id' => $session->id]))
        ->assertOk()
        ->assertSee('1:02.345')
        ->assertSee('Manual Lap Driver')
        ->assertSee('create-timing-lap');
});

it('accepts lap times entered directly as decimal seconds', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    [, , , $session] = makeTelemetrySession($user);

    $this->post(route('timing.laps.store'), [
        'session_id' => $session->id,
        'lap_time' => '62.345',
    ])->assertSessionHasNoErrors();

    $lap = TimingLap::query()->sole();

    expect($lap->lap_time_ms)->toBe(62345)
        ->and($lap->lap_number)->toBe(1)
        ->and($lap->driver_id)->toBeNull();
});

it('rejects invalid manual lap times without creating timing records', function (string $lapTime) {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    [, , , $session] = makeTelemetrySession($user);

    $this->post(route('timing.laps.store'), [
        'session_id' => $session->id,
        'lap_time' => $lapTime,
    ])->assertSessionHasErrors('lap_time');

    expect(TimingLap::query()->count())->toBe(0);
})->with(['not-a-time', '1:70.000', '-2.000', '0', '1:02.3456', '3601']);

it('prevents manual lap insertion into another workspace or with its drivers', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);
    [, , , $session] = makeTelemetrySession($user);

    $otherUser = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($otherUser);
    [, , , $otherSession] = makeTelemetrySession($otherUser);
    $otherDriver = Driver::query()->create(['display_name' => 'Other Workspace Driver', 'status' => 'active']);

    $this->actingAs($user);

    $this->post(route('timing.laps.store'), [
        'session_id' => $otherSession->id,
        'lap_time' => '62.345',
    ])->assertNotFound();

    $this->post(route('timing.laps.store'), [
        'session_id' => $session->id,
        'driver_id' => $otherDriver->id,
        'lap_time' => '62.345',
    ])->assertNotFound();

    expect(TimingLap::query()->count())->toBe(0);
});

it('keeps manual lap entry read only for viewer team members', function () {
    $owner = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($owner);
    [$workspace, , , $session] = makeTelemetrySession($owner);
    $viewer = User::factory()->create();
    $viewer->forceFill(['database_access_enabled' => true])->save();

    DB::table('workspace_user')->insert([
        'workspace_id' => $workspace->id,
        'user_id' => $viewer->id,
        'role' => 'viewer',
        'status' => 'active',
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($viewer)
        ->get(route('timing.index'))
        ->assertOk();

    $this->post(route('timing.laps.store'), [
        'session_id' => $session->id,
        'lap_time' => '62.345',
    ])->assertForbidden();

    expect(TimingLap::query()->count())->toBe(0);
});
