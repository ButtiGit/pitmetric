<?php

use App\Models\Circuit;
use App\Models\FollowUpTask;
use App\Models\TrackCapture;
use App\Models\User;

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

test('invalid lap times are rejected without creating captures', function () {
    $this->post(route('quick-captures.lap.store'), [
        'circuit_name' => 'Test Track',
        'lap_time' => '1:99.000',
    ])->assertSessionHasErrors('lap_time');

    expect(TrackCapture::query()->count())->toBe(0);
});
