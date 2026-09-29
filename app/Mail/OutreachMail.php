<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OutreachMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $contentLocale,
        public string $subjectLine,
        public string $messageBody,
        public string $note,
        public string $unsubscribeUrl,
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
            view: 'emails.outreach',
            with: [
                'locale' => $this->contentLocale,
                'messageBody' => $this->messageBody,
                'note' => $this->note,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [];
    }
}
