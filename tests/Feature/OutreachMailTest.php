<?php

use App\Mail\OutreachMail;
use App\Models\User;
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
        ->assertDontSeeInHtml('<img')
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
        ->assertDontSeeInHtml('<img', false)
        ->assertSeeInHtml('&lt;script&gt;', false)
        ->assertSeeInHtml('Mezzi &amp; componenti', false)
        ->assertSeeInHtml('&lt;img', false)
        ->assertSeeInText($body)
        ->assertSeeInText($note)
        ->assertSeeInText($mail->unsubscribeUrl);
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
