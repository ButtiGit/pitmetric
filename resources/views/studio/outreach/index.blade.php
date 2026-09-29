<x-layouts::app title="PitMetric Outreach">
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1220px] space-y-5">
            @if (session('status'))
                <div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>
            @endif

            <section class="pm-panel p-6 sm:p-8">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.14em] text-pm-accent">Studio / Outreach</div>
                        <h1 class="mt-3 text-3xl font-black tracking-[-0.035em] text-pm-text sm:text-4xl">Pubblicità PitMetric, senza sembrare spam</h1>
                        <p class="mt-3 max-w-3xl text-sm leading-7 text-pm-text-secondary">Inserisci il contatto, scegli il tono, personalizza il testo e invia una mail con identità PitMetric, demo e risposta diretta al tuo Inbox Studio.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('studio.mail.index') }}" class="pm-race-button">Inbox</a>
                        <a href="{{ route('studio.updates.index') }}" class="pm-ghost-button">Pubblicazione</a>
                        <a href="{{ route('studio.users.index') }}" class="pm-ghost-button">Utenti</a>
                    </div>
                </div>
            </section>

            <div class="grid gap-5 xl:grid-cols-[1.05fr_.95fr]">
                <section class="pm-panel p-6 sm:p-8">
                    <form id="outreach-form" method="POST" action="{{ route('studio.outreach.send') }}" class="space-y-6" onsubmit="return confirm('Inviare la mail ai destinatari inseriti?')">
                        @csrf

                        <div>
                            <label for="recipients" class="text-sm font-bold text-pm-text">Indirizzo email</label>
                            <p class="mt-1 text-xs leading-5 text-pm-muted">Puoi usare un solo contatto oppure più indirizzi separati da riga, virgola o spazio. Ogni destinatario riceve una mail separata.</p>
                            <textarea id="recipients" name="recipients" rows="4" required class="mt-3 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 font-mono text-sm text-pm-text outline-none transition focus:border-pm-accent" placeholder="info@team.it">{{ old('recipients') }}</textarea>
                            @error('recipients')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="company" class="text-sm font-bold text-pm-text">Nome realtà / team</label>
                                <input id="company" name="company" maxlength="160" value="{{ old('company') }}" class="mt-2 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm text-pm-text" placeholder="Kart Planet">
                            </div>
                            <div>
                                <label for="locale" class="text-sm font-bold text-pm-text">Lingua</label>
                                <select id="locale" name="locale" class="mt-2 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm text-pm-text">
                                    <option value="it" @selected(old('locale', 'it') === 'it')>Italiano</option>
                                    <option value="en" @selected(old('locale') === 'en')>English</option>
                                </select>
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
                            <input id="subject" name="subject" maxlength="180" value="{{ old('subject', $templates['professional']['subject']) }}" class="mt-2 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm text-pm-text">
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
                            @php
                                $defaultMessage = str_replace(
                                    ['{{company}}', 'Buongiorno ,'],
                                    ['', 'Buongiorno,'],
                                    $templates['professional']['message'],
                                );
                            @endphp
                            <textarea id="message" name="message" rows="11" maxlength="6000" class="mt-3 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm leading-6 text-pm-text outline-none transition focus:border-pm-accent">{{ old('message', $defaultMessage) }}</textarea>
                            @error('message')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="note" class="text-sm font-bold text-pm-text">Nota specifica facoltativa</label>
                            <p class="mt-1 text-xs leading-5 text-pm-muted">Una frase davvero riferita al destinatario aumenta molto la qualità del contatto.</p>
                            <textarea id="note" name="note" rows="3" maxlength="800" class="mt-3 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm text-pm-text" placeholder="Ho visto che organizzate...">{{ old('note') }}</textarea>
                        </div>

                        <label class="flex items-start gap-3 rounded-xl border border-pm-warning/20 bg-pm-warning-subtle px-4 py-3 text-xs leading-5 text-pm-text-secondary">
                            <input type="checkbox" name="compliance_confirmed" value="1" required class="mt-0.5 size-4 shrink-0">
                            <span>Confermo che i destinatari sono contatti professionali pertinenti e che l'invio rispetta la base lecita o il consenso richiesto. Non sto usando liste acquistate o raccolte indiscriminatamente.</span>
                        </label>
                        @error('compliance_confirmed')<p class="text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror

                        <div class="flex flex-col gap-3 border-t border-pm-border pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-xs leading-5 text-pm-muted">
                                Da: <span class="font-semibold text-pm-text-secondary">{{ config('services.resend.studio_from_name') }} &lt;{{ config('services.resend.studio_from_address') }}&gt;</span><br>
                                Risposte: <span class="font-mono text-pm-text-secondary">{{ config('services.resend.studio_reply_to') }}</span>
                            </div>
                            <button type="submit" class="pm-race-button">Invia pubblicità</button>
                        </div>
                    </form>
                </section>

                <section class="pm-panel overflow-hidden xl:sticky xl:top-7 xl:self-start">
                    <div class="border-b border-pm-border px-6 py-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.14em] text-pm-muted">Anteprima identità</div>
                    </div>
                    <div class="bg-[#f4f4f5] p-4 sm:p-6">
                        <div class="mx-auto max-w-[560px] border border-zinc-200 bg-white text-zinc-900 shadow-sm">
                            <div class="border-b-4 border-[#E10600] bg-[#090a0c] px-6 py-5 text-white">
                                <img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-8 w-auto max-w-48">
                                <div class="mt-2 font-mono text-[10px] uppercase tracking-[0.18em] text-zinc-400">Trackside operations</div>
                            </div>
                            <div class="px-6 py-7">
                                <div class="text-[11px] font-bold uppercase tracking-[0.14em] text-[#E10600]">PitMetric / contatto diretto</div>
                                <div id="preview-message" class="mt-4 whitespace-pre-wrap text-sm leading-6 text-zinc-600"></div>
                                <div id="preview-note" class="mt-5 hidden border-l-4 border-[#E10600] bg-zinc-50 px-4 py-3 text-sm leading-6 text-zinc-700"></div>
                                <span class="mt-6 inline-block bg-[#E10600] px-5 py-3 text-sm font-black text-white">Esplora la Demo</span>
                                <div class="mt-6 border-t border-zinc-200 pt-4 text-sm leading-6 text-zinc-600"><strong class="text-zinc-900">Simone Buttice</strong><br>Sviluppatore e ideatore di PitMetric<br><span class="font-semibold text-[#E10600]">pitmetric.it</span></div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const templatesIt = @json($templates);
            const templatesEn = {
                friendly: {subject: 'Can I show you what I am building for motorsport?', message: 'Hi {{company}},\n\nI am Simone, the developer behind PitMetric. I am building it to keep the practical side of track work in one place: vehicles, components, setups, sessions, maintenance, costs, timing and telemetry.\n\nI would genuinely value your feedback and would be happy if you explored the demo to see whether any part could be useful in your day-to-day work.'},
                professional: {subject: 'PitMetric - motorsport technical management platform', message: 'Hello {{company}},\n\nMy name is Simone Buttice and I am the developer of PitMetric, a platform designed to organise technical motorsport operations in one workspace.\n\nPitMetric covers vehicles, components and usage history, configurations, setups, sessions, maintenance, costs, timing and telemetry. I am contacting selected motorsport organisations to gather concrete feedback and understand where the product can create real value.'},
                local: {subject: 'A local motorsport software project I would like to show you', message: 'Hello {{company}},\n\nI am Simone, the developer of PitMetric. I am reaching out personally because I prefer speaking directly with motorsport organisations rather than sending anonymous campaigns.\n\nPitMetric is built around real trackside workflows: vehicle history, components, setups, sessions, maintenance, costs, timing and telemetry. I would be glad if you took a look at the demo and told me what you would change or need in practice.'},
                technical: {subject: 'PitMetric - setups, components, sessions and technical history', message: 'Hello {{company}},\n\nPitMetric is a technical workspace for teams, drivers and preparers who need traceability across a vehicle\'s life. It connects component usage, configurations, setup changes, sessions, maintenance, expenses, timing and telemetry so information remains linked instead of scattered across notes and chats.\n\nI am looking for experienced motorsport feedback to validate the workflow against real track operations.'},
                custom: {subject: '', message: ''}
            };

            const form = document.getElementById('outreach-form');
            const company = document.getElementById('company');
            const locale = document.getElementById('locale');
            const subject = document.getElementById('subject');
            const message = document.getElementById('message');
            const note = document.getElementById('note');
            const previewMessage = document.getElementById('preview-message');
            const previewNote = document.getElementById('preview-note');

            const tone = () => form.querySelector('input[name="tone"]:checked')?.value || 'professional';
            const templates = () => locale.value === 'en' ? templatesEn : templatesIt;
            const personalise = (value) => value
                .replaceAll('{{company}}', company.value.trim())
                .replace('Ciao ,', 'Ciao,')
                .replace('Buongiorno ,', 'Buongiorno,')
                .replace('Hi ,', 'Hi,')
                .replace('Hello ,', 'Hello,');

            const refreshPreview = () => {
                previewMessage.textContent = message.value;
                previewNote.textContent = note.value;
                previewNote.classList.toggle('hidden', note.value.trim() === '');
            };

            const applyTemplate = () => {
                const selected = templates()[tone()] || templates().professional;
                if (tone() !== 'custom') {
                    subject.value = personalise(selected.subject);
                    message.value = personalise(selected.message);
                }
                refreshPreview();
            };

            document.getElementById('apply-template').addEventListener('click', applyTemplate);
            form.querySelectorAll('input[name="tone"]').forEach((input) => input.addEventListener('change', applyTemplate));
            locale.addEventListener('change', applyTemplate);
            company.addEventListener('input', () => {
                if (tone() !== 'custom') applyTemplate();
            });
            message.addEventListener('input', refreshPreview);
            note.addEventListener('input', refreshPreview);
            refreshPreview();
        })();
    </script>
</x-layouts::app>
