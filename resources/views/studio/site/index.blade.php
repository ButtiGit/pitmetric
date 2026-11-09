<x-layouts::app title="Gestione sito">
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 pb-32 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1400px] space-y-5">
            @if (session('status'))
                <div role="status" class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>
            @endif

            <section class="pm-panel relative overflow-hidden p-5 sm:p-7">
                <div aria-hidden="true" class="pointer-events-none absolute -right-24 -top-36 size-96 rounded-full bg-[#E10600]/[.09] blur-[80px]"></div>
                <div class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-2 rounded-full border border-pm-border bg-pm-subtle px-3 py-1.5 font-mono text-[10px] font-bold uppercase tracking-[.14em] text-pm-text-secondary">
                                <span class="size-1.5 rounded-full bg-[#E10600]"></span> Studio / Monitoraggio
                            </span>
                            <span class="font-mono text-[10px] uppercase tracking-[.13em] text-pm-muted">Dati degli ultimi 30 giorni</span>
                        </div>
                        <h1 class="mt-4 text-3xl font-black tracking-[-.05em] text-pm-text sm:text-4xl">Gestione sito<span class="text-[#E10600]">.</span></h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-pm-text-secondary">Traffico, interesse verso le app e stato tecnico di PitMetric in un solo posto. Le statistiche si aggiornano dalle nuove visite, senza salvare IP o dati personali dei visitatori.</p>
                    </div>
                    <div class="flex shrink-0 flex-col gap-2 sm:flex-row md:flex-col lg:flex-row">
                        <a href="{{ route('localized.home', ['locale' => app()->getLocale()]) }}" target="_blank" rel="noopener noreferrer" class="pm-ghost-button text-center">Visita il sito ↗</a>
                        <form method="POST" action="{{ route('studio.site.checks') }}">
                            @csrf
                            <button type="submit" class="pm-race-button w-full justify-center">Esegui verifiche <span aria-hidden="true">↗</span></button>
                        </form>
                    </div>
                </div>
            </section>

            <section aria-labelledby="site-metrics-title" class="space-y-3">
                <div class="flex items-end justify-between gap-3 px-1">
                    <h2 id="site-metrics-title" class="text-base font-black tracking-tight text-pm-text">Panoramica traffico</h2>
                    <span class="font-mono text-[10px] uppercase tracking-[.12em] text-pm-muted">Periodo mobile · 30 giorni</span>
                </div>
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-3 2xl:grid-cols-5">
                    @foreach ([
                        ['label' => 'Visualizzazioni', 'value' => $totals['page_view'], 'note' => 'Pagine pubbliche', 'accent' => 'text-pm-text'],
                        ['label' => 'Download APK (GitHub)', 'value' => $githubApkDownloads, 'note' => 'Totale release corrente · GitHub', 'accent' => 'text-[#F25A56]'],
                        ['label' => 'Clic download APK', 'value' => $totals['apk_download'], 'note' => 'Clic sul sito · 30 giorni', 'accent' => 'text-[#F2B774]'],
                        ['label' => 'Aperture PWA', 'value' => $totals['pwa_open'], 'note' => 'Clic su apri PWA', 'accent' => 'text-[#8BC7F6]'],
                        ['label' => 'Errori HTTP', 'value' => $totals['http_error'], 'note' => 'Risposte del server 5xx', 'accent' => $totals['http_error'] > 0 ? 'text-[#F2B774]' : 'text-pm-success'],
                    ] as $stat)
                        <article class="pm-panel flex min-w-0 flex-col justify-between p-4 sm:p-5">
                            <p class="text-[11px] font-bold uppercase tracking-[.1em] text-pm-text-secondary">{{ $stat['label'] }}</p>
                            <p class="mt-5 truncate font-mono text-3xl font-black tracking-[-.05em] sm:text-4xl {{ $stat['accent'] }}" title="{{ $stat['value'] === null ? 'Dato non disponibile' : number_format($stat['value'], 0, ',', '.') }}">{{ $stat['value'] === null ? '—' : number_format($stat['value'], 0, ',', '.') }}</p>
                            <p class="mt-2 text-[11px] leading-4 text-pm-muted">{{ $stat['note'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.45fr)_minmax(0,1fr)]">
                <section class="pm-panel min-w-0 p-5 sm:p-6" aria-labelledby="site-chart-title">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 id="site-chart-title" class="text-lg font-black tracking-tight text-pm-text">Andamento delle visite</h2>
                            <p class="mt-1 text-xs text-pm-muted">Visualizzazioni giornaliere, ultimi 14 giorni</p>
                        </div>
                        <span class="rounded-lg border border-pm-border bg-pm-subtle px-2.5 py-1.5 font-mono text-xs text-pm-text-secondary">Max {{ $trendMaximum }}</span>
                    </div>
                    <div class="mt-7 grid h-52 grid-cols-[repeat(14,minmax(0,1fr))] items-end gap-1.5 sm:gap-2" role="img" aria-label="Grafico visualizzazioni giornaliere degli ultimi 14 giorni">
                        @foreach ($trend as $day)
                            <div class="group flex h-full min-w-0 flex-col items-center justify-end gap-2" title="{{ $day['label'] }}: {{ $day['value'] }} visualizzazioni">
                                <span class="text-[10px] font-bold tabular-nums text-pm-text-secondary opacity-0 transition-opacity group-hover:opacity-100 group-focus-within:opacity-100">{{ $day['value'] }}</span>
                                <div class="flex h-36 w-full items-end overflow-hidden rounded-t-sm bg-white/[.025]">
                                    <div class="min-h-1 w-full rounded-t-sm bg-gradient-to-t from-[#A60D09] to-[#F23D37] transition-[height] duration-300 group-hover:from-[#CF160E] group-hover:to-[#FF716B]" style="height: {{ max(2, round(($day['value'] / $trendMaximum) * 100)) }}%"></div>
                                </div>
                                <span class="w-full truncate text-center font-mono text-[9px] tabular-nums text-pm-muted">{{ substr($day['label'], 0, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-3 border-t border-pm-border pt-3 text-[11px] leading-5 text-pm-muted">Le visualizzazioni vengono contate quando una pagina pubblica risponde correttamente; i crawler più comuni vengono esclusi.</p>
                </section>

                <section class="pm-panel min-w-0 p-5 sm:p-6" aria-labelledby="site-top-pages-title">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 id="site-top-pages-title" class="text-lg font-black tracking-tight text-pm-text">Pagine più viste</h2>
                            <p class="mt-1 text-xs text-pm-muted">Percorsi pubblici · 30 giorni</p>
                        </div>
                        <span class="rounded-lg border border-pm-border px-2 py-1 font-mono text-xs text-pm-muted">Top 8</span>
                    </div>
                    <div class="mt-5 space-y-4">
                        @forelse ($topPages as $page)
                            <div class="min-w-0">
                                <div class="mb-1.5 flex items-baseline justify-between gap-3">
                                    <span class="min-w-0 truncate font-mono text-xs text-pm-text-secondary" title="{{ $page['path'] }}">{{ $page['path'] }}</span>
                                    <span class="shrink-0 font-mono text-xs font-bold tabular-nums text-pm-text">{{ number_format($page['views'], 0, ',', '.') }}</span>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-pm-subtle">
                                    <div class="h-full rounded-full bg-[#E10600]" style="width: {{ $totals['page_view'] > 0 ? max(2, 100 * $page['views'] / $totals['page_view']) : 0 }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-pm-border bg-pm-subtle px-4 py-8 text-center">
                                <p class="font-semibold text-pm-text-secondary">Nessuna visita registrata</p>
                                <p class="mt-2 text-xs leading-5 text-pm-muted">La raccolta comincia dopo il deploy e l’esecuzione della migrazione.</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>

            <section class="pm-panel min-w-0 p-5 sm:p-7" aria-labelledby="site-diagnostics-title">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 id="site-diagnostics-title" class="text-xl font-black tracking-tight text-pm-text">Stato delle funzionalità</h2>
                            @if ($runs->isNotEmpty())
                                @php($lastRun = $runs->first())
                                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[.1em] {{ $lastRun->status === 'ok' ? 'bg-pm-success-subtle text-pm-success' : ($lastRun->status === 'warning' ? 'bg-pm-warning-subtle text-pm-warning' : 'bg-[#E10600]/15 text-[#FA7773]') }}">
                                    {{ $lastRun->status === 'ok' ? 'Operativo' : ($lastRun->status === 'warning' ? 'Da verificare' : 'Problemi rilevati') }}
                                </span>
                            @else
                                <span class="rounded-full border border-pm-border bg-pm-subtle px-2.5 py-1 text-[10px] font-bold uppercase tracking-[.1em] text-pm-muted">Mai testato</span>
                            @endif
                        </div>
                        <p class="mt-2 max-w-3xl text-xs leading-6 text-pm-text-secondary">Controlli tecnici di sola lettura su database, percorsi, moduli, configurazione, storage, PWA e ultima suite GitHub Actions. Non inviano email né modificano i dati.</p>
                    </div>
                    @if ($runs->isNotEmpty())
                        <span class="shrink-0 font-mono text-[11px] text-pm-muted">Ultima scansione {{ $lastRun->created_at->timezone('Europe/Rome')->format('d/m/Y · H:i') }}</span>
                    @endif
                </div>

                @if ($runs->isNotEmpty())
                    <div class="mt-5 grid gap-2 sm:grid-cols-3">
                        <div class="rounded-xl border border-pm-success/20 bg-pm-success-subtle p-3"><p class="font-mono text-2xl font-black text-pm-success">{{ $lastRun->passed }}</p><p class="mt-1 text-[11px] font-semibold text-pm-text-secondary">Controlli riusciti</p></div>
                        <div class="rounded-xl border border-pm-warning/20 bg-pm-warning-subtle p-3"><p class="font-mono text-2xl font-black text-pm-warning">{{ $lastRun->warnings }}</p><p class="mt-1 text-[11px] font-semibold text-pm-text-secondary">Avvisi</p></div>
                        <div class="rounded-xl border border-[#E10600]/20 bg-[#E10600]/[.07] p-3"><p class="font-mono text-2xl font-black text-[#FA7773]">{{ $lastRun->failed }}</p><p class="mt-1 text-[11px] font-semibold text-pm-text-secondary">Errori</p></div>
                    </div>

                    <div class="mt-5 grid gap-2 md:grid-cols-2">
                        @foreach ($lastRun->results as $check)
                            <article class="flex min-w-0 items-start gap-3 rounded-xl border border-pm-border bg-pm-subtle p-3.5">
                                <span class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg text-xs font-black {{ $check['status'] === 'ok' ? 'bg-pm-success-subtle text-pm-success' : ($check['status'] === 'warning' ? 'bg-pm-warning-subtle text-pm-warning' : 'bg-[#E10600]/15 text-[#FA7773]') }}" aria-label="{{ $check['status'] === 'ok' ? 'Riuscito' : ($check['status'] === 'warning' ? 'Avviso' : 'Errore') }}">{{ $check['status'] === 'ok' ? '✓' : ($check['status'] === 'warning' ? '!' : '×') }}</span>
                                <div class="min-w-0">
                                    <h3 class="text-xs font-bold text-pm-text">{{ $check['name'] }}</h3>
                                    <p class="mt-1 text-[11px] leading-5 text-pm-muted">{{ $check['detail'] }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="mt-5 rounded-xl border border-dashed border-pm-border bg-pm-subtle p-7 text-center sm:p-10">
                        <div class="mx-auto flex size-10 items-center justify-center rounded-xl border border-pm-border text-pm-text-secondary"><flux:icon.shield-check class="size-5" /></div>
                        <p class="mt-3 text-sm font-bold text-pm-text">Nessun report ancora disponibile</p>
                        <p class="mt-1 text-xs text-pm-muted">Premi “Esegui verifiche” per generare il primo report tecnico.</p>
                    </div>
                @endif
            </section>

            <section class="pm-panel p-5 sm:p-6" aria-labelledby="site-errors-title">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 id="site-errors-title" class="text-lg font-black tracking-tight text-pm-text">Report errori HTTP</h2>
                        <p class="mt-1 text-xs text-pm-muted">Risposte 5xx aggregate per funzione / percorso, ultimi 30 giorni</p>
                    </div>
                    <span class="rounded-full px-3 py-1.5 font-mono text-[10px] font-bold uppercase {{ $httpErrors->isEmpty() ? 'bg-pm-success-subtle text-pm-success' : 'bg-[#E10600]/15 text-[#FA7773]' }}">{{ $httpErrors->isEmpty() ? 'Nessun errore registrato' : $httpErrors->count().' percorsi coinvolti' }}</span>
                </div>
                @if ($httpErrors->isNotEmpty())
                    <div class="mt-4 grid gap-2 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($httpErrors as $error)
                            <div class="flex min-w-0 items-center justify-between gap-3 rounded-xl border border-[#E10600]/20 bg-[#E10600]/[.06] px-4 py-3">
                                <span class="min-w-0 truncate font-mono text-xs text-pm-text-secondary" title="{{ $error['route'] }}">{{ $error['route'] }}</span>
                                <span class="shrink-0 rounded-md bg-[#E10600]/15 px-2 py-1 font-mono text-xs font-bold text-[#FA7773]">{{ $error['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-4 rounded-xl border border-pm-border bg-pm-subtle px-4 py-4 text-xs leading-5 text-pm-text-secondary">Non risultano errori server registrati nel periodo. Se stai cercando un problema specifico, consulta anche i log di Laravel in Coolify.</p>
                @endif
            </section>

            <section class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,.8fr)]">
                <div class="pm-panel min-w-0 p-5 sm:p-6">
                    <h2 class="text-lg font-black tracking-tight text-pm-text">Storico report</h2>
                    <p class="mt-1 text-xs text-pm-muted">Ultime 8 scansioni, consultabili anche dopo una nuova verifica.</p>
                    <div class="mt-4 space-y-2">
                        @forelse ($runs as $run)
                            <details class="group rounded-xl border border-pm-border bg-pm-subtle">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 [&::-webkit-details-marker]:hidden">
                                    <span class="min-w-0"><span class="block text-xs font-bold text-pm-text">{{ $run->created_at->timezone('Europe/Rome')->format('d/m/Y · H:i:s') }}</span><span class="mt-0.5 block truncate text-[11px] text-pm-muted">{{ $run->author?->name ?? 'Sistema' }}</span></span>
                                    <span class="flex shrink-0 items-center gap-2"><span class="font-mono text-[10px] text-pm-text-secondary">{{ $run->passed }} OK · {{ $run->warnings }} avvisi · {{ $run->failed }} errori</span><span class="text-pm-muted transition-transform group-open:rotate-180">⌄</span></span>
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
                        <p><span class="font-bold text-pm-text">App:</span> i download APK forniti da GitHub sono riferiti alla release corrente, non ai soli ultimi 30 giorni. I clic APK indicano le visite al pulsante del sito; le aperture PWA non corrispondono alle installazioni effettive.</p>
                        <p><span class="font-bold text-pm-text">Diagnostica:</span> i controlli della dashboard verificano disponibilità e configurazione. I test funzionali e browser approfonditi vengono eseguiti nella pipeline CI, non sul database di produzione.</p>
                        <p><span class="font-bold text-pm-text">Errori:</span> le risposte HTTP 5xx sono aggregate per percorso; per analizzare stack trace e dettagli tecnici consulta i log di Coolify/Laravel.</p>
                    </div>
                    <a href="https://github.com/ButtiGit/pitmetric/actions/workflows/tests.yml" target="_blank" rel="noopener noreferrer" class="pm-ghost-button mt-6 inline-flex max-w-full items-center justify-center text-center">Apri i test completi su GitHub ↗</a>
                </aside>
            </section>
        </div>
    </div>
</x-layouts::app>
