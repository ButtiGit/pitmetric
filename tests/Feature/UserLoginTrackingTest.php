<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;

test('a successful web login records and advances the last login without treating page views as logins', function () {
    $user = User::factory()->create(['last_login_at' => '2026-11-01 08:00:00']);
    $this->travelTo(now()->setDateTime(2026, 11, 2, 10, 25));
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at->toDateTimeString())->toBe('2026-11-02 10:25:00');
    $this->travel(10)->minutes();
    $this->get(route('dashboard'))->assertOk();
    expect($user->fresh()->last_login_at->toDateTimeString())->toBe('2026-11-02 10:25:00');
});

test('a failed web login preserves the last successful login', function () {
    $user = User::factory()->create(['last_login_at' => '2026-11-01 08:00:00']);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'incorrect'])->assertSessionHasErrors('email');
    $this->assertGuest();
    expect($user->fresh()->last_login_at->toDateTimeString())->toBe('2026-11-01 08:00:00');
});

test('mobile login and registration record successful access while invalid credentials do not', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $this->travelTo(now()->setDateTime(2026, 11, 2, 10, 25));
    $token = $this->postJson('/api/mobile/login', ['email' => $user->email, 'password' => 'password'])->assertOk()->json('token');
    expect($user->fresh()->last_login_at->toDateTimeString())->toBe('2026-11-02 10:25:00');
    $this->travel(10)->minutes();
    $this->withToken($token)->getJson('/api/mobile/me')->assertOk();
    $this->postJson('/api/mobile/login', ['email' => $user->email, 'password' => 'incorrect'])->assertUnprocessable();
    expect($user->fresh()->last_login_at->toDateTimeString())->toBe('2026-11-02 10:25:00')
        ->and($other->fresh()->last_login_at)->toBeNull();
    $this->postJson('/api/mobile/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
    expect($user->fresh()->last_login_at->toDateTimeString())->toBe('2026-11-02 10:35:00');
    $this->postJson('/api/mobile/register', [
        'name' => 'New mobile member', 'email' => 'new-mobile@example.test',
        'password' => 'password', 'password_confirmation' => 'password',
    ])->assertCreated();
    expect(User::where('email', 'new-mobile@example.test')->firstOrFail()->last_login_at->toDateTimeString())->toBe('2026-11-02 10:35:00');
});

test('login remains available while the last login migration has not yet run', function () {
    $user = User::factory()->create();
    Schema::partialMock()->shouldReceive('hasColumn')->with('users', 'last_login_at')->once()->andReturn(false);
    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->toBeNull();
});
