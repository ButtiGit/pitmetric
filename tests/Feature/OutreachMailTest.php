<?php

use App\Mail\OutreachMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    config()->set('pitmetric.update_editor_emails', ['editor@pitmetric.test']);
});

test('the public product presentation renders in html and plain text without private editor features', function () {
    $editor = User::factory()->create(['email' => 'editor@pitmetric.test']);
    $response = $this->actingAs($editor)->get(route('studio.outreach.index'))->assertOk();
    $mail = new OutreachMail(
        'it',
        $response->viewData('defaultSubject'),
        $response->viewData('defaultMessage'),
        '',
        URL::signedRoute('marketing.unsubscribe', ['email' => 'team@example.com']),
    );

    $mail->assertSeeInHtml('Pit Mode')
        ->assertSeeInHtml('Android')
        ->assertSeeInHtml('Data Hub')
        ->assertSeeInHtml('aggiornamenti di sicurezza')
        ->assertSeeInHtml(route('demo.public'))
        ->assertSeeInHtml(route('localized.app', ['locale' => 'it']))
        ->assertDontSeeInHtml('Studio')
        ->assertDontSeeInHtml('SysPilot')
        ->assertDontSeeInHtml('ButtiDesk')
        ->assertSeeInHtml('alt="PitMetric"', false)
        ->assertSeeInHtml('data:image/png;base64,', false)
        ->assertSeeInText($mail->messageBody)
        ->assertSeeInText($mail->unsubscribeUrl);

    $envelope = $mail->envelope();
    expect($envelope->from->address)->toBe(config('services.resend.studio_from_address'))
        ->and($envelope->replyTo[0]->address)->toBe(config('services.resend.studio_reply_to'));
});

test('outreach escapes custom html while preserving the original plain text', function () {
    $body = "<script>alert('mail')</script>\n\nMezzi & componenti";
    $note = '<img src=x onerror=alert(1)>';
    $mail = new OutreachMail('it', 'PitMetric', $body, $note, 'https://pitmetric.example/opt-out?email=test&signature=example');

    $mail->assertDontSeeInHtml('<script>', false)
        ->assertDontSeeInHtml('<img src=x', false)
        ->assertSeeInHtml('&lt;script&gt;', false)
        ->assertSeeInHtml('Mezzi &amp; componenti', false)
        ->assertSeeInHtml('&lt;img', false)
        ->assertSeeInText($body)
        ->assertSeeInText($note)
        ->assertSeeInText($mail->unsubscribeUrl);

    $document = new DOMDocument;
    @$document->loadHTML($mail->render());
    expect($document->getElementsByTagName('img'))->toHaveCount(1);
});

test('english outreach renders localized actions signature and opt out text', function () {
    $mail = new OutreachMail('en', 'Discover PitMetric', 'Hello, meet PitMetric.', '', 'https://pitmetric.example/opt-out');

    $mail->assertSeeInHtml('Explore the demo')
        ->assertSeeInHtml('Discover the Android app')
        ->assertSeeInHtml('Developer and creator of PitMetric')
        ->assertSeeInHtml('stop future emails')
        ->assertSeeInHtml(route('localized.app', ['locale' => 'en']))
        ->assertSeeInText('Explore the demo')
        ->assertSeeInText(route('localized.app', ['locale' => 'en']));
});

test('outreach embeds the official logo as an inline png instead of an external image', function () {
    $mail = new OutreachMail('it', 'PitMetric', 'Presentazione PitMetric.', '', 'https://pitmetric.example/opt-out');
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
        ->and($email->getHtmlBody())->toContain('cid:')
        ->not->toContain('<svg', 'data-outreach-note', '#FF5A36')
        ->and($email->getTextBody())->not->toContain('cid:');

    $image = getimagesize(public_path('brand/pitmetric-primary-dark-email.png'));
    expect($image[0])->toBe(1200)
        ->and($image[1])->toBe(220)
        ->and($image['mime'])->toBe('image/png');
});
