<?php

use App\Models\User;
use App\Services\WorkspaceContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

beforeEach(function () {
    config()->set('pitmetric.update_editor_emails', ['editor@example.test']);
    config()->set('buttidesk.bridge', ['enabled' => true, 'url' => 'https://desk.example.test', 'secret' => str_repeat('s', 64)]);
    Http::preventStrayRequests();
});

function deskBridgeRequest(TestCase $test, string $action, array $data, ?string $nonce = null, ?int $timestamp = null): TestResponse
{
    $path = '/api/buttidesk/v1/'.$action;
    $body = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    $timestamp ??= time();
    $nonce ??= bin2hex(random_bytes(16));
    $canonical = "v1\n{$timestamp}\n{$nonce}\nPOST\n{$path}\n".hash('sha256', $body);

    return $test->call('POST', $path, [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_BUTTIDESK_TIMESTAMP' => (string) $timestamp, 'HTTP_X_BUTTIDESK_NONCE' => $nonce,
        'HTTP_X_BUTTIDESK_SIGNATURE' => hash_hmac('sha256', $canonical, (string) config('buttidesk.bridge.secret')),
    ], $body);
}

function deskBridgeGrant(TestCase $test, User $user): array
{
    $verifier = str_repeat('v', 64);
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    $response = $test->actingAs($user)->get(route('buttidesk.authorize', ['state' => str_repeat('a', 64), 'code_challenge' => $challenge]));
    $response->assertRedirect();
    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

    return ['code' => $query['code'], 'code_verifier' => $verifier];
}

it('shows the app switch only to editors or explicitly enabled users without outgoing requests', function () {
    $user = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertDontSee('data-buttidesk-switch', false);
    $user->forceFill(['buttidesk_access_enabled' => true])->save();
    $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('data-buttidesk-switch', false);
    $this->get(route('buttidesk.launch'))->assertRedirect('https://desk.example.test/auth/pitmetric/start');
    $editor = User::factory()->create(['email' => 'editor@example.test']);
    $this->actingAs($editor)->get(route('dashboard'))->assertOk()->assertSee('data-buttidesk-switch', false);
    Http::assertNothingSent();
});

it('does not grant ButtiDesk access through the ordinary database permission', function () {
    $member = User::factory()->withDatabaseAccess()->create();
    $this->actingAs($member)->get(route('buttidesk.launch'))->assertForbidden();
    $this->get(route('buttidesk.index'))->assertForbidden();
});

it('lets only an editor change the dedicated user flag independently of database access', function () {
    $editor = User::factory()->create(['email' => 'editor@example.test']);
    $member = User::factory()->create();
    $this->actingAs($member)->patch(route('studio.users.buttidesk-access', $member), ['buttidesk_access_enabled' => true])->assertForbidden();
    $this->actingAs($editor)->get(route('studio.users.index'))->assertOk()->assertSee('ButtiDesk-Pro');
    $this->patch(route('studio.users.buttidesk-access', $member), ['buttidesk_access_enabled' => true])->assertRedirect();
    expect($member->fresh()->hasButtiDeskAccess())->toBeTrue()->and($member->fresh()->hasDatabaseAccess())->toBeFalse();
    $this->patch(route('studio.users.buttidesk-access', $editor), ['buttidesk_access_enabled' => false])->assertForbidden();
    $this->patch(route('studio.users.buttidesk-access', $member), ['buttidesk_access_enabled' => false])->assertRedirect();
    expect($member->fresh()->hasButtiDeskAccess())->toBeFalse();
    $this->patch(route('studio.users.buttidesk-access', $member), ['buttidesk_access_enabled' => 'invalid'])->assertSessionHasErrors('buttidesk_access_enabled');
});

it('stays available before the new access column is migrated', function () {
    Schema::table('users', fn ($table) => $table->dropColumn('buttidesk_access_enabled'));
    $user = User::factory()->create();
    $editor = User::factory()->create(['email' => 'editor@example.test']);
    expect($user->hasButtiDeskAccess())->toBeFalse();
    $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertDontSee('data-buttidesk-switch', false);
    $this->actingAs($editor)->get(route('studio.users.index'))->assertOk()->assertSee(__('users.desk_not_ready'));
    $this->patch(route('studio.users.buttidesk-access', $user), ['buttidesk_access_enabled' => true])->assertServiceUnavailable();
});

it('exchanges a one-use PKCE code for a server-side context bound to the source workspace', function () {
    $member = User::factory()->withButtiDeskAccess()->create();
    $grant = deskBridgeGrant($this, $member);
    $result = deskBridgeRequest($this, 'token', $grant)->assertOk()->assertJsonPath('context.user.id', $member->id)->assertJsonPath('context.role', 'owner')->assertHeader('Cache-Control', 'no-store, private');
    $token = $result->json('session_token');
    $workspaceId = $result->json('context.workspace.id');
    expect($token)->toHaveLength(64)->and($member->fresh()->hasDatabaseAccess())->toBeFalse();
    deskBridgeRequest($this, 'token', $grant)->assertUnauthorized();
    deskBridgeRequest($this, 'context', ['session_token' => $token, 'workspace_id' => 999, 'role' => 'viewer'])
        ->assertOk()->assertJsonPath('context.workspace.id', $workspaceId)->assertJsonPath('context.role', 'owner');
});

