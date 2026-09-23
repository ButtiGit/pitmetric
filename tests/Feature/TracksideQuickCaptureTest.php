<?php

use App\Models\Circuit;
use App\Models\TracksideCapture;
use App\Models\User;

it('saves a lap immediately when the circuit does not exist yet', function () {
    $user = User::factory()->withDatabaseAccess()->create();

    $response = $this->actingAs($user)->post(route('timing.quick.store'), [
        'lap_time' => '1:02.345',
        'circuit_name' => 'Temporary Track',
        'driver_name' => 'Driver just off the kart',
        'vehicle_name' => 'Kart 27',
        'notes' => 'Rear grip low',
    ]);

    $response->assertRedirect(route('timing.index'));
    $response->assertSessionHasNoErrors();

    $capture = TracksideCapture::query()->firstOrFail();

    expect($capture->lap_time_ms)->toBe(62345)
        ->and($capture->circuit_name)->toBe('Temporary Track')
        ->and($capture->circuit_layout_id)->toBeNull()
        ->and($capture->status)->toBe('pending');

    expect($user->fresh()->unreadNotifications()->count())->toBe(1)
        ->and($user->fresh()->unreadNotifications()->firstOrFail()->data['alert_key'])->toBe('trackside-capture:'.$capture->id);

    $this->actingAs($user)
        ->get(route('timing.index'))
        ->assertOk()
        ->assertSee('Temporary Track')
        ->assertSee('1:02.345');

    $this->actingAs($user)
        ->get(route('circuits.index'))
        ->assertOk()
        ->assertSee('Temporary Track')
        ->assertSee('Needs completion');
});

it('links pending lap captures automatically when the missing circuit is created', function () {
    $user = User::factory()->withDatabaseAccess()->create();

    $this->actingAs($user)->post(route('timing.quick.store'), [
        'lap_time' => '58,901',
        'circuit_name' => 'Later Circuit',
    ])->assertSessionHasNoErrors();

    $capture = TracksideCapture::query()->firstOrFail();
    expect($capture->status)->toBe('pending');

    $this->actingAs($user)->post(route('circuits.store'), [
        'name' => 'Later Circuit',
        'country' => 'Italy',
        'layout_name' => 'Full',
        'length_meters' => 1175,
    ])->assertSessionHasNoErrors();

    $capture->refresh();
    $circuit = Circuit::query()->where('name', 'Later Circuit')->firstOrFail();

    expect($capture->lap_time_ms)->toBe(58901)
        ->and($capture->status)->toBe('resolved')
        ->and($capture->resolved_at)->not->toBeNull()
        ->and($capture->circuit_layout_id)->toBe($circuit->layouts()->firstOrFail()->id)
        ->and($user->fresh()->unreadNotifications()->count())->toBe(0);
});

it('auto links a quick lap when an unambiguous circuit already exists', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user);

    $circuit = Circuit::query()->create(['name' => 'Existing Circuit']);
    $layout = $circuit->layouts()->create([
        'name' => 'Full',
        'length_meters' => 950,
        'is_active' => true,
    ]);

    $this->post(route('timing.quick.store'), [
        'lap_time' => '47.500',
        'circuit_name' => 'existing circuit',
    ])->assertSessionHasNoErrors();

    $capture = TracksideCapture::query()->firstOrFail();

    expect($capture->status)->toBe('resolved')
        ->and($capture->circuit_layout_id)->toBe($layout->id)
        ->and($user->fresh()->unreadNotifications()->count())->toBe(0);
});
