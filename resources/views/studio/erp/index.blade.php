<x-layouts::app title="Gestione tecnica ERP">
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 pb-32 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1400px] space-y-5">
            @if (session('status'))
                <div role="status" class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>
            @endif

            <section class="pm-panel relative overflow-hidden p-5 sm:p-7">
                <div aria-hidden="true" class="pointer-events-none absolute -right-20 -top-28 size-80 rounded-full bg-[#E10600]/[.08] blur-[70px]"></div>
                <div class="relative flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full border border-pm-border bg-pm-subtle px-3 py-1.5 font-mono text-[10px] font-bold uppercase tracking-[.14em] text-pm-text-secondary">Gestione / Solo editor</span>
                            <span class="rounded-full border border-pm-success/25 bg-pm-success-subtle px-3 py-1.5 text-[10px] font-bold text-pm-success">Controlli non distruttivi</span>
                        </div>
                        <h1 class="mt-4 text-3xl font-black tracking-[-.05em] text-pm-text sm:text-4xl">Gestione tecnica ERP<span class="text-[#E10600]">.</span></h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-pm-text-secondary">Panoramica tecnica dei processi PitMetric: stato dei moduli, quantità di dati e verifiche di integrità. I test leggono le informazioni operative senza modificarle.</p>
                    </div>
                    <form method="POST" action="{{ route('studio.erp.checks') }}" class="shrink-0">
                        @csrf
                        <button type="submit" class="pm-race-button w-full justify-center sm:w-auto"><flux:icon.shield-check class="size-4" /> Esegui diagnostica ERP</button>
                    </form>
                </div>
            </section>

            <section aria-labelledby="erp-overview-title" class="space-y-3">
                <div class="flex flex-wrap items-end justify-between gap-2 px-1">
                    <h2 id="erp-overview-title" class="text-base font-black tracking-tight text-pm-text">Panoramica ERP</h2>
                    <span class="font-mono text-[10px] uppercase tracking-[.12em] text-pm-muted">Record aggregati di tutti i workspace</span>
                </div>
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    @foreach ([
                        ['label' => 'Team / Workspace', 'value' => $totals['workspaces'], 'note' => 'Ambienti registrati'],
                        ['label' => 'Veicoli', 'value' => $totals['vehicles'], 'note' => 'Garage complessivo'],
                        ['label' => 'Sessioni', 'value' => $totals['sessions'], 'note' => 'Sessioni nel database'],
                        ['label' => 'Lavori aperti', 'value' => $totals['open_orders'], 'note' => 'Da fare, in corso o bloccati'],
                    ] as $metric)
                        <article class="pm-panel flex min-w-0 flex-col justify-between p-4 sm:p-5">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-pm-text-secondary">{{ $metric['label'] }}</p>
                            <p class="mt-5 font-mono text-3xl font-black tabular-nums text-pm-text sm:text-4xl">{{ $metric['value'] === null ? '—' : number_format($metric['value'], 0, ',', '.') }}</p>
                            <p class="mt-2 text-[11px] leading-5 text-pm-muted">{{ $metric['note'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="pm-panel min-w-0 p-5 sm:p-7" aria-labelledby="erp-modules-title">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 id="erp-modules-title" class="text-xl font-black tracking-tight text-pm-text">Moduli operativi</h2>
                        <p class="mt-1 text-xs leading-5 text-pm-muted">Disponibilità delle tabelle e dei percorsi applicativi. Il numero mostra i record principali di ciascun modulo.</p>
                    </div>
                    <span class="rounded-lg border border-pm-border bg-pm-subtle px-3 py-1.5 font-mono text-xs font-bold text-pm-text-secondary">{{ collect($modules)->where('status', 'ok')->count() }} / {{ count($modules) }} disponibili</span>
                </div>
                <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($modules as $module)
                        <article class="flex min-w-0 flex-col justify-between rounded-xl border border-pm-border bg-pm-subtle p-4 sm:p-5">
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="text-sm font-black text-pm-text">{{ $module['name'] }}</h3>
                                <span class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-bold {{ $module['status'] === 'ok' ? 'bg-pm-success-subtle text-pm-success' : ($module['status'] === 'warning' ? 'bg-pm-warning-subtle text-pm-warning' : 'bg-[#E10600]/15 text-[#FA7773]') }}">{{ $module['status'] === 'ok' ? 'Disponibile' : 'Da verificare' }}</span>
                            </div>
                            <p class="mt-2 text-xs leading-5 text-pm-muted">{{ $module['description'] }}</p>
                            <div class="mt-5 flex items-end justify-between gap-2 border-t border-pm-border pt-3">
                                <div>
                                    <p class="font-mono text-2xl font-black tabular-nums text-pm-text">{{ $module['count'] === null ? '—' : number_format($module['count'], 0, ',', '.') }}</p>
                                    <p class="text-[10px] text-pm-muted">Record principali</p>
                                </div>
                                @if ($module['status'] === 'ok')
                                    <a class="text-xs font-bold text-pm-text-secondary underline-offset-4 hover:text-pm-text hover:underline" href="{{ route($module['route']) }}">Apri modulo</a>
                                @endif
                            </div>
                            @if ($module['missing'] !== [])
                                <p class="mt-3 break-words text-[11px] leading-5 text-pm-warning">Tabelle mancanti: {{ implode(', ', $module['missing']) }}</p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="pm-panel min-w-0 p-5 sm:p-7" aria-labelledby="erp-diagnostics-title">
                @php($lastRun = $runs->first())
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 id="erp-diagnostics-title" class="text-xl font-black tracking-tight text-pm-text">Diagnostica e integrità</h2>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider {{ ! $lastRun ? 'border border-pm-border bg-pm-subtle text-pm-muted' : ($lastRun->status === 'ok' ? 'bg-pm-success-subtle text-pm-success' : ($lastRun->status === 'warning' ? 'bg-pm-warning-subtle text-pm-warning' : 'bg-[#E10600]/15 text-[#FA7773]')) }}">
                                {{ ! $lastRun ? 'Non verificato' : ($lastRun->status === 'ok' ? 'Operativo' : ($lastRun->status === 'warning' ? 'Attenzione' : 'Problemi rilevati')) }}
                            </span>
                        </div>
                        <p class="mt-2 max-w-3xl text-xs leading-6 text-pm-text-secondary">Controlli di connessione, migrazioni, moduli, collegamenti fra workspace, code e permessi di scrittura. Nessuna riparazione o modifica automatica ai dati.</p>
                    </div>
                    @if ($lastRun)
                        <span class="font-mono text-[11px] text-pm-muted">Ultimo controllo {{ $lastRun->created_at->timezone('Europe/Rome')->format('d/m/Y · H:i') }}</span>
                    @endif
                </div>

                @if ($lastRun)
                    <div class="mt-5 grid grid-cols-3 gap-2">
                        @foreach ([
                            ['value' => $lastRun->passed, 'label' => 'Superati', 'color' => 'text-pm-success bg-pm-success-subtle border-pm-success/20'],
                            ['value' => $lastRun->warnings, 'label' => 'Avvisi', 'color' => 'text-pm-warning bg-pm-warning-subtle border-pm-warning/20'],
                            ['value' => $lastRun->failed, 'label' => 'Errori', 'color' => 'text-[#FA7773] bg-[#E10600]/[.07] border-[#E10600]/20'],
                        ] as $counter)
                            <div class="min-w-0 rounded-xl border p-3 {{ $counter['color'] }}">
                                <p class="font-mono text-2xl font-black tabular-nums">{{ $counter['value'] }}</p>
                                <p class="mt-1 text-[11px] font-bold">{{ $counter['label'] }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-5 grid gap-2 md:grid-cols-2">
                        @foreach ($lastRun->results as $check)
                            <article class="flex min-w-0 items-start gap-3 rounded-xl border border-pm-border bg-pm-subtle p-3.5">
                                <span class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg text-[10px] font-black {{ $check['status'] === 'ok' ? 'bg-pm-success-subtle text-pm-success' : ($check['status'] === 'warning' ? 'bg-pm-warning-subtle text-pm-warning' : 'bg-[#E10600]/15 text-[#FA7773]') }}">{{ $check['status'] === 'ok' ? 'OK' : ($check['status'] === 'warning' ? '!' : 'X') }}</span>
                                <div class="min-w-0">
                                    <h3 class="text-xs font-bold text-pm-text">{{ $check['name'] }}</h3>
                                    <p class="mt-1 text-[11px] leading-5 text-pm-muted">{{ $check['detail'] }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="mt-5 rounded-xl border border-dashed border-pm-border bg-pm-subtle p-7 text-center sm:p-10">
                        <flux:icon.shield-check class="mx-auto size-9 text-pm-text-secondary" />
                        <h3 class="mt-3 text-sm font-bold text-pm-text">Nessun report ancora disponibile</h3>
                        <p class="mt-2 text-xs text-pm-muted">Avvia la prima diagnostica ERP dal pulsante in alto.</p>
                    </div>
                @endif
            </section>

            <section class="grid gap-4 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,.8fr)]">
                <div class="pm-panel min-w-0 p-5 sm:p-6">
                    <h2 class="text-lg font-black tracking-tight text-pm-text">Storico report ERP</h2>
                    <p class="mt-1 text-xs text-pm-muted">Ultime 8 verifiche, con data e autore.</p>
                    <div class="mt-4 space-y-2">
                        @forelse ($runs as $run)
                            <details class="group rounded-xl border border-pm-border bg-pm-subtle">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 [&::-webkit-details-marker]:hidden">
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
                        <a class="pm-ghost-button inline-flex items-center justify-center gap-2 text-center" href="{{ route('studio.site.index') }}">Monitoraggio sito <flux:icon.arrow-top-right-on-square class="size-4" /></a>
                        <a class="pm-ghost-button inline-flex items-center justify-center gap-2 text-center" href="https://github.com/ButtiGit/pitmetric/actions/workflows/tests.yml" target="_blank" rel="noopener noreferrer">Suite automatica CI <flux:icon.arrow-top-right-on-square class="size-4" /></a>
                    </div>
                </aside>
            </section>
        </div>
    </div>
</x-layouts::app>
