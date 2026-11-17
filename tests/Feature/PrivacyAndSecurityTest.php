<?php

use App\Http\Middleware\SecureResponses;
use App\Models\MobileAccessToken;
use App\Models\PrivacyRequest;
use App\Models\User;
use App\Services\PrivacyReadiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Events\TwoFactorAuthenticationConfirmed;
use Symfony\Component\HttpFoundation\Response;

test('privacy requests require authentication and a recent password confirmation', function () {
    $this->get(route('privacy.index'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->post(route('privacy.store'), [
        'type' => 'access', 'details' => 'Vorrei una copia dei dati.',
    ])->assertRedirect(route('password.confirm'));
    expect(PrivacyRequest::query()->count())->toBe(0);
});

test('request ownership deadline and status are server controlled and personal content is encrypted', function () {
    $this->travelTo(now()->setDate(2027, 1, 31)->setTime(12, 0));
    $user = User::factory()->create();
    $other = User::factory()->create();
    $this->actingAs($user)->withSession(['auth.password_confirmed_at' => now()->timestamp])->post(route('privacy.store'), [
        'type' => 'erasure', 'details' => 'Eliminare i dati personali del mio profilo.',
        'user_id' => $other->id, 'status' => 'completed', 'due_at' => '2040-01-01',
    ])->assertRedirect(route('privacy.index'));
    $item = PrivacyRequest::query()->sole();
    expect($item->user_id)->toBe($user->id)
        ->and($item->status)->toBe('received')
        ->and($item->due_at->format('Y-m-d'))->toBe('2027-02-28')
        ->and($item->details)->toBe('Eliminare i dati personali del mio profilo.')
        ->and(DB::table('privacy_requests')->value('details'))->not->toContain('Eliminare')
        ->and(DB::table('privacy_requests')->value('history'))->not->toContain('Richiesta ricevuta')
        ->and($item->toArray())->not->toHaveKeys(['details', 'history']);
});

test('users only see their own requests and cannot provide editor responses', function () {
    $item = PrivacyRequest::factory()->create(['details' => 'Dati segreti di un altro account.']);
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('privacy.index'))->assertOk()->assertDontSee($item->details);
    $this->withSession(['auth.password_confirmed_at' => now()->timestamp])->post(route('studio.privacy.respond', $item), [
        'status' => 'completed', 'message' => 'Richiesta completata.',
    ])->assertForbidden();
    expect($item->fresh()->status)->toBe('received');
});

test('editors must provide a response and cannot overwrite closed requests', function () {
    config(['pitmetric.update_editor_emails' => ['editor@pitmetric.test']]);
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);
    $item = PrivacyRequest::factory()->create();
    $this->actingAs($editor)->post(route('studio.privacy.respond', $item), [])->assertRedirect(route('password.confirm'));
    $this->withSession(['auth.password_confirmed_at' => now()->timestamp])->post(route('studio.privacy.respond', $item), [
        'status' => 'rejected',
    ])->assertSessionHasErrors('message');
    $this->post(route('studio.privacy.respond', $item), [
        'status' => 'processing', 'message' => 'Stiamo verificando i dati del team.',
    ])->assertRedirect();
    $this->post(route('studio.privacy.respond', $item), [
        'status' => 'completed', 'message' => 'Riscontro completo disponibile al richiedente.',
    ])->assertRedirect();
    $item->refresh();
    expect($item->history)->toHaveCount(2)
        ->and($item->closed_at)->not->toBeNull()
        ->and(DB::table('privacy_requests')->value('history'))->not->toContain('Riscontro completo');
    $this->post(route('studio.privacy.respond', $item), [
        'status' => 'processing', 'message' => 'Provo a cambiare il riscontro precedente.',
    ])->assertSessionHasErrors('status');
    $this->actingAs($item->user)->get(route('privacy.index'))->assertOk()->assertSee('Riscontro completo disponibile');
});

test('privacy inputs are validated without flashing personal details to the session', function () {
    $this->actingAs(User::factory()->create())->withSession(['auth.password_confirmed_at' => now()->timestamp])
        ->post(route('privacy.store'), ['type' => 'invalid', 'details' => 'Informazioni personali da non duplicare.'])
        ->assertSessionHasErrors('type')->assertSessionMissing('_old_input.details');
    expect(PrivacyRequest::query()->count())->toBe(0);
});

test('privacy content is escaped and request register survives account deletion', function () {
    $item = PrivacyRequest::factory()->create(['details' => '<script>alert("secret")</script>']);
    $this->actingAs($item->user)->get(route('privacy.index'))->assertOk()
        ->assertSee($item->details)->assertDontSee($item->details, false);
    $item->user->delete();
    expect($item->fresh()->user_id)->toBeNull()->and($item->fresh()->details)->toBe($item->details);
});

test('monitoring distinguishes technical settings from outstanding organizational evidence and overdue requests', function () {
    config(['pitmetric.update_editor_emails' => ['editor@pitmetric.test']]);
    Http::fake();
    PrivacyRequest::factory()->create(['due_at' => now()->subDay()]);
    $this->actingAs(User::factory()->create(['email' => 'editor@pitmetric.test']))->get(route('studio.site.index'))
        ->assertOk()->assertSee('Sicurezza e privacy')->assertSee('1 richieste oltre il termine iniziale')
        ->assertSee('non attestano conformità GDPR')->assertSee('Sistema di gestione ISO/IEC 27001:2022');
    config(['session.encrypt' => false]);
    expect(collect(app(PrivacyReadiness::class)->checks())->firstWhere('name', 'Sessioni cifrate')['status'])->toBe('Da configurare');
});

