<?php

use App\Mail\StudioReplyMail;
use App\Models\User;
use App\Modules\ButtiDeskPro\Models\Appointment;
use App\Modules\ButtiDeskPro\Models\Contact;
use App\Modules\ButtiDeskPro\Models\MailLog;
use App\Modules\ButtiDeskPro\Models\Ticket;
use App\Modules\ButtiDeskPro\Models\Worklog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

it('keeps PitMetric available when ButtiDesk-Pro is disabled', function () {
    $user = User::factory()->withDatabaseAccess()->withButtiDeskAccess()->create();
    config()->set('buttidesk.enabled', false);

    $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertDontSee('ButtiDesk-Pro');
    $this->get(route('buttidesk.index'))->assertNotFound();
    $this->get('/buttidesk/calendar/feed/'.str_repeat('a', 48).'.ics')->assertNotFound();
});

it('keeps the desk route separate from public asset directories', function () {
    expect(is_dir(public_path('buttidesk')))->toBeFalse();

    $user = User::factory()->withDatabaseAccess()->withButtiDeskAccess()->create();
    $this->actingAs($user)->get('/buttidesk')->assertOk();
});

it('requires an authenticated and authorized PitMetric account', function () {
    $this->get(route('buttidesk.index'))->assertRedirect(route('login'));

    $regular = User::factory()->create();
    $this->actingAs($regular)->get(route('buttidesk.index'))->assertForbidden();

    $owner = User::factory()->withDatabaseAccess()->withButtiDeskAccess()->create();
    $this->actingAs($owner)->get(route('buttidesk.index'))->assertOk()->assertSee('ButtiDesk');
});

it('creates contacts and tickets and registers work for printable reports', function () {
    $user = User::factory()->withDatabaseAccess()->withButtiDeskAccess()->create();
    $this->actingAs($user);

    $this->post(route('buttidesk.contacts.store'), [
        'name' => 'Azienda Cliente', 'type' => 'client', 'email' => 'client@example.test',
    ])->assertSessionHasNoErrors();

    $contact = Contact::query()->firstOrFail();

    $this->post(route('buttidesk.tickets.store'), [
        'title' => 'Installazione postazione',
        'description' => 'Configurare rete e monitor',
        'category' => 'hardware',
        'status' => 'open',
        'priority' => 'high',
        'client_id' => $contact->id,
    ])->assertSessionHasNoErrors();

    $ticket = Ticket::query()->firstOrFail();

    $this->post(route('buttidesk.tickets.worklogs.store', $ticket), [
        'summary' => 'PC configurato e testato',
        'details' => 'Installazione completa',
        'duration_minutes' => 75,
        'performed_at' => now()->format('Y-m-d H:i:s'),
    ])->assertSessionHasNoErrors();

    expect(Worklog::query()->firstOrFail()->duration_minutes)->toBe(75);

    $this->get(route('buttidesk.tickets.report', $ticket))
        ->assertOk()->assertSee('PC configurato e testato')
        ->assertSee('Azienda Cliente');
    $this->get(route('buttidesk.tickets.reports'))
        ->assertOk()->assertSee('PC configurato e testato');
});

it('isolates contacts tickets appointments and reports between PitMetric workspaces', function () {
    $owner = User::factory()->withDatabaseAccess()->withButtiDeskAccess()->create();
    $other = User::factory()->withDatabaseAccess()->withButtiDeskAccess()->create();

    $this->actingAs($owner);

    $contact = Contact::query()->create(['name' => 'Privato Team Uno', 'type' => 'client']);
    $ticket = Ticket::query()->create([
        'title' => 'Ticket riservato',
        'category' => 'support',
        'status' => 'open',
        'priority' => 'normal',
        'client_id' => $contact->id,
        'created_by' => $owner->id,
    ]);

    $this->actingAs($other);

    $this->get(route('buttidesk.contacts.index'))->assertOk()->assertDontSee('Privato Team Uno');
    $this->get(route('buttidesk.tickets.index'))->assertOk()->assertDontSee('Ticket riservato');
    $this->get(route('buttidesk.tickets.show', $ticket->id))->assertNotFound();
    $this->get(route('buttidesk.tickets.report', $ticket->id))->assertNotFound();

    $this->post(route('buttidesk.tickets.store'), [
        'title' => 'Tentativo',
        'status' => 'open',
        'priority' => 'normal',
        'category' => 'support',
        'client_id' => $contact->id,
    ])->assertSessionHasErrors('client_id');

    $this->assertDatabaseMissing('desk_tickets', ['title' => 'Tentativo']);
});

it('makes appointments available in an optional revocable private iCal feed', function () {
    $user = User::factory()->withDatabaseAccess()->withButtiDeskAccess()->create();
    $this->actingAs($user);

    $this->post(route('buttidesk.calendar.store'), [
        'title' => 'Visita tecnica',
        'starts_at' => '2026-11-11T09:00',
        'ends_at' => '2026-11-11T10:00',
    ])->assertSessionHasNoErrors();

    expect(Appointment::query()->count())->toBe(1);

    $response = $this->post(route('buttidesk.calendar.feed.create'));
    $response->assertSessionHas('desk_calendar_url');
    $url = session('desk_calendar_url');

    $this->get($url)->assertOk()->assertSee('BEGIN:VCALENDAR')->assertSee('Visita tecnica');

    $this->post(route('buttidesk.calendar.feed.create'))->assertSessionHas('desk_calendar_url');
    $newUrl = session('desk_calendar_url');
    $this->get($url)->assertNotFound();
    $this->get($newUrl)->assertOk();

    $this->delete(route('buttidesk.calendar.feed.remove'))->assertSessionHasNoErrors();
    $this->get($newUrl)->assertNotFound();
});

it('uses the existing PitMetric mail transport with editor-only access', function () {
    config()->set('pitmetric.update_editor_emails', ['editor@example.test']);
    config()->set('services.resend.studio_key', 'resend_test_key');

    Http::fake([
        'https://api.resend.com/emails/receiving*' => Http::response(['data' => [[
            'id' => 'email_in_test', 'from' => 'Cliente <client@example.com>',
            'subject' => 'Assistenza', 'created_at' => '2026-10-14T08:00:00Z',
        ]]], 200),
    ]);

    $other = User::factory()->withDatabaseAccess()->withButtiDeskAccess()->create();
    $this->actingAs($other)->get(route('buttidesk.mail.index'))->assertForbidden();

    $editor = User::factory()->withDatabaseAccess()->withButtiDeskAccess()->create(['email' => 'editor@example.test']);
    $this->actingAs($editor)->get(route('buttidesk.mail.index'))->assertOk()->assertSee('Assistenza');
    Mail::fake();

    $this->post(route('buttidesk.mail.send'), [
        'to' => 'client@example.test',
        'subject' => 'Richiesta presa in carico',
        'message' => 'Ti rispondo da ButtiDesk-Pro.',
    ])->assertSessionHasNoErrors();

    Mail::assertSent(StudioReplyMail::class, fn (StudioReplyMail $mail): bool => $mail->hasTo('client@example.test') && $mail->subjectLine === 'Richiesta presa in carico');
    expect(MailLog::query()->count())->toBe(1);
});
