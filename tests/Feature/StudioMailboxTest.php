<?php

use App\Mail\StudioReplyMail;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config()->set('pitmetric.update_editor_emails', ['editor@pitmetric.test']);
    config()->set('services.resend.studio_key', 're_test_studio');
    config()->set('services.resend.studio_from_address', 'hello@pitmetric.it');
    config()->set('services.resend.studio_from_name', 'Simone | PitMetric');
    config()->set('services.resend.studio_reply_to', 'outreach@reply.pitmetric.it');
});

it('only allows editors to open the Studio inbox', function () {
    Http::fake([
        'https://api.resend.com/emails/receiving*' => Http::response(['data' => []], 200),
    ]);

    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);
    $user = User::factory()->create(['email' => 'driver@pitmetric.test']);

    $this->actingAs($editor)->get(route('studio.mail.index'))
        ->assertOk()
        ->assertSee('Inbox PitMetric');

    $this->actingAs($user)->get(route('studio.mail.index'))->assertForbidden();
});

it('lists received Resend emails for an editor', function () {
    Http::fake([
        'https://api.resend.com/emails/receiving*' => Http::response([
            'data' => [[
                'id' => 'email_in_123',
                'from' => 'Kart Team <team@example.com>',
                'to' => ['outreach@reply.pitmetric.it'],
                'subject' => 'Re: PitMetric',
                'created_at' => '2026-09-29T08:00:00.000Z',
            ]],
        ], 200),
    ]);

    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);

    $this->actingAs($editor)->get(route('studio.mail.index'))
        ->assertOk()
        ->assertSee('Kart Team')
        ->assertSee('Re: PitMetric');

    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer re_test_studio'));
});

it('shows a received message without rendering inbound html', function () {
    Http::fake([
        'https://api.resend.com/emails/receiving/*' => Http::response([
            'id' => 'email_in_123',
            'from' => 'Kart Team <team@example.com>',
            'to' => ['outreach@reply.pitmetric.it'],
            'subject' => 'Re: PitMetric',
            'created_at' => '2026-09-29T08:00:00.000Z',
            'text' => '',
            'html' => '<p>Ciao Simone</p><script>alert(1)</script>',
            'message_id' => '<message@example.com>',
            'attachments' => [],
        ], 200),
    ]);

    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);

    $this->actingAs($editor)->get(route('studio.mail.show', 'email_in_123'))
        ->assertOk()
        ->assertSee('Ciao Simone')
        ->assertDontSee('<script>', false);
});

it('lets an editor send a direct email from Studio', function () {
    Mail::fake();
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);

    $this->actingAs($editor)->get(route('studio.mail.compose'))
        ->assertOk()
        ->assertSee('Nuova mail');

    $this->actingAs($editor)->post(route('studio.mail.send'), [
        'to' => 'contact@example.com',
        'subject' => 'PitMetric - informazioni',
        'message' => 'Ciao, ti scrivo direttamente dallo Studio PitMetric.',
    ])->assertRedirect(route('studio.mail.index'));

    Mail::assertSent(StudioReplyMail::class, function (StudioReplyMail $mail): bool {
        return $mail->hasTo('contact@example.com')
            && $mail->subjectLine === 'PitMetric - informazioni';
    });
});

it('lets an editor reply to a received email', function () {
    Mail::fake();
    Http::fake([
        'https://api.resend.com/emails/receiving/*' => Http::response([
            'id' => 'email_in_123',
            'from' => 'Kart Team <team@example.com>',
            'to' => ['outreach@reply.pitmetric.it'],
            'subject' => 'Re: PitMetric',
            'created_at' => '2026-09-29T08:00:00.000Z',
            'text' => 'Vorremmo saperne di più.',
            'html' => null,
            'message_id' => '<message@example.com>',
            'attachments' => [],
        ], 200),
    ]);

    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);

    $this->actingAs($editor)->post(route('studio.mail.reply', 'email_in_123'), [
        'subject' => 'Re: PitMetric',
        'message' => 'Volentieri, vi mostro la demo.',
    ])->assertSessionHas('status', 'Risposta inviata a team@example.com.');

    Mail::assertSent(StudioReplyMail::class, function (StudioReplyMail $mail): bool {
        return $mail->hasTo('team@example.com')
            && $mail->subjectLine === 'Re: PitMetric'
            && $mail->messageBody === 'Volentieri, vi mostro la demo.';
    });
});
