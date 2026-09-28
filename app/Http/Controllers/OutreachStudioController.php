<?php

namespace App\Http\Controllers;

use App\Mail\OutreachMail;
use App\Models\MarketingSuppression;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Throwable;

class OutreachStudioController extends Controller
{
    private const MAX_RECIPIENTS = 25;

    public function index(): View
    {
        return view('studio.outreach.index', [
            'defaultSubjectIt' => 'Una domanda sul vostro lavoro in pista',
            'defaultSubjectEn' => 'A quick question about your trackside workflow',
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipients' => ['required', 'string', 'max:6000'],
            'locale' => ['required', 'in:it,en'],
            'subject' => ['nullable', 'string', 'max:140'],
            'note' => ['nullable', 'string', 'max:600'],
            'compliance_confirmed' => ['accepted'],
        ]);

        $recipients = $this->parseRecipients((string) $data['recipients']);

        if ($recipients === []) {
            throw ValidationException::withMessages(['recipients' => 'Inserisci almeno un indirizzo email valido.']);
        }

        if (count($recipients) > self::MAX_RECIPIENTS) {
            throw ValidationException::withMessages(['recipients' => 'Puoi inviare al massimo '.self::MAX_RECIPIENTS.' contatti per volta.']);
        }

        $locale = (string) $data['locale'];
        $subject = trim((string) ($data['subject'] ?? '')) ?: ($locale === 'it'
            ? 'Una domanda sul vostro lavoro in pista'
            : 'A quick question about your trackside workflow');
        $note = trim((string) ($data['note'] ?? ''));

        $sent = 0;
        $suppressed = 0;
        $failed = 0;

        foreach ($recipients as $email) {
            $hash = hash('sha256', strtolower($email));

            if (MarketingSuppression::query()->where('email_hash', $hash)->exists()) {
                $suppressed++;

                continue;
            }

            $unsubscribeUrl = URL::signedRoute('marketing.unsubscribe', ['email' => $email]);

            try {
                Mail::to($email)->send(new OutreachMail($locale, $subject, $note, $unsubscribeUrl));
                $sent++;
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        return back()->with('status', "Invio completato: {$sent} inviate, {$suppressed} escluse, {$failed} non riuscite.");
    }

    /** @return list<string> */
    private function parseRecipients(string $raw): array
    {
        $parts = preg_split('/[\s,;]+/', $raw) ?: [];
        $emails = [];

        foreach ($parts as $part) {
            $email = strtolower(trim($part));

            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                continue;
            }

            $emails[$email] = $email;
        }

        return array_values($emails);
    }
}
