            <section class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,.8fr)]">
                <div class="pm-panel min-w-0 p-5 sm:p-6">
                    <h2 class="text-lg font-black tracking-tight text-pm-text">Storico report sito</h2>
                    <p class="mt-1 text-xs text-pm-muted">Ultime 8 scansioni, consultabili anche dopo una nuova verifica.</p>
                    <div class="mt-4 space-y-2">
                        @forelse ($runs as $run)
                            <details class="group rounded-xl border border-pm-border bg-pm-subtle">
                                <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3 px-4 py-3 [&::-webkit-details-marker]:hidden">
                                    <span class="min-w-0"><span class="block text-xs font-bold text-pm-text">{{ $run->created_at->timezone('Europe/Rome')->format('d/m/Y · H:i:s') }}</span><span class="mt-0.5 block truncate text-[11px] text-pm-muted">{{ $run->author?->name ?? 'Sistema' }}</span></span>
                                    <span class="flex shrink-0 items-center gap-2"><span class="font-mono text-[10px] text-pm-text-secondary">{{ $run->passed }} OK · {{ $run->warnings }} avvisi · {{ $run->failed }} errori</span><flux:icon.chevron-down class="size-4 text-pm-muted transition-transform group-open:rotate-180" /></span>
                                </summary>
                                <div class="space-y-2 border-t border-pm-border px-4 py-3">
                                    @foreach ($run->results as $check)
                                        <div class="flex items-start gap-2 text-[11px] leading-5"><span class="shrink-0 font-bold {{ $check['status'] === 'ok' ? 'text-pm-success' : ($check['status'] === 'warning' ? 'text-pm-warning' : 'text-[#FA7773]') }}">{{ strtoupper($check['status']) }}</span><p class="text-pm-text-secondary"><span class="font-semibold text-pm-text">{{ $check['name'] }}:</span> {{ $check['detail'] }}</p></div>
                                    @endforeach
                                </div>
                            </details>
                        @empty
                            <p class="rounded-xl border border-dashed border-pm-border px-4 py-6 text-center text-xs text-pm-muted">Lo storico comparirà dopo il primo controllo.</p>
                        @endforelse
                    </div>
                </div>

                <aside class="pm-panel min-w-0 p-5 sm:p-6">
                    <h2 class="text-lg font-black tracking-tight text-pm-text">Come leggere questi dati</h2>
                    <div class="mt-4 space-y-4 text-xs leading-6 text-pm-text-secondary">
                        <p><span class="font-bold text-pm-text">Visite:</span> sono visualizzazioni di pagine, non visitatori unici. I conteggi iniziano dall’attivazione del modulo; non recuperiamo lo storico passato.</p>
                        <p><span class="font-bold text-pm-text">App:</span> i download APK forniti da GitHub sono riferiti alla release corrente, non ai soli ultimi 30 giorni. I clic APK e PWA misurano i pulsanti del sito; il nuovo contatore delle prime aperture misura i browser/installazioni che eseguono la versione aggiornata, non download o utenti online. La cancellazione dei dati locali può far contare di nuovo un browser.</p>
                        <p><span class="font-bold text-pm-text">Diagnostica:</span> i controlli della dashboard verificano disponibilità e configurazione. I test funzionali e browser approfonditi vengono eseguiti nella pipeline CI, non sul database di produzione.</p>
                        <p><span class="font-bold text-pm-text">Errori:</span> le risposte HTTP 5xx sono aggregate per percorso; per analizzare stack trace e dettagli tecnici consulta i log di Coolify/Laravel.</p>
                    </div>
                    <a href="https://github.com/ButtiGit/pitmetric/actions/workflows/tests.yml" target="_blank" rel="noopener noreferrer" class="pm-ghost-button mt-6 inline-flex max-w-full items-center justify-center gap-2 text-center">Apri i test completi su GitHub <flux:icon.arrow-top-right-on-square class="size-4" /></a>
                </aside>
            </section>
