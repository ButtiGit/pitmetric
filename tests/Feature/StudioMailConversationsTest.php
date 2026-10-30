<?php

use App\Mail\StudioReplyMail;
use App\Models\StudioMailMessage;
use App\Models\User;
use App\Services\StudioMailConversations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Cache::flush();
    Http::preventStrayRequests();
    config()->set('pitmetric.update_editor_emails', ['editor@pitmetric.test', 'other@pitmetric.test']);
    config()->set('services.resend.studio_key', 're_test_studio');
    config()->set('services.resend.studio_from_address', 'hello@pitmetric.it');
    config()->set('services.resend.studio_reply_to', 'outreach@reply.pitmetric.it');
    Http::fake([
        'https://api.resend.com/emails/receiving?*' => Http::response(['data' => []]),
        'https://api.resend.com/emails?*' => Http::response(['data' => []]),
    ]);
});

function studioMessage(string $id, string $direction = 'received', string $subject = 'Re: Prova in pista', string $contact = 'team@example.com'): StudioMailMessage
{
    return app(StudioMailConversations::class)->store([
        'id' => $id, 'from' => $direction === 'received' ? $contact : 'Simone <hello@pitmetric.it>',
        'to' => [$direction === 'received' ? 'outreach@reply.pitmetric.it' : $contact],
        'subject' => $subject, 'text' => 'Testo '.$id, 'message_id' => '<'.$id.'@mail.test>',
        'created_at' => '2026-10-30T10:00:00Z',
    ], $direction);
}

test('inbox groups incoming messages and our replies while separating other correspondents', function () {
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);
    studioMessage('sent_1', 'sent', 'Prova in pista');
    studioMessage('incoming_1');
    studioMessage('sent_2', 'sent', 'RE: Re: Prova in pista');
    studioMessage('other_team', contact: 'another@example.com');

    $this->actingAs($editor)->get(route('studio.mail.index'))->assertOk()
        ->assertSee('3 messaggi · 2 inviati')->assertSee('Da leggere · 2')
        ->assertSee('another@example.com');
    $this->get(route('studio.mail.show', 'incoming_1'))->assertOk()
        ->assertSeeInOrder(['Testo sent_1', 'Testo incoming_1', 'Testo sent_2'])
        ->assertDontSee('Testo other_team')->assertSee('Tu · Inviata');
});

test('opening a conversation reads only its incoming messages for the current editor', function () {
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);
    $other = User::factory()->create(['email' => 'other@pitmetric.test']);
    $first = studioMessage('incoming_1');
    $sent = studioMessage('sent_1', 'sent');

    $this->actingAs($editor)->get(route('studio.mail.show', 'incoming_1'))->assertOk();
    expect($first->readers()->whereKey($editor->id)->exists())->toBeTrue()
        ->and($first->readers()->whereKey($other->id)->exists())->toBeFalse()
        ->and($sent->readers()->exists())->toBeFalse();
    $this->get(route('studio.mail.index', ['filter' => 'unread']))->assertSee('Nessun messaggio da leggere');

    $new = studioMessage('incoming_new');
    $this->get(route('studio.mail.index', ['filter' => 'unread']))->assertOk()->assertSee('1 da leggere');
    expect($new->readers()->exists())->toBeFalse();
    $this->actingAs($other)->get(route('studio.mail.index', ['filter' => 'unread']))->assertSee('2 da leggere');
});

test('manual read and unread actions survive refreshing and validate their target', function () {
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);
    $message = studioMessage('incoming_1');
    $this->actingAs($editor)->patch(route('studio.mail.read', 'incoming_1'), ['read' => 1, 'through_id' => $message->id])->assertRedirect(route('studio.mail.index'));
    $this->get(route('studio.mail.index'))->assertSee('Letta')->assertSee('Segna come da leggere');
    $this->patch(route('studio.mail.read', 'incoming_1'), ['read' => 0])->assertRedirect();
    expect($message->readers()->exists())->toBeFalse();
    $this->get(route('studio.mail.index'))->assertSee('1 da leggere');
    $this->patch(route('studio.mail.read', 'missing'), ['read' => 1, 'through_id' => $message->id])->assertNotFound();
    $this->patch(route('studio.mail.read', 'incoming_1'), ['read' => 'arbitrary'])->assertSessionHasErrors('read');
});

test('guests and ordinary users cannot read conversations or change read states', function () {
    $message = studioMessage('incoming_1');
    $this->get(route('studio.mail.show', 'incoming_1'))->assertRedirect(route('login'));
    $this->patch(route('studio.mail.read', 'incoming_1'), ['read' => 1])->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route('studio.mail.show', 'incoming_1'))->assertForbidden();
    $this->patch(route('studio.mail.read', 'incoming_1'), ['read' => 1])->assertForbidden();
    $this->post(route('studio.mail.older'), ['direction' => 'sent'])->assertForbidden();
    expect($message->readers()->exists())->toBeFalse();
    Http::assertNothingSent();
});

