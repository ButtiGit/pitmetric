<x-layouts::app title="PitMetric Outreach">
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1180px] space-y-5">
            @if (session('status'))
                <div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>
            @endif

            <section class="pm-panel p-6 sm:p-8">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.14em] text-pm-accent">Studio / Outreach</div>
                        <h1 class="mt-3 text-3xl font-black tracking-[-0.035em] text-pm-text sm:text-4xl">Invia PitMetric a team e piloti</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-pm-text-secondary">Inserisci contatti professionali pertinenti. Ogni destinatario riceve una mail separata, brandizzata PitMetric, con CTA al sito e opt-out personale.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('studio.updates.index') }}" class="pm-ghost-button">Development log</a>
                        <a href="{{ route('studio.users.index') }}" class="pm-ghost-button">Utenti</a>
                    </div>
                </div>
            </section>

            <div class="grid gap-5 xl:grid-cols-[1.05fr_.95fr]">
                <section class="pm-panel p-6 sm:p-8">
                    <form method="POST" action="{{ route('studio.outreach.send') }}" class="space-y-6" onsubmit="return confirm('Inviare la campagna ai destinatari inseriti?')">
                        @csrf

                        <div>
                            <label for="recipients" class="text-sm font-bold text-pm-text">Indirizzi email</label>
                            <p class="mt-1 text-xs leading-5 text-pm-muted">Uno per riga oppure separati da virgola/spazio. Duplicati rimossi automaticamente. Massimo 25 per invio.</p>
                            <textarea id="recipients" name="recipients" rows="9" required class="mt-3 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 font-mono text-sm text-pm-text outline-none transition focus:border-pm-accent" placeholder="team@example.com&#10;driver@example.com">{{ old('recipients') }}</textarea>
                            @error('recipients')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="locale" class="text-sm font-bold text-pm-text">Lingua</label>
                                <select id="locale" name="locale" class="mt-2 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm text-pm-text">
                                    <option value="it" @selected(old('locale', 'it') === 'it')>Italiano</option>
                                    <option value="en" @selected(old('locale') === 'en')>English</option>
                                </select>
                            </div>
                            <div>
                                <label for="subject" class="text-sm font-bold text-pm-text">Oggetto</label>
                                <input id="subject" name="subject" maxlength="140" value="{{ old('subject', $defaultSubjectIt) }}" class="mt-2 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm text-pm-text" />
                                @error('subject')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label for="note" class="text-sm font-bold text-pm-text">Nota personalizzata facoltativa</label>
                            <p class="mt-1 text-xs leading-5 text-pm-muted">Aggiungi una frase specifica sul team o su come lo hai trovato. È il modo migliore per evitare l'effetto “mail in massa”.</p>
                            <textarea id="note" name="note" rows="4" maxlength="600" class="mt-3 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm text-pm-text" placeholder="Ho visto che correte nel campionato…">{{ old('note') }}</textarea>
                        </div>

                        <label class="flex items-start gap-3 rounded-xl border border-pm-warning/20 bg-pm-warning-subtle px-4 py-3 text-xs leading-5 text-pm-text-secondary">
                            <input type="checkbox" name="compliance_confirmed" value="1" required class="mt-0.5 size-4 shrink-0">
                            <span>Confermo che i destinatari sono contatti professionali pertinenti e che l'invio rispetta la base lecita/il consenso richiesto per questi contatti. Non sto usando liste acquistate o raccolte indiscriminatamente.</span>
                        </label>
                        @error('compliance_confirmed')<p class="text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror

                        <div class="flex flex-col gap-3 border-t border-pm-border pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-xs text-pm-muted">Mittente configurato: <span class="font-semibold text-pm-text-secondary">{{ config('mail.from.name') }} &lt;{{ config('mail.from.address') }}&gt;</span></div>
                            <button type="submit" class="pm-race-button">Invia campagna</button>
                        </div>
                    </form>
                </section>

                <section class="pm-panel overflow-hidden">
                    <div class="border-b border-pm-border px-6 py-4">
                        <div class="text-xs font-semibold uppercase tracking-[0.14em] text-pm-muted">Anteprima contenuto</div>
                    </div>
                    <div class="bg-[#f4f4f5] p-4 sm:p-6">
                        <div class="mx-auto max-w-[560px] border border-zinc-200 bg-white text-zinc-900 shadow-sm">
                            <div class="border-b-4 border-[#E10600] bg-[#090a0c] px-6 py-5 text-white">
                                <div class="font-mono text-[10px] uppercase tracking-[0.18em] text-zinc-400">PitMetric / Trackside operations</div>
                                <div class="mt-1 text-2xl font-black">PIT<span class="text-[#E10600]">METRIC</span></div>
                            </div>
                            <div class="px-6 py-7">
                                <div class="text-[11px] font-bold uppercase tracking-[0.14em] text-[#E10600]">Per piloti e team motorsport</div>
                                <h2 class="mt-3 text-2xl font-black leading-tight tracking-tight">Quanto del vostro weekend finisce ancora tra note, chat e memoria?</h2>
                                <p class="mt-4 text-sm leading-6 text-zinc-600">PitMetric riunisce tempi, sessioni, setup, configurazioni, componenti, manutenzione, costi e storico tecnico del mezzo.</p>
                                <div class="mt-5 border-l-4 border-[#E10600] bg-zinc-50 px-4 py-3 text-sm leading-6 text-zinc-700">La nota personalizzata, se inserita, appare qui.</div>
                                <p class="mt-5 text-sm leading-6 text-zinc-600">Cerchiamo team che vogliano provarlo davvero sul campo. Per chi collabora attivamente allo sviluppo, PitMetric è gratuito.</p>
                                <span class="mt-5 inline-block bg-[#E10600] px-5 py-3 text-sm font-black text-white">Scopri PitMetric</span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-layouts::app>
