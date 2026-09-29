<?php

use App\Mail\OutreachMail;
use App\Models\MarketingSuppression;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    config()->set('pitmetric.update_editor_emails', ['editor@pitmetric.test']);
});

it('only allows editors to open outreach studio', function () {
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);
    $user = User::factory()->create(['email' => 'driver@pitmetric.test']);

    $this->actingAs($editor)->get(route('studio.outreach.index'))
        ->assertOk()
        ->assertSee('Amichevole')
        ->assertSee('Professionale')
        ->assertSee('Locale / personale')
        ->assertSee('Tecnico / racing');

    $this->actingAs($user)->get(route('studio.outreach.index'))->assertForbidden();
});

it('sends one branded email per unique recipient and skips opt outs', function () {
    Mail::fake();
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);

    MarketingSuppression::query()->create([
        'email_hash' => hash('sha256', 'blocked@example.com'),
    ]);

    $response = $this->actingAs($editor)->post(route('studio.outreach.send'), [
        'recipients' => "one@example.com\ntwo@example.com\none@example.com\nblocked@example.com",
        'locale' => 'it',
        'tone' => 'professional',
        'company' => 'Race Team',
        'subject' => 'PitMetric per Race Team',
        'message' => 'Buongiorno Race Team, vorrei presentarvi PitMetric.',
        'note' => 'Ho visto il vostro programma gare e penso che PitMetric possa essere utile in pista.',
        'compliance_confirmed' => '1',
    ]);

    $response->assertSessionHas('status', 'Invio completato: 2 inviate, 1 escluse, 0 non riuscite.');
    Mail::assertSent(OutreachMail::class, 2);
    Mail::assertSent(OutreachMail::class, fn (OutreachMail $mail) => $mail->hasTo('one@example.com') && $mail->subjectLine === 'PitMetric per Race Team');
    Mail::assertSent(OutreachMail::class, fn (OutreachMail $mail) => $mail->hasTo('two@example.com'));
    Mail::assertNotSent(OutreachMail::class, fn (OutreachMail $mail) => $mail->hasTo('blocked@example.com'));
});

it('can generate the selected preset server side', function () {
    Mail::fake();
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);

    $this->actingAs($editor)->post(route('studio.outreach.send'), [
        'recipients' => 'local@example.com',
        'locale' => 'it',
        'tone' => 'local',
        'company' => 'Kart Team Cuneo',
        'subject' => '',
        'message' => '',
        'compliance_confirmed' => '1',
    ])->assertSessionHasNoErrors();

    Mail::assertSent(OutreachMail::class, function (OutreachMail $mail): bool {
        return $mail->subjectLine === 'Un progetto motorsport nato in provincia di Cuneo'
            && str_contains($mail->messageBody, 'Kart Team Cuneo')
            && str_contains($mail->messageBody, 'provincia di Cuneo');
    });
});

it('requires the compliance confirmation before sending', function () {
    Mail::fake();
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);

    $this->actingAs($editor)->post(route('studio.outreach.send'), [
        'recipients' => 'team@example.com',
        'locale' => 'it',
        'tone' => 'professional',
        'subject' => 'PitMetric',
        'message' => 'Presentazione PitMetric.',
    ])->assertSessionHasErrors('compliance_confirmed');

    Mail::assertNothingSent();
});

it('stores a suppression from a signed opt out link', function () {
    $url = URL::signedRoute('marketing.unsubscribe', ['email' => 'stop@example.com']);

    $this->get($url)->assertOk();

    expect(MarketingSuppression::query()->where(
        'email_hash',
        hash('sha256', 'stop@example.com'),
    )->exists())->toBeTrue();
});