test('reference headers connect replies even after the subject changes', function () {
    $root = studioMessage('root', 'sent', 'Invito iniziale');
    $reply = app(StudioMailConversations::class)->store([
        'id' => 'reply', 'from' => 'team@example.com', 'to' => ['outreach@reply.pitmetric.it'],
        'subject' => 'Richieste per domenica', 'text' => 'Le nostre richieste',
        'headers' => ['In-Reply-To' => '<root@mail.test>', 'References' => '<root@mail.test>'],
    ], 'received');
    expect($reply->thread_key)->toBe($root->thread_key);
});

test('successful Studio sending is archived immediately in the incoming conversation', function () {
    $root = studioMessage('incoming_1');
    Mail::mailer('array')->to('team@example.com')->send(new StudioReplyMail('Re: Prova in pista', 'Risposta confermata', [
        'In-Reply-To' => '<incoming_1@mail.test>', 'References' => '<incoming_1@mail.test>',
    ]));
    $sent = StudioMailMessage::query()->where('direction', 'sent')->sole();
    expect($sent->body)->toContain('Risposta confermata')
        ->and($sent->thread_key)->toBe($root->thread_key)
        ->and($sent->headers['in-reply-to'])->toBe('<incoming_1@mail.test>');
});

test('provider reconciliation preserves cached bodies and read receipts without duplicates', function () {
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);
    $message = studioMessage('received_1');
    app(StudioMailConversations::class)->markRead(new Collection([$message]), $editor, true);
    app(StudioMailConversations::class)->store([
        'id' => 'received_1', 'from' => 'team@example.com', 'subject' => 'Re: Prova in pista',
    ], 'received');
    expect(StudioMailMessage::query()->count())->toBe(1)
        ->and($message->refresh()->body)->toBe('Testo received_1')
        ->and($message->readers()->whereKey($editor->id)->exists())->toBeTrue();
});

test('provider outages leave saved conversations available and unread bodies remain unread', function () {
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);
    studioMessage('incoming_1');
    $missing = studioMessage('missing');
    $missing->update(['body' => null]);
    Http::swap(new Factory);
    Http::preventStrayRequests();
    Http::fake(['*' => Http::response(['message' => 'Unavailable'], 503)]);
    $this->actingAs($editor)->get(route('studio.mail.index'))->assertOk()->assertSee('Lo storico già salvato resta disponibile');
    $this->get(route('studio.mail.show', 'incoming_1'))->assertOk()->assertSee('Testo incoming_1')
        ->assertSee('Da leggere · testo non disponibile');
    expect($missing->readers()->exists())->toBeFalse();
});

test('older provider pages can be imported without losing latest messages', function () {
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);
    Http::swap(new Factory);
    Http::preventStrayRequests();
    Http::fake([
        'https://api.resend.com/emails/receiving?*' => Http::response(['data' => [], 'has_more' => false]),
        'https://api.resend.com/emails?*' => Http::sequence()->push(['data' => [[
            'id' => 'sent_latest', 'from' => 'hello@pitmetric.it', 'to' => ['team@example.com'], 'subject' => 'Novità',
        ]], 'has_more' => true])->push(['data' => [[
            'id' => 'sent_old', 'from' => 'hello@pitmetric.it', 'to' => ['team@example.com'], 'subject' => 'Primo contatto',
        ]], 'has_more' => false]),
    ]);
    $this->actingAs($editor)->get(route('studio.mail.index'))->assertOk()->assertSee('Carica inviate precedenti');
    $this->post(route('studio.mail.older'), ['direction' => 'sent'])->assertRedirect(route('studio.mail.index'));
    expect(StudioMailMessage::query()->where('direction', 'sent')->count())->toBe(2);
    Http::assertSent(fn ($request): bool => ($request['after'] ?? null) === 'sent_latest');
    $this->get(route('studio.mail.index'))->assertSee('Primo contatto')->assertDontSee('Carica inviate precedenti');
});

test('sent-only conversations can be opened without a reply form', function () {
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);
    studioMessage('sent_only', 'sent');
    $this->actingAs($editor)->get(route('studio.mail.show', ['emailId' => 'sent_only', 'direction' => 'sent']))
        ->assertOk()->assertSee('Testo sent_only')->assertDontSee('Invia risposta');
});

test('marking the displayed thread read does not consume a message arriving after rendering', function () {
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);
    $old = studioMessage('old_message');
    $new = studioMessage('new_arrival');
    $this->actingAs($editor)->patch(route('studio.mail.read', 'old_message'), ['read' => 1, 'through_id' => $old->id])->assertRedirect();
    expect($old->readers()->whereKey($editor->id)->exists())->toBeTrue()
        ->and($new->readers()->exists())->toBeFalse();
});
