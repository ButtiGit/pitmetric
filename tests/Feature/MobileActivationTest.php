<?php

use App\Models\User;
use App\Services\StudioAppActivations;
use Illuminate\Support\Facades\DB;

test('first opening is recorded once per installation and remains anonymous', function () {
    $installation = 'c61600c2-77e3-46b6-96bf-6dad7c903fd0';

    $this->postJson(route('mobile.activation'), ['installation_id' => $installation, 'platform' => 'pwa'])
        ->assertOk()->assertJson(['registered' => true, 'release_key' => StudioAppActivations::RELEASE_KEY]);
    $this->postJson(route('mobile.activation'), ['installation_id' => $installation, 'platform' => 'pwa'])
        ->assertOk();

    expect(DB::table('studio_app_activations')->count())->toBe(1);
    $activation = DB::table('studio_app_activations')->sole();
    expect($activation->platform)->toBe('pwa')
        ->and($activation->release_key)->toBe(StudioAppActivations::RELEASE_KEY)
        ->and($activation->installation_hash)->not->toBe($installation)
        ->and(strlen($activation->installation_hash))->toBe(64);

    expect(app(StudioAppActivations::class)->totals())->toBe(['total' => 1, 'pwa' => 1, 'android' => 0, 'ios' => 0]);
});

test('different app instances increment totals separately by platform', function () {
    foreach ([
        ['c61600c2-77e3-46b6-96bf-6dad7c903fd0', 'pwa'],
        ['73910e0e-aa0c-493e-b87a-79fe86348598', 'android'],
        ['e612d21a-cfb4-4381-9a09-3953e2d9cd59', 'ios'],
    ] as [$installationId, $platform]) {
        $this->postJson(route('mobile.activation'), ['installation_id' => $installationId, 'platform' => $platform])->assertOk();
    }

    expect(app(StudioAppActivations::class)->totals())->toBe(['total' => 3, 'pwa' => 1, 'android' => 1, 'ios' => 1]);
});

test('public first opening endpoint rejects invalid devices and unknown platforms', function () {
    $this->postJson(route('mobile.activation'), ['installation_id' => 'device-123', 'platform' => 'pwa'])
        ->assertUnprocessable()->assertJsonValidationErrors('installation_id');
    $this->postJson(route('mobile.activation'), ['installation_id' => 'c61600c2-77e3-46b6-96bf-6dad7c903fd0', 'platform' => 'desktop'])
        ->assertUnprocessable()->assertJsonValidationErrors('platform');

    expect(DB::table('studio_app_activations')->count())->toBe(0);
});

test('activation widget is visible only to editors', function () {
    config()->set('pitmetric.update_editor_emails', ['editor@pitmetric.test']);
    $regular = User::factory()->create();
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);

    $this->postJson(route('mobile.activation'), [
        'installation_id' => 'c61600c2-77e3-46b6-96bf-6dad7c903fd0', 'platform' => 'pwa',
    ])->assertOk();

    $this->actingAs($regular)->get(route('studio.site.index'))->assertForbidden();
    $this->actingAs($editor)->get(route('studio.site.index'))
        ->assertOk()
        ->assertSee('App e PWA attive dopo')
        ->assertSee('09/11/2026');
});
