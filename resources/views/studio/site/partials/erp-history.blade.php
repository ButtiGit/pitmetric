            <section class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,.8fr)]">
                <div class="pm-panel min-w-0 p-5 sm:p-6">
                    <h2 class="text-lg font-black tracking-tight text-pm-text">Storico report ERP</h2>
                    <p class="mt-1 text-xs text-pm-muted">Ultime 8 verifiche, con data e autore.</p>
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
                            <p class="rounded-xl border border-dashed border-pm-border px-4 py-6 text-center text-xs text-pm-muted">Lo storico sarà disponibile dopo la prima verifica.</p>
                        @endforelse
                    </div>
                </div>
                <aside class="pm-panel min-w-0 p-5 sm:p-6">
                    <h2 class="text-lg font-black tracking-tight text-pm-text">Limiti dei controlli</h2>
                    <div class="mt-4 space-y-3 text-xs leading-6 text-pm-text-secondary">
                        <p>La dashboard mostra <strong class="text-pm-text">conteggi globali aggregati</strong>: nessun dettaglio dei dati dei singoli team viene esposto qui.</p>
                        <p>La verifica di integrità confronta i workspace di <strong class="text-pm-text">7 collegamenti ERP critici</strong>. Non sostituisce i test funzionali in CI o un audit completo del database.</p>
                        <p>Lo stato della coda riflette i job registrati; per verificare che un worker sia effettivamente in esecuzione utilizza anche i log di Coolify.</p>
                    </div>
                    <div class="mt-6 flex flex-col gap-2">
                        <a class="pm-ghost-button inline-flex items-center justify-center gap-2 text-center" href="#controlli">Torna ai controlli <flux:icon.arrow-top-right-on-square class="size-4" /></a>
                        <a class="pm-ghost-button inline-flex items-center justify-center gap-2 text-center" href="https://github.com/ButtiGit/pitmetric/actions/workflows/tests.yml" target="_blank" rel="noopener noreferrer">Suite automatica CI <flux:icon.arrow-top-right-on-square class="size-4" /></a>
                    </div>
                </aside>
            </section>
