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

