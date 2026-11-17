<div class="pm-panel flex flex-wrap items-center justify-between gap-3 p-5">
    <div><h3 class="text-sm font-bold text-pm-text">Visualizzazioni delle pagine pubbliche</h3><p class="mt-1 text-xs text-pm-muted">Ultimi 30 giorni · Le visualizzazioni non sono visitatori unici.</p></div>
    <p class="font-mono text-3xl font-black tabular-nums text-pm-text">{{ number_format($totals['page_view'], 0, ',', '.') }}</p>
</div>
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

