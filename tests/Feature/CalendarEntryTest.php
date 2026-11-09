<?php

use App\Models\CalendarEntry;
use App\Models\StudioMailMessage;
use App\Models\User;
use App\Services\StudioMailConversations;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('pitmetric.update_editor_emails', ['editor@pitmetric.test']);
    config()->set('pitmetric.calendar_timezone', 'Europe/Rome');
    config()->set('services.resend.studio_key', 're_test_studio');
    Http::preventStrayRequests();
    Http::fake(['https://api.resend.com/*' => Http::response(['data' => []])]);
});

function calendarMail(string $providerId, string $direction): StudioMailMessage
{
    return app(StudioMailConversations::class)->store([
        'id' => $providerId,
        'from' => $direction === 'received' ? 'Team <team@example.com>' : 'PitMetric <hello@pitmetric.it>',
        'to' => [$direction === 'received' ? 'hello@pitmetric.it' : 'team@example.com'],
        'subject' => 'Riunione di prova',
        'text' => 'Organizziamo l’incontro.',
        'message_id' => '<'.$providerId.'@example.com>',
        'created_at' => '2026-11-09T09:00:00Z',
    ], $direction);
}

function calendarPayload(array $overrides = []): array
{
    return array_replace([
        'title' => 'Riunione kart',
        'starts_at' => '2026-11-14T10:00',
        'ends_at' => '2026-11-14T11:00',
        'location' => 'Circuito',
        'description' => 'Verifica i dati.',
    ], $overrides);
}

test('calendar is available to enabled accounts and Studio editors only', function () {
    $ordinary = User::factory()->create(['database_access_enabled' => false]);
    $enabled = User::factory()->create(['database_access_enabled' => true]);
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test', 'database_access_enabled' => false]);

    $this->get(route('calendar.index'))->assertRedirect(route('login'));
    $this->actingAs($ordinary)->get(route('calendar.index'))->assertForbidden();
    $this->post(route('calendar.store'), calendarPayload())->assertForbidden();
    $this->actingAs($enabled)->get(route('calendar.index'))->assertOk()->assertSee('Il tuo calendario');
    $this->actingAs($editor)->get(route('calendar.index'))->assertOk();
});

test('a personal event is stored in UTC and isolated between accounts', function () {
    $first = User::factory()->create(['database_access_enabled' => true]);
    $second = User::factory()->create(['database_access_enabled' => true]);

    $this->actingAs($first)->post(route('calendar.store'), calendarPayload())
        ->assertRedirect(route('calendar.index', ['month' => '2026-11']));

    $entry = CalendarEntry::query()->sole();
    expect($entry->user_id)->toBe($first->id)
        ->and($entry->starts_at->utc()->format('Y-m-d H:i'))->toBe('2026-11-14 09:00')
        ->and($entry->ends_at->utc()->format('Y-m-d H:i'))->toBe('2026-11-14 10:00');

    $this->get(route('calendar.index', ['month' => '2026-11']))->assertSee('Riunione kart');
    $this->actingAs($second)->get(route('calendar.index', ['month' => '2026-11']))->assertDontSee('Riunione kart');
    $this->put(route('calendar.update', $entry), calendarPayload(['title' => 'Modificato']))->assertNotFound();
    $this->delete(route('calendar.destroy', $entry))->assertNotFound();

    $this->actingAs($first)->put(route('calendar.update', $entry), calendarPayload(['title' => 'Modificato']))
        ->assertSessionHas('status', 'Evento aggiornato.');
    expect($entry->fresh()->title)->toBe('Modificato');
    $this->delete(route('calendar.destroy', $entry))->assertSessionHas('status', 'Evento eliminato.');
    $this->assertModelMissing($entry);
});

test('both received and sent email messages offer an event action and a prefilled calendar form', function () {
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);
    $received = calendarMail('mail_in_event', 'received');
    $sent = calendarMail('mail_sent_event', 'sent');

    $this->actingAs($editor)->get(route('studio.mail.index'))->assertOk()->assertSee('Crea evento');
    $this->get(route('studio.mail.show', ['emailId' => $sent->provider_id, 'direction' => 'sent']))
        ->assertOk()->assertSee('Crea evento dalla mail:')
        ->assertSee('mail_in_event')->assertSee('mail_sent_event');

    foreach ([$received, $sent] as $mail) {
        $this->get(route('calendar.index', ['mail' => $mail->provider_id]))
            ->assertOk()->assertSee('Riunione di prova')->assertSee('studio_mail_message_id');
    }

    $this->post(route('calendar.store'), calendarPayload(['studio_mail_message_id' => $sent->id]))
        ->assertSessionHas('status', 'Evento aggiunto al calendario.');
    expect(CalendarEntry::query()->sole()->studio_mail_message_id)->toBe($sent->id);
});

test('mail references cannot be linked by ordinary accounts and event dates are validated', function () {
    $email = calendarMail('protected_email', 'received');
    $enabled = User::factory()->create(['database_access_enabled' => true]);

    $this->actingAs($enabled)->get(route('calendar.index', ['mail' => $email->provider_id]))->assertForbidden();
    $this->post(route('calendar.store'), calendarPayload(['studio_mail_message_id' => $email->id]))
        ->assertSessionHasErrors('studio_mail_message_id');
    $this->post(route('calendar.store'), calendarPayload(['ends_at' => '2026-11-14T09:00']))
        ->assertSessionHasErrors('ends_at');
    $this->post(route('calendar.store'), calendarPayload(['studio_mail_message_id' => 999999]))
        ->assertSessionHasErrors('studio_mail_message_id');
    expect(CalendarEntry::query()->count())->toBe(0);
});

test('calendar selects a date without showing appointments from a different user', function () {
    $user = User::factory()->create(['database_access_enabled' => true]);
    $this->actingAs($user)->post(route('calendar.store'), calendarPayload())->assertRedirect();

    $this->get(route('calendar.index', ['month' => '2026-11', 'day' => '2026-11-14']))
        ->assertOk()->assertSee('Riunione kart')->assertSee('14 novembre 2026');
    $this->get(route('calendar.index', ['month' => '2026-12']))
        ->assertOk()->assertDontSee('Riunione kart');
});
