<?php

use App\Mail\OutreachMail;
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

it('shows a received message without rendering active inbound html', function () {
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
        ->assertDontSee('alert(1)');
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
            && $mail->messageBody === 'Volentieri, vi mostro la demo.'
            && $mail->headers()->text === [
                'In-Reply-To' => '<message@example.com>',
                'References' => '<message@example.com>',
            ];
    });
});

test('studio replies share outreach branding and preserve paragraphs safely', function () {
    $body = "Buongiorno,\nGrazie per la risposta.\n\n<script>alert('mail')</script> & disponibilità";
    $reply = new StudioReplyMail('Re: PitMetric', $body);
    $outreach = new OutreachMail('it', 'PitMetric', $body, '', 'https://pitmetric.example/opt-out');

    $reply->assertSeeInHtml('Sviluppatore e ideatore di PitMetric')
        ->assertSeeInHtml('alt="PitMetric"', false)
        ->assertSeeInHtml('data:image/png;base64,', false)
        ->assertSeeInHtml('Buongiorno,<br', false)
        ->assertSeeInHtml('&lt;script&gt;', false)
        ->assertSeeInHtml('&amp; disponibilità', false)
        ->assertDontSeeInHtml('<script>', false)
        ->assertDontSeeInHtml('Un invito a provare PitMetric')
        ->assertDontSeeInHtml('Esplora la demo')
        ->assertDontSeeInHtml('non ricevere altre email')
        ->assertSeeInText($body)
        ->assertSeeInText('Sviluppatore e ideatore di PitMetric')
        ->assertSeeInText(route('localized.home', ['locale' => 'it']));

    $documents = [];
    foreach ([$reply, $outreach] as $mail) {
        $document = new DOMDocument;
        @$document->loadHTML($mail->render());
        $documents[] = new DOMXPath($document);
    }

    foreach (['//body', '//table', '//img', '//td[1]', '//*[@data-outreach-message]/p', '//*[@data-outreach-label="signature"]/..'] as $selector) {
        $replyNode = $documents[0]->query($selector)->item(0);
        $outreachNode = $documents[1]->query($selector)->item(0);
        expect($replyNode->getAttribute('style'))->toBe($outreachNode->getAttribute('style'));
    }

    expect($documents[0]->query('//*[@data-outreach-message]/p'))->toHaveCount(2);
});

test('studio replies embed the official logo and keep plain text and reply headers', function () {
    $mail = new StudioReplyMail('Re: PitMetric', 'Volentieri, sentiamoci.', [
        'In-Reply-To' => '<message@example.com>',
        'References' => '<message@example.com>',
    ]);
    $sent = Mail::mailer('array')->to('preview@example.test')->send($mail);
    $email = $sent->getSymfonySentMessage()->getOriginalMessage();
    $images = $email->getAttachments();

    expect($images)->toHaveCount(1);
    $logo = $images[0];
    expect($logo->getDisposition())->toBe('inline')
        ->and($logo->getMediaType())->toBe('image')
        ->and($logo->getMediaSubtype())->toBe('png')
        ->and(base64_decode(preg_replace('/\s+/', '', $logo->bodyToString()), true))
        ->toBe(file_get_contents(public_path('brand/pitmetric-primary-dark-email.png')))
        ->and($email->getHtmlBody())->toContain('cid:', 'color-scheme', '#080a0d', '#111318')
        ->and($email->getTextBody())->toContain('Volentieri, sentiamoci.', 'Sviluppatore e ideatore di PitMetric')
        ->not->toContain('cid:', '<table')
        ->and($email->getHeaders()->get('In-Reply-To')->getBodyAsString())->toBe('<message@example.com>')
        ->and($email->getHeaders()->get('References')->getBodyAsString())->toBe('<message@example.com>')
        ->and($email->getFrom()[0]->getAddress())->toBe(config('services.resend.studio_from_address'))
        ->and($email->getReplyTo()[0]->getAddress())->toBe(config('services.resend.studio_reply_to'));
});
