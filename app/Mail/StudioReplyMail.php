<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

final class StudioReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<string, string> $customHeaders */
    public function __construct(
        public string $subjectLine,
        public string $messageBody,
        public array $customHeaders = [],
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                (string) config('services.resend.studio_from_address', 'hello@pitmetric.it'),
                (string) config('services.resend.studio_from_name', 'Simone | PitMetric'),
            ),
            replyTo: [new Address(
                (string) config('services.resend.studio_reply_to', 'outreach@reply.pitmetric.it'),
                'Simone | PitMetric',
            )],
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.studio-reply',
            with: ['messageBody' => $this->messageBody],
        );
    }

    public function headers(): Headers
    {
        return new Headers(text: $this->customHeaders);
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [];
    }
}
