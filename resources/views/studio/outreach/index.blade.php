<x-layouts::app title="PitMetric Outreach">
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1220px] space-y-5">
            @if (session('status'))
                <div role="status" @class([
                    'rounded-xl border px-4 py-3 text-sm font-semibold',
                    'border-pm-success/25 bg-pm-success-subtle text-pm-success' => ! session('outreach_failed'),
                    'border-pm-warning/25 bg-pm-warning-subtle text-pm-text-secondary' => session('outreach_failed'),
                ])>{{ session('status') }}</div>
            @endif

            <section class="pm-panel p-6 sm:p-8">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.14em] text-pm-accent">Studio / Outreach</div>
                        <h1 class="mt-3 text-3xl font-black tracking-[-0.035em] text-pm-text sm:text-4xl">Presenta PitMetric in un invio</h1>
                        <p class="mt-3 max-w-3xl text-sm leading-7 text-pm-text-secondary">Inserisci gli indirizzi email e premi Invio. La comunicazione è già pronta: presenta PitMetric, le funzionalità attuali e la prova gratuita.</p>
                    </div>
                    <a href="{{ route('studio.mail.index') }}" class="shrink-0 text-sm font-semibold text-pm-accent underline underline-offset-4">Apri Inbox</a>
                </div>
            </section>

            <div class="grid gap-5 xl:grid-cols-[1.05fr_.95fr]">
                <section class="pm-panel self-start p-6 sm:p-8">
                    <form id="outreach-form" method="POST" action="{{ route('studio.outreach.send') }}" class="space-y-6">
                        @csrf

                        <div>
                            <label for="recipients" class="text-sm font-bold text-pm-text">Destinatari</label>
                            <p class="mt-1 text-xs leading-5 text-pm-muted">Fino a 25 indirizzi, separati da virgole, punti e virgola, spazi o righe. Ogni destinatario riceve una mail separata.</p>
                            <textarea id="recipients" name="recipients" rows="4" required maxlength="6000" aria-describedby="recipients-help" class="mt-3 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 font-mono text-sm text-pm-text outline-none transition focus:border-pm-accent" placeholder="info@team.it">{{ old('recipients') }}</textarea>
                            <p id="recipients-help" class="mt-2 text-xs leading-5 text-pm-muted">Invio per spedire · Maiusc+Invio per andare a capo.</p>
                            @error('recipients')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                        </div>

                        <div class="flex flex-col gap-3 border-t border-pm-border pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-xs leading-5 text-pm-muted">
                                Da: <span class="font-semibold text-pm-text-secondary">{{ config('services.resend.studio_from_name') }} &lt;{{ config('services.resend.studio_from_address') }}&gt;</span><br>
                                Risposte: <span class="font-mono text-pm-text-secondary">{{ config('services.resend.studio_reply_to') }}</span>
                            </div>
                            <button id="send-outreach" type="submit" class="pm-race-button disabled:cursor-wait disabled:opacity-60">Invia comunicazione</button>
                        </div>

                        <p id="outreach-progress" role="status" class="text-sm text-pm-muted"></p>
                        @if (session('outreach_failed'))
                            <p class="rounded-xl border border-pm-warning/25 bg-pm-warning-subtle p-4 text-sm text-pm-text-secondary">Sono rimasti in lista solo gli indirizzi per cui l'invio non è riuscito. Puoi riprovare senza reinviare la mail agli altri.</p>
                        @endif
                        <details id="outreach-options" class="border-t border-pm-border pt-5" @if ($errors->hasAny(['locale', 'tone', 'company', 'subject', 'message', 'note'])) open @endif>
                            <summary class="cursor-pointer text-sm font-bold text-pm-text"><span>Personalizza la comunicazione <span class="font-normal text-pm-muted">(facoltativo)</span></span></summary>
                            <div class="mt-5 space-y-5">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="company" class="text-sm font-bold text-pm-text">Nome realtà / team</label>
                                        <input id="company" name="company" maxlength="160" value="{{ old('company') }}" class="mt-2 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm text-pm-text" placeholder="Kart Planet">
                                        @error('company')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="locale" class="text-sm font-bold text-pm-text">Lingua</label>
                                        <select id="locale" name="locale" class="mt-2 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm text-pm-text">
                                            <option value="it" @selected(old('locale', 'it') === 'it')>Italiano</option>
                                            <option value="en" @selected(old('locale') === 'en')>English</option>
                                        </select>
                                        @error('locale')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div>
                                    <label class="text-sm font-bold text-pm-text">Formato</label>
                                    <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                        @foreach ($tones as $value => $label)
                                            <label class="cursor-pointer rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm font-semibold text-pm-text-secondary transition hover:border-pm-accent/50">
                                                <input class="mr-2" type="radio" name="tone" value="{{ $value }}" @checked(old('tone', 'professional') === $value)>
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('tone')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="subject" class="text-sm font-bold text-pm-text">Oggetto</label>
                                    <input id="subject" name="subject" maxlength="180" value="{{ old('subject', $defaultSubject) }}" class="mt-2 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm text-pm-text">
                                    @error('subject')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <div class="flex items-end justify-between gap-3">
                                        <div>
                                            <label for="message" class="text-sm font-bold text-pm-text">Testo della mail</label>
                                            <p class="mt-1 text-xs leading-5 text-pm-muted">Il formato scelto prepara una base che puoi modificare liberamente prima dell'invio.</p>
                                        </div>
                                        <button id="apply-template" type="button" class="pm-ghost-button">Rigenera testo</button>
                                    </div>
                                    <textarea id="message" name="message" rows="11" maxlength="6000" class="mt-3 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm leading-6 text-pm-text outline-none transition focus:border-pm-accent">{{ old('message', $defaultMessage) }}</textarea>
                                    @error('message')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                                </div>

                                <div>
                                    <label for="note" class="text-sm font-bold text-pm-text">Nota specifica facoltativa</label>
                                    <p class="mt-1 text-xs leading-5 text-pm-muted">Una frase davvero riferita al destinatario aumenta molto la qualità del contatto.</p>
                                    <textarea id="note" name="note" rows="3" maxlength="800" class="mt-3 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm text-pm-text" placeholder="Ho visto che organizzate...">{{ old('note') }}</textarea>
                                    @error('note')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                                </div>

                            </div>
                        </details>
                    </form>
                </section>

                <section class="pm-panel overflow-hidden xl:sticky xl:top-7 xl:self-start">
                    <div class="border-b border-pm-border px-6 py-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.14em] text-pm-muted">La mail che riceveranno</div>
                    </div>
                    <div class="border-b border-pm-border px-6 py-4">
                        <p class="text-xs text-pm-muted">Oggetto</p>
                        <p id="preview-subject" class="mt-1 text-sm font-semibold text-pm-text">{{ old('subject', $defaultSubject) }}</p>
                    </div>
                    <div id="outreach-preview" class="max-h-[44rem] overflow-y-auto bg-[#f4f4f5] p-3 sm:p-5" tabindex="0" aria-label="Anteprima della comunicazione">
                        <x-emails.outreach :locale="old('locale', 'it') ?: 'it'" :message-body="old('message', $defaultMessage) ?: $defaultMessage" :note="old('note', '') ?? ''" />
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const templatesByLocale = @json($templatesByLocale);
            const companyToken = String.fromCharCode(123, 123) + 'company' + String.fromCharCode(125, 125);
            const form = document.getElementById('outreach-form');
            const company = document.getElementById('company');
            const locale = document.getElementById('locale');
            const subject = document.getElementById('subject');
            const message = document.getElementById('message');
            const note = document.getElementById('note');
            const recipients = document.getElementById('recipients');
            const preview = document.getElementById('outreach-preview');
            const previewMessage = preview.querySelector('[data-outreach-message]');
            const previewNote = preview.querySelector('[data-outreach-note]');
            const paragraphStyle = previewMessage.firstElementChild.getAttribute('style');
            const links = @json($localizedLinks);
            const labels = {
                it: {
                    eyebrow: 'Un invito a provare PitMetric', demo: 'Esplora la demo', app: 'Scopri l’app Android',
                    reply: 'Per un confronto o per iniziare la prova, rispondete direttamente a questa email: vi risponderò personalmente.',
                    signature: 'Sviluppatore e ideatore di PitMetric',
                    footer: 'Messaggio inviato da PitMetric. Se il progetto non è pertinente o preferisci non ricevere altre comunicazioni, puoi usare questo link:',
                    unsubscribe: 'non ricevere altre email',
                },
                en: {
                    eyebrow: 'An invitation to try PitMetric', demo: 'Explore the demo', app: 'Discover the Android app',
                    reply: 'To discuss the project or start testing, reply directly to this email. I will get back to you personally.',
                    signature: 'Developer and creator of PitMetric',
                    footer: 'A message from PitMetric. If the project is not relevant or you prefer not to receive further messages, use this link:',
                    unsubscribe: 'stop future emails',
                },
            };
            const tone = () => form.querySelector('input[name="tone"]:checked')?.value || 'professional';
            const selectedTemplate = () => templatesByLocale[locale.value]?.[tone()] || templatesByLocale.it.professional;
            const personalise = (value) => value.replaceAll(companyToken, company.value.trim())
                .replace('Ciao ,', 'Ciao,').replace('Buongiorno ,', 'Buongiorno,').replace('Hi ,', 'Hi,').replace('Hello ,', 'Hello,');
            const refreshPreview = () => {
                const language = locale.value === 'en' ? 'en' : 'it';
                document.getElementById('preview-subject').textContent = subject.value.trim() || personalise(selectedTemplate().subject);
                previewMessage.replaceChildren();
                const body = message.value.trim() || personalise(selectedTemplate().message);
                body.split(/\r?\n\s*\r?\n/).forEach((text) => {
                    const paragraph = document.createElement('p');
                    paragraph.setAttribute('style', paragraphStyle);
                    text.split(/\r?\n/).forEach((line, index) => {
                        if (index > 0) paragraph.append(document.createElement('br'));
                        paragraph.append(document.createTextNode(line));
                    });
                    previewMessage.append(paragraph);
                });
                previewNote.textContent = note.value;
                previewNote.hidden = note.value.trim() === '';
                preview.querySelector('table').lang = language;
                preview.querySelectorAll('[data-outreach-label]').forEach((element) => {
                    element.textContent = labels[language][element.dataset.outreachLabel];
                });
                preview.querySelectorAll('[data-outreach-link]').forEach((element) => {
                    element.href = links[language][element.dataset.outreachLink];
                });
            };
            const applyTemplate = () => {
                if (tone() !== 'custom') {
                    subject.value = personalise(selectedTemplate().subject);
                    message.value = personalise(selectedTemplate().message);
                }
                refreshPreview();
            };
            document.getElementById('apply-template').addEventListener('click', applyTemplate);
            form.querySelectorAll('input[name="tone"]').forEach((input) => input.addEventListener('change', applyTemplate));
            locale.addEventListener('change', applyTemplate);
            company.addEventListener('input', () => {
                if (tone() !== 'custom') applyTemplate();
            });
            [subject, message, note].forEach((input) => input.addEventListener('input', refreshPreview));
            recipients.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && !event.shiftKey && !event.ctrlKey && !event.altKey && !event.metaKey && !event.isComposing) {
                    event.preventDefault();
                    form.requestSubmit();
                }
            });
            let sending = false;
            form.addEventListener('submit', (event) => {
                if (sending) {
                    event.preventDefault();
                    return;
                }
                sending = true;
                const button = document.getElementById('send-outreach');
                button.disabled = true;
                button.textContent = 'Invio in corso…';
                form.setAttribute('aria-busy', 'true');
                document.getElementById('outreach-progress').textContent = 'Sto inviando una mail separata per ciascun destinatario. Attendi il riepilogo.';
            });
            refreshPreview();
        })();
    </script>
</x-layouts::app>
