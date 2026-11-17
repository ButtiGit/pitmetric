            <section class="pm-panel min-w-0 p-5 sm:p-7" aria-labelledby="erp-diagnostics-title">
                @php($lastRun = $runs->first())
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 id="erp-diagnostics-title" class="text-xl font-black tracking-tight text-pm-text">Controlli del gestionale</h2>
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

