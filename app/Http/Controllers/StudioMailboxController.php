<?php

namespace App\Http\Controllers;

use App\Mail\StudioReplyMail;
use App\Services\ResendStudioMailbox;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class StudioMailboxController extends Controller
{
    public function index(ResendStudioMailbox $mailbox): View
    {
        $emails = [];
        $mailboxError = null;

        try {
            $emails = $mailbox->received(60)['data'];
        } catch (Throwable $exception) {
            report($exception);
            $mailboxError = 'Impossibile leggere la posta da Resend. Controlla RESEND_STUDIO_KEY e i suoi permessi.';
        }

        return view('studio.mail.index', [
            'emails' => $emails,
            'mailboxError' => $mailboxError,
        ]);
    }

    public function compose(): View
    {
        return view('studio.mail.compose');
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'to' => ['required', 'email:rfc', 'max:254'],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:12000'],
        ]);

        Mail::to((string) $data['to'])->send(new StudioReplyMail(
            (string) $data['subject'],
            (string) $data['message'],
        ));

        return redirect()->route('studio.mail.index')->with('status', 'Email inviata a '.$data['to'].'.');
    }

    public function show(string $emailId, ResendStudioMailbox $mailbox): View
    {
        $email = $mailbox->receivedEmail($emailId);
        $body = trim((string) ($email['text'] ?? ''));

        if ($body === '') {
            $html = (string) ($email['html'] ?? '');
            $body = trim(html_entity_decode(strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html)));
        }

        return view('studio.mail.show', [
            'email' => $email,
            'body' => $body,
            'replyAddress' => $this->extractAddress((string) ($email['from'] ?? '')),
        ]);
    }

    public function reply(Request $request, string $emailId, ResendStudioMailbox $mailbox): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:12000'],
        ]);

        $original = $mailbox->receivedEmail($emailId);
        $to = $this->extractAddress((string) ($original['from'] ?? ''));

        if ($to === null) {
            return back()->withErrors(['message' => 'Non riesco a determinare l’indirizzo del mittente.']);
        }

        $headers = [];
        $messageId = trim((string) ($original['message_id'] ?? ''));

        if ($messageId !== '') {
            $headers['In-Reply-To'] = $messageId;
            $headers['References'] = $messageId;
        }

        Mail::to($to)->send(new StudioReplyMail(
            (string) $data['subject'],
            (string) $data['message'],
            $headers,
        ));

        return back()->with('status', 'Risposta inviata a '.$to.'.');
    }

    private function extractAddress(string $from): ?string
    {
        if (preg_match('/<([^>]+)>/', $from, $matches) === 1) {
            $candidate = trim($matches[1]);

            return filter_var($candidate, FILTER_VALIDATE_EMAIL) !== false ? $candidate : null;
        }

        $candidate = trim($from);

        return filter_var($candidate, FILTER_VALIDATE_EMAIL) !== false ? $candidate : null;
    }
}
