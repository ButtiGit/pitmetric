<?php

namespace App\Listeners;

use App\Mail\OutreachMail;
use App\Mail\StudioReplyMail;
use App\Services\StudioMailConversations;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Address;
use Throwable;

final class ArchiveStudioMail
{
    public function __construct(private StudioMailConversations $conversations) {}

    public function handle(MessageSent $event): void
    {
        if (! in_array($event->data['__laravel_mailable'] ?? null, [StudioReplyMail::class, OutreachMail::class], true)) {
            return;
        }
        $email = $event->message;
        $headers = [];
        foreach (['Message-ID', 'In-Reply-To', 'References'] as $name) {
            $headers[mb_strtolower($name)] = $email->getHeaders()->get($name)?->getBodyAsString() ?? '';
        }
        try {
            $this->conversations->store([
                'id' => $email->getHeaders()->get('X-Resend-Email-ID')?->getBodyAsString() ?? 'local-'.Str::uuid(),
                'message_id' => $headers['message-id'], 'headers' => $headers,
                'from' => $email->getFrom()[0]->toString(),
                'to' => array_map(fn (Address $address): string => $address->toString(), $email->getTo()),
                'cc' => array_map(fn (Address $address): string => $address->toString(), $email->getCc()),
                'subject' => $email->getSubject(), 'text' => $email->getTextBody(), 'created_at' => now(),
            ], 'sent');
        } catch (Throwable $exception) {
            // Sending has already succeeded; Resend reconciliation can recover this archive entry.
            report($exception);
        }
    }
}
