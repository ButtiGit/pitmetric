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
        $templatesByLocale = [
            'it' => $this->templates('it'),
            'en' => $this->templates('en'),
        ];
        $locale = session()->getOldInput('locale', 'it');
        $locale = is_string($locale) && isset($templatesByLocale[$locale]) ? $locale : 'it';
        $tone = session()->getOldInput('tone', 'professional');
        $tone = is_string($tone) && isset($templatesByLocale[$locale][$tone]) ? $tone : 'professional';
        $company = session()->getOldInput('company', '');
        $company = is_string($company) ? $company : '';
        $template = $templatesByLocale[$locale][$tone];

        return view('studio.outreach.index', [
            'tones' => [
                'friendly' => 'Amichevole',
                'professional' => 'Professionale',
                'local' => 'Locale / personale',
                'technical' => 'Tecnico / racing',
                'custom' => 'Altro / personalizzato',
            ],
            'templates' => $templatesByLocale['it'],
            'templatesByLocale' => $templatesByLocale,
            'defaultSubject' => $this->replaceCompany($template['subject'], $company),
            'defaultMessage' => $this->replaceCompany($template['message'], $company),
            'localizedLinks' => collect(['it', 'en'])->mapWithKeys(fn (string $locale): array => [
                $locale => [
                    'home' => route('localized.home', ['locale' => $locale]),
                    'app' => route('localized.app', ['locale' => $locale]),
                ],
            ])->all(),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipients' => ['required', 'string', 'max:6000'],
            'locale' => ['nullable', 'in:it,en'],
            'tone' => ['nullable', 'in:'.implode(',', self::TONES)],
            'company' => ['nullable', 'string', 'max:160'],
            'subject' => ['nullable', 'string', 'max:180', 'not_regex:/[\r\n]/'],
            'message' => ['nullable', 'string', 'max:6000'],
            'note' => ['nullable', 'string', 'max:800'],
        ]);

        $recipients = $this->parseRecipients((string) $data['recipients']);

        if ($recipients === []) {
            throw ValidationException::withMessages(['recipients' => 'Inserisci almeno un indirizzo email valido.']);
        }

        if (count($recipients) > self::MAX_RECIPIENTS) {
            throw ValidationException::withMessages(['recipients' => 'Puoi inviare al massimo '.self::MAX_RECIPIENTS.' contatti per volta.']);
        }

        $locale = (string) ($data['locale'] ?? 'it');
        $tone = (string) ($data['tone'] ?? 'professional');
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
        $failedRecipients = [];

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
                $failedRecipients[] = $email;
            }
        }

        $response = back()
            ->with('status', "Invio completato: {$sent} inviate, {$suppressed} escluse, {$failed} non riuscite.")
            ->with('outreach_failed', $failed);

        if ($failedRecipients !== []) {
            return $response->withInput([
                ...$data,
                'recipients' => implode("\n", $failedRecipients),
            ]);
        }

        return $response;
    }

    /** @return array<string, array{subject: string, message: string}> */
    private function templates(string $locale): array
    {
        if ($locale === 'en') {
            $overview = "\n\nPitMetric connects the practical work of a small motorsport team, starting with karting:\n\n- Team workspaces, invitations and roles, so everyone can work with the right information and permissions.\n- Vehicles, drivers, component installation and usage history, versioned configurations and technical setups.\n- Race weekends, entries, schedules, tasks, notes and track sessions, with quick capture in Pit Mode.\n- Maintenance schedules, work orders, expenses and private documents, linked to the operational history.\n- Timing, imported telemetry, operational reports and the Data Hub for importing and exporting workspace data.\n- An Android beta app for trackside sessions, setups, maintenance, notes, photos and videos, with offline support and sync for enabled accounts.";
            $security = "\n\nRecent work also focuses on reliability and security: security updates to the platform, separate team workspaces, role-based permissions, verified email access and protected document downloads.";
            $invitation = "\n\nI am looking for teams and owner-drivers willing to try the pilot with real trackside workflows and give me direct feedback. Testing is completely free, with no subscription or purchase commitment. You can explore the demo now; if it looks useful, reply to this email and I will help you get started.\n\nWhich part of your trackside work would you most like to keep organised?";

            return [
                'friendly' => [
                    'subject' => 'An invitation to try PitMetric at the track',
                    'message' => "Hi {{company}},\n\nI am Simone, the developer of PitMetric. I am building a practical workspace to keep vehicle history, team preparation and track activity in one place.".$overview.$security.$invitation,
                ],
                'professional' => [
                    'subject' => 'PitMetric: one workspace for your team and track operations',
                    'message' => "Hello {{company}},\n\nMy name is Simone Buttice and I develop PitMetric, a technical operations platform for small motorsport teams and owner-drivers. The goal is to connect preparation, track activity and vehicle history, reducing information scattered across spreadsheets, notes and chats.".$overview.$security.$invitation,
                ],
                'local' => [
                    'subject' => 'A motorsport project from Cuneo: discover PitMetric',
                    'message' => "Hello {{company}},\n\nI am Simone Buttice, a developer from the province of Cuneo and the creator of PitMetric. I would like to introduce the project personally and learn what would make it useful for your team at the track.".$overview.$security.$invitation,
                ],
                'technical' => [
                    'subject' => 'PitMetric: from component history to session data',
                    'message' => "Hello {{company}},\n\nPitMetric links the technical history of a vehicle to the work carried out before, during and after each track session. It is designed for teams, drivers and preparers who need to trace what was installed, which setup was used, how much a component ran and what needs attention next.".$overview.$security.$invitation,
                ],
                'custom' => ['subject' => '', 'message' => ''],
            ];
        }

        $overview = "\n\nPitMetric collega il lavoro quotidiano di un piccolo team motorsport, partendo dal karting:\n\n- Spazi di lavoro del team, inviti e ruoli, per collaborare con informazioni e permessi adeguati.\n- Mezzi, piloti, installazioni e utilizzo dei componenti, configurazioni versionate e setup tecnici.\n- Weekend di gara, iscrizioni, programmi, attività, note e sessioni in pista, con registrazione rapida in Pit Mode.\n- Scadenze e ordini di manutenzione, spese e documenti privati, collegati allo storico operativo.\n- Timing, telemetria importata, report operativi e Data Hub per importare ed esportare i dati del workspace.\n- App Android in beta per sessioni, setup, manutenzione, note, foto e video in pista, con modalità offline e sincronizzazione per gli account abilitati.";
        $security = "\n\nLe novità riguardano anche affidabilità e sicurezza: aggiornamenti di sicurezza della piattaforma, spazi dei team separati, permessi per ruolo, accesso con email verificata e download protetti dei documenti.";
        $invitation = "\n\nSto cercando team e piloti disponibili a provare il progetto pilota sul campo e a confrontarsi direttamente con me. Il test è completamente gratuito, senza abbonamenti o obblighi di acquisto. Potete esplorare subito la demo; se vi sembra utile, rispondete a questa email e vi aiuterò a iniziare.\n\nQuale parte del lavoro in pista vorreste organizzare meglio?";

        return [
            'friendly' => [
                'subject' => 'Un invito a provare PitMetric in pista',
                'message' => "Ciao {{company}},\n\nsono Simone, lo sviluppatore di PitMetric. Sto costruendo uno spazio pratico per tenere insieme storico del mezzo, preparazione del team e attività in pista.".$overview.$security.$invitation,
            ],
            'professional' => [
                'subject' => 'PitMetric: team e attività in pista in un unico posto',
                'message' => "Buongiorno {{company}},\n\nmi chiamo Simone Buttice e sviluppo PitMetric, una piattaforma di gestione tecnica per piccoli team motorsport e piloti. L'obiettivo è collegare preparazione, attività in pista e storico del mezzo, riducendo le informazioni sparse tra fogli di calcolo, appunti e chat.".$overview.$security.$invitation,
            ],
            'local' => [
                'subject' => 'Un progetto motorsport nato in provincia di Cuneo',
                'message' => "Buongiorno {{company}},\n\nsono Simone Buttice, sviluppatore della provincia di Cuneo e ideatore di PitMetric. Vi scrivo personalmente per presentarvi il progetto e capire cosa potrebbe essere utile al vostro team in pista.".$overview.$security.$invitation,
            ],
            'technical' => [
                'subject' => 'PitMetric: dallo storico componenti ai dati di sessione',
                'message' => "Buongiorno {{company}},\n\nPitMetric collega lo storico tecnico del mezzo al lavoro svolto prima, durante e dopo ogni sessione. È pensato per team, piloti e preparatori che vogliono ricostruire cosa è stato installato, quale setup è stato usato, quanto ha lavorato un componente e cosa richiede attenzione.".$overview.$security.$invitation,
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

            if ($email === '') {
                continue;
            }

            if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw ValidationException::withMessages([
                    'recipients' => "Indirizzo email non valido: {$email}. Correggi la lista prima di inviare.",
                ]);
            }

            $emails[$email] = $email;
        }

        return array_values($emails);
    }
}
