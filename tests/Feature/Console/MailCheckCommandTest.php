<?php

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;

test('mail check displays safe configuration without exposing the resend key', function () {
    $resendKey = 're_private_test_key_that_must_not_be_printed';

    config()->set([
        'app.url' => 'https://pitmetric.example',
        'mail.default' => 'resend',
        'mail.mailers.resend.transport' => 'resend',
        'mail.from.address' => 'sender@example.com',
        'mail.from.name' => 'PitMetric',
        'queue.default' => 'sync',
        'services.resend.key' => $resendKey,
    ]);

    $this->artisan('pitmetric:mail-check')
        ->expectsTable(['Setting', 'Value'], [
            ['APP_ENV', app()->environment()],
            ['APP_URL', 'https://pitmetric.example'],
            ['MAIL_MAILER', 'resend'],
            ['MAIL_TRANSPORT', 'resend'],
            ['RESEND_KEY_PRESENT', 'yes'],
            ['RESEND_KEY_LENGTH', (string) strlen($resendKey)],
            ['MAIL_FROM_ADDRESS', 'sender@example.com'],
            ['MAIL_FROM_NAME', 'PitMetric'],
            ['QUEUE_CONNECTION', 'sync'],
            ['RESEND_SDK_INSTALLED', 'yes'],
            ['USER_MUST_VERIFY_EMAIL', 'yes'],
            ['CONFIG_CACHED', app()->configurationIsCached() ? 'yes' : 'no'],
        ])
        ->doesntExpectOutputToContain($resendKey)
        ->assertSuccessful();
});

test('mail check sends a synchronous diagnostic email through the configured mailer', function () {
    config()->set([
        'mail.default' => 'resend',
        'mail.mailers.resend.transport' => 'resend',
    ]);

    Mail::shouldReceive('mailer')
        ->once()
        ->with('resend')
        ->andReturnSelf();

    Mail::shouldReceive('raw')
        ->once()
        ->withArgs(function (string $content, Closure $callback): bool {
            $message = new Message(new Email);
            $callback($message);

            expect($content)->toBe('PitMetric mail configuration check.')
                ->and($message->getSymfonyMessage()->getTo()[0]->getAddress())->toBe('test@example.com')
                ->and($message->getSymfonyMessage()->getSubject())->toBe('PitMetric mail configuration check');

            return true;
        });

    $this->artisan('pitmetric:mail-check', ['--send' => 'test@example.com'])
        ->expectsOutputToContain('Diagnostic email sent')
        ->assertSuccessful();
});

test('mail check rejects an invalid diagnostic recipient without sending mail', function () {
    Mail::shouldReceive('mailer')->never();

    $this->artisan('pitmetric:mail-check', ['--send' => 'not-an-email'])
        ->expectsOutputToContain('must be a valid email address')
        ->assertFailed();
});
