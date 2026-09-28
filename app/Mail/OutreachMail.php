<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
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
        public string $note,
        public string $unsubscribeUrl,
    ) {
        // Intentionally empty: promoted properties carry the message state.
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.outreach',
            with: [
                'locale' => $this->contentLocale,
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
