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

