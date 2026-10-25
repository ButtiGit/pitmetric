<?php

use App\Mail\OutreachMail;
use App\Models\MarketingSuppression;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Mail\PendingMail;
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
        ->assertSee('Tecnico / racing')
        ->assertSee('Invia comunicazione')
        ->assertSee('Personalizza la comunicazione')
        ->assertDontSee('name="compliance_confirmed"', false)
        ->assertDontSee('onsubmit="return confirm', false);

    $this->actingAs($user)->get(route('studio.outreach.index'))->assertForbidden();
});

it('sends one branded email per unique recipient and skips opt outs', function () {
    Mail::fake();
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);

    MarketingSuppression::query()->create([
        'email_hash' => hash('sha256', 'blocked@example.com'),
    ]);

    $response = $this->actingAs($editor)->post(route('studio.outreach.send'), [
        'recipients' => "ONE@example.com;two@example.com, one@example.com\nBLOCKED@example.com",
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

test('sends the current presentation with only the recipient addresses', function () {
    Mail::fake();
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);

    $this->actingAs($editor)->post(route('studio.outreach.send'), [
        'recipients' => 'team@example.com',
    ])->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'Invio completato: 1 inviate, 0 escluse, 0 non riuscite.');

    Mail::assertSent(OutreachMail::class, function (OutreachMail $mail): bool {
        expect($mail->contentLocale)->toBe('it')
            ->and($mail->subjectLine)->toBe('PitMetric: team e attività in pista in un unico posto')
            ->and($mail->messageBody)->toContain('ruoli', 'componenti', 'Pit Mode', 'documenti privati', 'Data Hub', 'Android', 'offline', 'aggiornamenti di sicurezza')
            ->not->toContain('Studio', 'SysPilot', 'ButtiDesk', 'game_sim', '{{company}}');

        return $mail->hasTo('team@example.com') && count($mail->to) === 1;
    });
});

test('each recipient gets a private message and a valid individual opt out link', function () {
    Mail::fake();
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);

    $this->actingAs($editor)->post(route('studio.outreach.send'), [
        'recipients' => 'one@example.com two@example.com',
    ])->assertSessionHasNoErrors();

    $messages = Mail::sent(OutreachMail::class);

    expect($messages)->toHaveCount(2)
        ->and($messages->pluck('unsubscribeUrl')->unique())->toHaveCount(2);

    foreach ($messages as $mail) {
        expect($mail->to)->toHaveCount(1)
            ->and($mail->cc)->toBeEmpty()
            ->and($mail->bcc)->toBeEmpty()
            ->and(URL::hasValidSignature(Request::create($mail->unsubscribeUrl)))->toBeTrue();
    }
});

test('all preset formats describe the current product in either language', function (string $locale, string $tone) {
    Mail::fake();
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);

    $this->actingAs($editor)->post(route('studio.outreach.send'), [
        'recipients' => 'team@example.com',
        'locale' => $locale,
        'tone' => $tone,
        'company' => 'Race Team',
    ])->assertSessionHasNoErrors();

    Mail::assertSent(OutreachMail::class, function (OutreachMail $mail) use ($locale): bool {
        expect($mail->contentLocale)->toBe($locale)
            ->and($mail->messageBody)->toContain('Race Team', 'Android', 'Pit Mode', 'Data Hub')
            ->not->toContain('Studio', 'SysPilot', 'ButtiDesk', '{{company}}');

        return true;
    });
})->with(['it', 'en'])->with(['friendly', 'professional', 'local', 'technical']);

test('invalid recipient lists fail before any email is sent', function (string $recipients) {
    Mail::fake();
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);

    $this->actingAs($editor)->post(route('studio.outreach.send'), [
        'recipients' => $recipients,
    ])->assertSessionHasErrors('recipients');

    Mail::assertNothingSent();
})->with([
    'empty' => '',
    'invalid' => 'not-an-email',
    'mixed' => 'valid@example.com, invalid-address',
    'too many' => implode(',', array_map(fn (int $number): string => "team{$number}@example.com", range(1, 26))),
]);

test('ordinary users cannot send outreach', function () {
    Mail::fake();
    $user = User::factory()->create(['email' => 'driver@pitmetric.test']);

    $this->actingAs($user)->post(route('studio.outreach.send'), [
        'recipients' => 'team@example.com',
    ])->assertForbidden();

    Mail::assertNothingSent();
});

test('custom messages remain available without additional required fields', function () {
    Mail::fake();
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);

    $this->actingAs($editor)->post(route('studio.outreach.send'), [
        'recipients' => 'team@example.com',
        'tone' => 'custom',
        'subject' => 'Un saluto al team',
        'message' => 'Una presentazione personalizzata.',
        'note' => 'Ci siamo conosciuti in pista.',
    ])->assertSessionHasNoErrors();

    Mail::assertSent(OutreachMail::class, fn (OutreachMail $mail): bool => $mail->subjectLine === 'Un saluto al team'
        && $mail->messageBody === 'Una presentazione personalizzata.'
        && $mail->note === 'Ci siamo conosciuti in pista.');
});

test('invalid optional fields are rejected before sending', function (array $options, string $field) {
    Mail::fake();
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);

    $this->actingAs($editor)->post(route('studio.outreach.send'), [
        'recipients' => 'team@example.com',
        ...$options,
    ])->assertSessionHasErrors($field);

    Mail::assertNothingSent();
})->with([
    'locale' => [['locale' => 'xx'], 'locale'],
    'tone' => [['tone' => 'unknown'], 'tone'],
    'empty custom' => [['tone' => 'custom'], 'subject'],
    'header newline' => [['subject' => "Hello\r\nBcc: hidden@example.com"], 'subject'],
]);

test('a partial mail transport failure keeps only failed recipients for retry', function () {
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);
    $successful = Mockery::mock(PendingMail::class);
    $successful->shouldReceive('send')->once()->with(Mockery::type(OutreachMail::class));
    $failed = Mockery::mock(PendingMail::class);
    $failed->shouldReceive('send')->once()->andThrow(new RuntimeException('Mail transport unavailable.'));
    Mail::shouldReceive('to')->once()->with('sent@example.com')->andReturn($successful);
    Mail::shouldReceive('to')->once()->with('retry@example.com')->andReturn($failed);

    $this->actingAs($editor)->from(route('studio.outreach.index'))
        ->post(route('studio.outreach.send'), [
            'recipients' => 'sent@example.com retry@example.com',
            'subject' => 'Presentazione al team',
        ])
        ->assertRedirect(route('studio.outreach.index'))
        ->assertSessionHas('status', 'Invio completato: 1 inviate, 0 escluse, 1 non riuscite.')
        ->assertSessionHas('outreach_failed', 1)
        ->assertSessionHasInput('recipients', 'retry@example.com')
        ->assertSessionHasInput('subject', 'Presentazione al team');

    $this->get(route('studio.outreach.index'))
        ->assertOk()
        ->assertSee('Sono rimasti in lista solo gli indirizzi');
});

it('stores a suppression from a signed opt out link', function () {
    $url = URL::signedRoute('marketing.unsubscribe', ['email' => 'stop@example.com']);

    $this->get($url)->assertOk();

    expect(MarketingSuppression::query()->where(
        'email_hash',
        hash('sha256', 'stop@example.com'),
    )->exists())->toBeTrue();
});
