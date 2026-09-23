<?php

use App\Models\Circuit;
use App\Models\FollowUpTask;
use App\Models\TrackCapture;
use App\Models\TrackCaptureReference;
use App\Models\User;

beforeEach(function () {
    $this->operator = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($this->operator);
});

test('pit mode exposes five trackside quick actions', function () {
    $this->get(route('pit-mode.index'))
        ->assertOk()
        ->assertSee('id="pit-lap"', false)
        ->assertSee('id="pit-pressure"', false)
        ->assertSee('id="pit-issue"', false)
        ->assertSee('id="pit-component"', false)
        ->assertSee('id="pit-note"', false);
});

test('pit context is remembered per workspace and reused by pressure captures', function () {
    Circuit::create(['name' => 'Pit Test Track', 'country' => 'Italy']);

    $this->post(route('pit-mode.context.update'), [
        'circuit_name' => 'Pit Test Track',
        'driver_name' => 'Track Driver',
        'vehicle_name' => 'Kart 12',
        'configuration_name' => 'Race build',
        'technical_setup_name' => 'Dry 1',
    ])->assertSessionHasNoErrors()->assertRedirect(route('pit-mode.index'));

    $this->post(route('pit-mode.pressures.store'), [
        'pressure_fl' => '0.82',
        'pressure_fr' => '0.84',
        'pressure_rl' => '0.80',
        'pressure_rr' => '0.81',
        'unit' => 'bar',
        'notes' => 'Hot pressures',
    ])->assertSessionHasNoErrors()->assertRedirect();

    $capture = TrackCapture::query()->firstOrFail();

    expect($capture->kind)->toBe('tyre_pressure')
        ->and($capture->circuit?->name)->toBe('Pit Test Track')
        ->and($capture->payload)->toMatchArray([
            'unit' => 'bar',
            'fl' => 0.82,
            'fr' => 0.84,
            'rl' => 0.80,
            'rr' => 0.81,
        ])
        ->and($capture->notes)->toBe('Hot pressures')
        ->and($capture->status)->toBe('needs_attention')
        ->and(TrackCaptureReference::query()->where('status', 'pending')->count())->toBe(4)
        ->and(FollowUpTask::query()->where('status', 'open')->count())->toBe(4);
});

test('issues and notes can be captured before the full database context exists', function () {
    $this->post(route('pit-mode.context.update'), [
        'circuit_name' => 'Unknown Track',
        'vehicle_name' => 'Unknown Kart',
    ])->assertSessionHasNoErrors();

    $this->post(route('pit-mode.issues.store'), [
        'issue' => 'Rear vibration under braking',
        'severity' => 'critical',
    ])->assertSessionHasNoErrors();

    $this->post(route('pit-mode.notes.store'), [
        'note' => 'Try two clicks more front bar after lunch',
    ])->assertSessionHasNoErrors();

    $issue = TrackCapture::query()->where('kind', 'issue')->firstOrFail();
    $note = TrackCapture::query()->where('kind', 'note')->firstOrFail();

    expect($issue->payload)->toMatchArray(['severity' => 'critical'])
        ->and($issue->notes)->toBe('Rear vibration under braking')
        ->and($note->notes)->toBe('Try two clicks more front bar after lunch')
        ->and(FollowUpTask::query()->where('kind', 'missing_circuit')->where('status', 'open')->count())->toBe(1)
        ->and(FollowUpTask::query()->where('kind', 'missing_vehicle')->where('status', 'open')->count())->toBe(1);
});

test('component changes preserve removed and installed roles while deferring missing records', function () {
    $this->post(route('pit-mode.context.update'), [
        'vehicle_name' => 'Kart 77',
    ])->assertSessionHasNoErrors();

    $this->post(route('pit-mode.component-changes.store'), [
        'removed_component' => 'Chain A',
        'installed_component' => 'Chain B',
        'notes' => 'Changed after heat two',
    ])->assertSessionHasNoErrors();

    $capture = TrackCapture::query()->where('kind', 'component_change')->firstOrFail();

    expect($capture->payload)->toMatchArray([
        'removed_component' => 'Chain A',
        'installed_component' => 'Chain B',
    ])
        ->and($capture->notes)->toBe('Changed after heat two')
        ->and(TrackCaptureReference::query()->where('kind', 'vehicle')->count())->toBe(1)
        ->and(TrackCaptureReference::query()->where('kind', 'component')->count())->toBe(2)
        ->and(FollowUpTask::query()->where('status', 'open')->count())->toBe(3);
});

test('pit lap capture returns to pit mode', function () {
    $this->post(route('quick-captures.lap.store'), [
        'pit_mode' => '1',
        'circuit_name' => 'Pit Lap Track',
        'lap_time' => '49.321',
    ])->assertSessionHasNoErrors()->assertRedirect(route('pit-mode.index', ['captured' => 1]));

    expect(TrackCapture::query()->firstOrFail()->kind)->toBe('lap_time');
});
