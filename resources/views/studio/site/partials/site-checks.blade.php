            <section class="pm-panel min-w-0 p-5 sm:p-7" aria-labelledby="site-diagnostics-title">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 id="site-diagnostics-title" class="text-xl font-black tracking-tight text-pm-text">Controlli del sito</h2>
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
                                <span class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg text-[10px] font-black {{ $check['status'] === 'ok' ? 'bg-pm-success-subtle text-pm-success' : ($check['status'] === 'warning' ? 'bg-pm-warning-subtle text-pm-warning' : 'bg-[#E10600]/15 text-[#FA7773]') }}" aria-label="{{ $check['status'] === 'ok' ? 'Riuscito' : ($check['status'] === 'warning' ? 'Avviso' : 'Errore') }}">{{ $check['status'] === 'ok' ? 'OK' : ($check['status'] === 'warning' ? '!' : 'X') }}</span>
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
                        <p class="mt-1 text-xs text-pm-muted">Premi “Verifica sito” per generare il primo report tecnico.</p>
                    </div>
                @endif
            </section>

