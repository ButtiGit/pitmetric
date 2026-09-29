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

    /** @var list<string> */
    private const TONES = ['friendly', 'professional', 'local', 'technical', 'custom'];

    public function index(): View
    {
        return view('studio.outreach.index', [
            'tones' => [
                'friendly' => 'Amichevole',
                'professional' => 'Professionale',
                'local' => 'Locale / personale',
                'technical' => 'Tecnico / racing',
                'custom' => 'Altro / personalizzato',
            ],
            'templates' => $this->templates('it'),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipients' => ['required', 'string', 'max:6000'],
            'locale' => ['required', 'in:it,en'],
            'tone' => ['required', 'in:'.implode(',', self::TONES)],
            'company' => ['nullable', 'string', 'max:160'],
            'subject' => ['nullable', 'string', 'max:180'],
            'message' => ['nullable', 'string', 'max:6000'],
            'note' => ['nullable', 'string', 'max:800'],
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
        $tone = (string) $data['tone'];
        $company = trim((string) ($data['company'] ?? ''));
        $templates = $this->templates($locale);
        $template = $templates[$tone] ?? $templates['professional'];
        $subject = trim((string) ($data['subject'] ?? '')) ?: $this->replaceCompany($template['subject'], $company);
        $message = trim((string) ($data['message'] ?? '')) ?: $this->replaceCompany($template['message'], $company);
        $note = trim((string) ($data['note'] ?? ''));

        if ($subject === '') {
            throw ValidationException::withMessages(['subject' => 'Inserisci un oggetto per la mail.']);
        }

        if ($message === '') {
            throw ValidationException::withMessages(['message' => 'Scrivi il testo della mail.']);
        }

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
                Mail::to($email)->send(new OutreachMail(
                    $locale,
                    $subject,
                    $message,
                    $note,
                    $unsubscribeUrl,
                ));
                $sent++;
            } catch (Throwable $exception) {
                report($exception);
                $failed++;
            }
        }

        return back()->with('status', "Invio completato: {$sent} inviate, {$suppressed} escluse, {$failed} non riuscite.");
    }

    /** @return array<string, array{subject: string, message: string}> */
    private function templates(string $locale): array
    {
        if ($locale === 'en') {
            return [
                'friendly' => [
                    'subject' => 'Can I show you what I am building for motorsport?',
                    'message' => "Hi {{company}},\n\nI am Simone, the developer behind PitMetric. I am building it to keep the practical side of track work in one place: vehicles, components, setups, sessions, maintenance, costs, timing and telemetry.\n\nI would genuinely value your feedback and would be happy if you explored the demo to see whether any part could be useful in your day-to-day work.",
                ],
                'professional' => [
                    'subject' => 'PitMetric - motorsport technical management platform',
                    'message' => "Hello {{company}},\n\nMy name is Simone Buttice and I am the developer of PitMetric, a platform designed to organise technical motorsport operations in one workspace.\n\nPitMetric covers vehicles, components and usage history, configurations, setups, sessions, maintenance, costs, timing and telemetry. I am contacting selected motorsport organisations to gather concrete feedback and understand where the product can create real value.",
                ],
                'local' => [
                    'subject' => 'A local motorsport software project I would like to show you',
                    'message' => "Hello {{company}},\n\nI am Simone, the developer of PitMetric. I am reaching out personally because I prefer speaking directly with motorsport organisations rather than sending anonymous campaigns.\n\nPitMetric is built around real trackside workflows: vehicle history, components, setups, sessions, maintenance, costs, timing and telemetry. I would be glad if you took a look at the demo and told me what you would change or need in practice.",
                ],
                'technical' => [
                    'subject' => 'PitMetric - setups, components, sessions and technical history',
                    'message' => "Hello {{company}},\n\nPitMetric is a technical workspace for teams, drivers and preparers who need traceability across a vehicle's life. It connects component usage, configurations, setup changes, sessions, maintenance, expenses, timing and telemetry so information remains linked instead of scattered across notes and chats.\n\nI am looking for experienced motorsport feedback to validate the workflow against real track operations.",
                ],
                'custom' => ['subject' => '', 'message' => ''],
            ];
        }

        return [
            'friendly' => [
                'subject' => 'Posso farvi vedere cosa sto costruendo per il motorsport?',
                'message' => "Ciao {{company}},\n\nsono Simone, lo sviluppatore di PitMetric. Lo sto costruendo per riunire in un unico posto la parte pratica del lavoro in pista: mezzi, componenti, setup, sessioni, manutenzione, costi, timing e telemetria.\n\nMi farebbe davvero piacere avere un vostro parere: potete esplorare la demo liberamente e capire in pochi minuti se c'è qualcosa che potrebbe esservi utile nel lavoro quotidiano.",
            ],
            'professional' => [
                'subject' => 'PitMetric - piattaforma per la gestione tecnica motorsport',
                'message' => "Buongiorno {{company}},\n\nmi chiamo Simone Buttice e sono lo sviluppatore di PitMetric, una piattaforma pensata per organizzare in un unico ambiente la gestione tecnica delle attività motorsport.\n\nPitMetric comprende mezzi, componenti e relativo storico di utilizzo, configurazioni, setup, sessioni, manutenzione, costi, timing e telemetria. Sto contattando realtà selezionate del settore per raccogliere feedback concreti e capire dove il prodotto possa generare valore reale.",
            ],
            'local' => [
                'subject' => 'Un progetto motorsport nato in provincia di Cuneo',
                'message' => "Buongiorno {{company}},\n\nsono Simone Buttice, sviluppatore della provincia di Cuneo e ideatore di PitMetric. Vi contatto personalmente perché preferisco confrontarmi direttamente con realtà del territorio e del motorsport invece di mandare comunicazioni anonime.\n\nPitMetric nasce per gestire storico del mezzo, componenti, setup, sessioni, manutenzione, costi, timing e telemetria. Mi farebbe piacere farvelo vedere e ricevere un parere concreto su cosa potrebbe essere davvero utile nella vostra attività.",
            ],
            'technical' => [
                'subject' => 'PitMetric - setup, componenti, sessioni e storico tecnico',
                'message' => "Buongiorno {{company}},\n\nPitMetric è un workspace tecnico per team, piloti e preparatori che vogliono mantenere tracciabilità sull'intera vita del mezzo. Collega utilizzo dei componenti, configurazioni, variazioni di setup, sessioni, manutenzione, costi, timing e telemetria evitando che le informazioni restino sparse tra note, chat e fogli separati.\n\nSto cercando feedback da chi lavora realmente nel motorsport per validare il flusso sulle esigenze operative in pista.",
            ],
            'custom' => ['subject' => '', 'message' => ''],
        ];
    }

    private function replaceCompany(string $value, string $company): string
    {
        $value = str_replace('{{company}}', $company, $value);

        return str_replace(
            ['Ciao ,', 'Buongiorno ,', 'Hi ,', 'Hello ,'],
            ['Ciao,', 'Buongiorno,', 'Hi,', 'Hello,'],
            $value,
        );
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