test('public private api and error responses have security headers and sensitive responses are not cached', function () {
    $this->get(route('login'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('Cache-Control', 'no-store, private');
    $this->get('/settings/missing')->assertNotFound()->assertHeader('Cache-Control', 'no-store, private');
    $this->getJson('/api/mobile/me')->assertUnauthorized()->assertHeader('Cache-Control', 'no-store, private');
    $this->actingAs(User::factory()->create())->get(route('privacy.index'))->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    $this->app->detectEnvironment(fn () => 'production');
    $response = app(SecureResponses::class)->protect(Request::create('https://pitmetric.test'), new Response);
    expect($response->headers->get('Strict-Transport-Security'))->toBe('max-age=31536000')
        ->and($response->headers->get('Permissions-Policy'))->toContain('geolocation=(self)');
    $response = app(SecureResponses::class)->protect(Request::create('http://localhost'), new Response);
    expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
});

test('two factor enrollment requires password and confirmation before becoming active', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->post(route('two-factor.enable'))->assertRedirect(route('password.confirm'));
    $this->withSession(['auth.password_confirmed_at' => now()->timestamp])->from(route('security.edit'))->post(route('two-factor.enable'))->assertRedirect(route('security.edit'));
    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull()->and($user->hasEnabledTwoFactorAuthentication())->toBeFalse();
    $this->get(route('security.edit'))->assertOk()->assertSee('Conferma attivazione')->assertHeader('Cache-Control', 'no-store, private');
    $this->mock(TwoFactorAuthenticationProvider::class)->shouldReceive('verify')->once()->withArgs(fn ($secret, $code) => $code === '123456')->andReturnTrue();
    $this->post(route('two-factor.confirm'), ['code' => '123456'])->assertRedirect();
    expect($user->refresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();
    $this->get(route('security.edit'))->assertOk()->assertSee('Codici di recupero');
});

test('web challenge accepts a recovery code only once', function () {
    $user = User::factory()->withTwoFactor()->create();
    $this->withSession(['login.id' => $user->id, 'login.remember' => false])->get(route('two-factor.login'))->assertOk();
    $this->post(route('two-factor.login.store'), ['recovery_code' => 'test-recovery-code'])->assertRedirect();
    $this->assertAuthenticatedAs($user);
    expect($user->refresh()->recoveryCodes())->not->toContain('test-recovery-code');
    $this->post(route('logout'));
    $this->withSession(['login.id' => $user->id, 'login.remember' => false])->postJson(route('two-factor.login.store'), ['recovery_code' => 'test-recovery-code'])->assertUnprocessable();
    $this->assertGuest();
});

test('mobile login cannot bypass confirmed two factor authentication', function () {
    $user = User::factory()->withTwoFactor()->create();
    $this->mock(TwoFactorAuthenticationProvider::class)->shouldReceive('verify')->with('test-two-factor-secret', '')->once()->andReturnFalse();
    $this->postJson('/api/mobile/login', ['email' => $user->email, 'password' => 'password'])
        ->assertUnprocessable()->assertJsonPath('two_factor_required', true);
    expect(MobileAccessToken::query()->count())->toBe(0);
});

test('mobile two factor login issues token only after verifying the code', function () {
    $user = User::factory()->withTwoFactor()->create();
    $this->mock(TwoFactorAuthenticationProvider::class)->shouldReceive('verify')->with('test-two-factor-secret', '123456')->once()->andReturnTrue();
    $this->postJson('/api/mobile/login', ['email' => $user->email, 'password' => 'password', 'two_factor_code' => '123456'])->assertOk();
    expect(MobileAccessToken::query()->count())->toBe(1);
    TwoFactorAuthenticationConfirmed::dispatch($user);
    expect(MobileAccessToken::query()->whereNull('revoked_at')->count())->toBe(0);
});

test('production access logs omit visitor identifiers and request URLs containing tokens', function () {
    $nginx = file_get_contents(base_path('nixpacks.toml'));
    preg_match('/log_format main (.*?);/s', $nginx, $matches);
    expect($matches[1])->not->toContain('$remote_addr', '$remote_user', '$request"', '$request_uri', '$uri', '$http_referer', '$http_user_agent', '$http_x_forwarded_for');
});

test('missing privacy tables leave settings and monitoring accessible during a rolling deployment', function () {
    Schema::drop('privacy_requests');
    $this->actingAs(User::factory()->create())->get(route('privacy.index'))->assertOk()->assertSee('Gestione richieste in attivazione');
    config(['pitmetric.update_editor_emails' => ['editor@pitmetric.test']]);
    Http::fake();
    $this->actingAs(User::factory()->create(['email' => 'editor@pitmetric.test']))->get(route('studio.site.index'))->assertOk()->assertSee('Applica le migrazioni per attivare il registro');
});