it('rejects invalid PKCE, stale signatures, unsigned requests and nonce replay', function () {
    $grant = deskBridgeGrant($this, User::factory()->withButtiDeskAccess()->create());
    deskBridgeRequest($this, 'token', array_replace($grant, ['code_verifier' => str_repeat('x', 64)]))->assertUnauthorized();
    deskBridgeRequest($this, 'token', $grant, timestamp: time() - 61)->assertUnauthorized();
    $this->postJson('/api/buttidesk/v1/token', $grant)->assertUnauthorized();
    $nonce = str_repeat('a', 32);
    $response = deskBridgeRequest($this, 'token', $grant, $nonce)->assertOk();
    deskBridgeRequest($this, 'context', ['session_token' => $response->json('session_token')], $nonce)->assertUnauthorized();
});

it('rechecks flags verification membership and roles on existing sessions', function () {
    $member = User::factory()->withButtiDeskAccess()->create();
    $grant = deskBridgeGrant($this, $member);
    $response = deskBridgeRequest($this, 'token', $grant)->assertOk();
    $token = $response->json('session_token');
    $workspaceId = $response->json('context.workspace.id');
    DB::table('workspace_user')->where('user_id', $member->id)->where('workspace_id', $workspaceId)->update(['role' => 'viewer']);
    deskBridgeRequest($this, 'context', ['session_token' => $token])->assertOk()->assertJsonPath('context.role', 'viewer');
    $member->forceFill(['buttidesk_access_enabled' => false])->save();
    deskBridgeRequest($this, 'context', ['session_token' => $token])->assertForbidden();
    $member->forceFill(['buttidesk_access_enabled' => true, 'email_verified_at' => null])->save();
    deskBridgeRequest($this, 'context', ['session_token' => $token])->assertForbidden();
    $member->forceFill(['email_verified_at' => now()])->save();
    DB::table('workspace_user')->where('user_id', $member->id)->where('workspace_id', $workspaceId)->update(['status' => 'suspended']);
    deskBridgeRequest($this, 'context', ['session_token' => $token])->assertForbidden();
});

it('denies users with only suspended memberships and does not invent a replacement tenant', function () {
    $member = User::factory()->withButtiDeskAccess()->create();
    $workspace = app(WorkspaceContext::class)->personal($member);
    DB::table('workspace_user')->where('user_id', $member->id)->update(['status' => 'suspended']);
    app()->forgetInstance(WorkspaceContext::class);
    $this->actingAs($member)->get(route('buttidesk.authorize', ['state' => str_repeat('a', 64), 'code_challenge' => str_repeat('a', 43)]))->assertForbidden();
    expect($member->workspaces()->count())->toBe(1);
});

it('revokes grants on explicit logout and on PitMetric logout', function () {
    $member = User::factory()->withButtiDeskAccess()->create();
    $first = deskBridgeRequest($this, 'token', deskBridgeGrant($this, $member))->assertOk()->json('session_token');
    deskBridgeRequest($this, 'revoke', ['session_token' => $first])->assertOk();
    deskBridgeRequest($this, 'context', ['session_token' => $first])->assertUnauthorized();
    $second = deskBridgeRequest($this, 'token', deskBridgeGrant($this, $member))->assertOk()->json('session_token');
    $this->actingAs($member)->post(route('logout'))->assertRedirect();
    deskBridgeRequest($this, 'context', ['session_token' => $second])->assertUnauthorized();
});

it('fails closed when unconfigured without affecting the PitMetric dashboard', function () {
    $member = User::factory()->withButtiDeskAccess()->create();
    config()->set('buttidesk.bridge.enabled', false);
    $this->actingAs($member)->get(route('dashboard'))->assertOk()->assertDontSee('data-buttidesk-switch', false);
    $this->get(route('buttidesk.launch'))->assertServiceUnavailable();
    deskBridgeRequest($this, 'context', ['session_token' => str_repeat('a', 64)])->assertNotFound();
    config()->set('buttidesk.bridge.enabled', true);
    config()->set('buttidesk.bridge.url', 'https://evil@desk.example.test');
    $this->get(route('buttidesk.launch'))->assertServiceUnavailable();
});

it('invalidates existing bridge sessions after credentials change', function () {
    $member = User::factory()->withButtiDeskAccess()->create();
    $token = deskBridgeRequest($this, 'token', deskBridgeGrant($this, $member))->assertOk()->json('session_token');
    $member->forceFill(['password' => 'a-new-test-password'])->save();
    deskBridgeRequest($this, 'context', ['session_token' => $token])->assertUnauthorized();
});
