<?php

namespace App\Console\Commands;

use Composer\InstalledVersions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

#[Signature('pitmetric:mail-check {--send= : Send a synchronous diagnostic email through the configured mailer}')]
#[Description('Display safe mail configuration and optionally send a diagnostic email')]
class MailCheckCommand extends Command
{
    public function handle(): int
    {
        $defaultMailer = (string) config('mail.default');
        $transport = (string) config("mail.mailers.{$defaultMailer}.transport", 'unknown');
        $resendKey = config('services.resend.key');
        $resendKey = is_string($resendKey) ? $resendKey : '';

        $this->table(['Setting', 'Value'], [
            ['APP_ENV', app()->environment()],
            ['APP_URL', (string) config('app.url')],
            ['MAIL_MAILER', $defaultMailer],
            ['MAIL_TRANSPORT', $transport],
            ['RESEND_KEY_PRESENT', $resendKey !== '' ? 'yes' : 'no'],
            ['RESEND_KEY_LENGTH', (string) Str::length($resendKey)],
            ['MAIL_FROM_ADDRESS', (string) config('mail.from.address')],
            ['MAIL_FROM_NAME', (string) config('mail.from.name')],
            ['QUEUE_CONNECTION', (string) config('queue.default')],
            ['RESEND_SDK_INSTALLED', InstalledVersions::isInstalled('resend/resend-php') ? 'yes' : 'no'],
            ['USER_MUST_VERIFY_EMAIL', 'yes'],
            ['CONFIG_CACHED', app()->configurationIsCached() ? 'yes' : 'no'],
        ]);

        $recipient = $this->option('send');

        if ($recipient === null) {
            return self::SUCCESS;
        }

        $recipient = Str::of($recipient)->trim()->toString();

        if (Validator::make(['recipient' => $recipient], [
            'recipient' => ['required', 'email:rfc'],
        ])->fails()) {
            $this->error('The diagnostic recipient must be a valid email address.');

            return self::FAILURE;
        }

        try {
            Mail::mailer($defaultMailer)->raw(
                'PitMetric mail configuration check.',
                function (Message $message) use ($recipient): void {
                    $message->to($recipient)
                        ->subject('PitMetric mail configuration check');
                },
            );
        } catch (Throwable $exception) {
            report($exception);

            $this->error('Diagnostic email failed. Review the application logs for the transport error.');

            return self::FAILURE;
        }

        $this->info("Diagnostic email sent to [{$recipient}] through [{$defaultMailer}].");

        return self::SUCCESS;
    }
}
