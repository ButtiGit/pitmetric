<x-layouts::app title="Controlli e analisi">
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 pb-32 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1400px] space-y-6">
            @if (session('status'))
                <div role="status" class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>
            @endif

            <header class="pm-panel p-5 sm:p-7">
                <p class="font-mono text-[10px] font-bold uppercase tracking-[.14em] text-pm-muted">Studio / Solo editor</p>
                <h1 class="mt-3 text-3xl font-black tracking-tight text-pm-text sm:text-4xl">Controlli e analisi<span class="text-[#FF5A36]">.</span></h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-pm-text-secondary">Un solo punto per verificare sito e gestionale, consultare traffico e app e capire come viene utilizzato PitMetric.</p>
                <nav aria-label="Sezioni controlli e analisi" class="mt-5 grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                    @foreach (['panoramica' => 'Panoramica', 'controlli' => 'Controlli', 'traffico' => 'Traffico', 'app' => 'App e PWA', 'gestionale' => 'Utilizzo gestionale', 'storico' => 'Storico report'] as $anchor => $label)
                        <a href="#{{ $anchor }}" class="pm-ghost-button justify-center text-center text-xs">{{ $label }}</a>
                    @endforeach
                </nav>
            </header>

            <section id="panoramica" aria-labelledby="overview-title" class="scroll-mt-6 space-y-3">
                <h2 id="overview-title" class="text-xl font-black text-pm-text">Stato della piattaforma</h2>
                <p class="text-xs leading-5 text-pm-muted">Gli stati si riferiscono all'ultimo controllo salvato. Data e ora indicano quando sono stati verificati.</p>
                <div class="grid gap-3 md:grid-cols-2">
                    @foreach ([['name' => 'Sito pubblico e app', 'ready' => $siteReady, 'run' => $runs->first(), 'action' => 'studio.site.checks', 'button' => 'Verifica sito'], ['name' => 'Gestionale e integrità dati', 'ready' => $erpReady, 'run' => $erpRuns->first(), 'action' => 'studio.erp.checks', 'button' => 'Verifica gestionale']] as $area)
                        @php
                            $report = $area['run'];
                            $state = ! $area['ready'] ? 'Configurazione richiesta' : (! $report ? 'Non verificato' : match ($report->status) {
                                'ok' => 'Operativo',
                                'warning' => 'Da verificare',
                                default => 'Problemi rilevati',
                            });
                        @endphp
                        <article class="pm-panel min-w-0 space-y-4 p-5">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <h3 class="text-sm font-black text-pm-text">{{ $area['name'] }}</h3>
                                <span class="rounded-full border border-pm-border bg-pm-subtle px-3 py-1 text-xs font-bold {{ ! $area['ready'] || $report?->status === 'warning' ? 'text-pm-warning' : ($report?->status === 'error' ? 'text-[#FA7773]' : ($report ? 'text-pm-success' : 'text-pm-muted')) }}">{{ $state }}</span>
                            </div>
                            @if ($report)
                                <p class="text-xs leading-6 text-pm-text-secondary">{{ $report->passed }} superati · {{ $report->warnings }} avvisi · {{ $report->failed }} errori<br>Ultimo controllo: {{ $report->created_at->timezone('Europe/Rome')->format('d/m/Y · H:i') }}</p>
                            @else
                                <p class="text-xs leading-6 text-pm-muted">{{ $area['ready'] ? 'Esegui il primo controllo per conoscere lo stato di questa area.' : 'Applica le migrazioni Laravel per attivare i report di questa area.' }}</p>
                            @endif
                            <form method="POST" action="{{ route($area['action']) }}" x-data="{ running: false }" x-on:submit="running = true">
                                @csrf
                                <button type="submit" @disabled(! $area['ready']) x-bind:disabled="running || {{ $area['ready'] ? 'false' : 'true' }}" class="pm-race-button w-full justify-center disabled:cursor-not-allowed disabled:opacity-50">
                                    <span x-show="! running">{{ $area['button'] }}</span>
                                    <span x-show="running" x-cloak role="status">Verifica in corso…</span>
                                </button>
                            </form>
                        </article>
                    @endforeach
                </div>
            </section>

            <section id="controlli" aria-labelledby="checks-title" class="scroll-mt-6 space-y-4">
                <h2 id="checks-title" class="text-xl font-black text-pm-text">Controlli e problemi da verificare</h2>
                @if ($siteReady)
                    @include('studio.site.partials.errors')
                    <details class="group pm-panel p-4 sm:p-5">
                        <summary class="cursor-pointer text-sm font-bold text-pm-text">Report dettagliato del sito</summary>
                        <div class="mt-4">@include('studio.site.partials.site-checks')</div>
                    </details>
                @else
                    <p class="pm-panel p-5 text-sm text-pm-warning">Monitoraggio sito in attesa delle migrazioni. Le altre aree restano consultabili.</p>
                @endif
                @if ($erpReady)
                    <details class="group pm-panel p-4 sm:p-5">
                        <summary class="cursor-pointer text-sm font-bold text-pm-text">Report dettagliato del gestionale</summary>
                        <div class="mt-4">@include('studio.site.partials.erp-checks', ['runs' => $erpRuns])</div>
                    </details>
                @else
                    <p class="pm-panel p-5 text-sm text-pm-warning">Diagnostica ERP in attesa delle migrazioni. Le analisi di utilizzo restano consultabili.</p>
                @endif
            </section>

            <section id="traffico" aria-labelledby="traffic-title" class="scroll-mt-6 space-y-4">
                <h2 id="traffic-title" class="text-xl font-black text-pm-text">Traffico del sito</h2>
                @if ($siteReady)
                    @include('studio.site.partials.traffic')
                @else
                    <p class="pm-panel p-5 text-sm text-pm-muted">Statistiche disponibili dopo l'attivazione del monitoraggio.</p>
                @endif
            </section>

            <section id="app" aria-label="App e PWA" class="scroll-mt-6 space-y-4">
                @include('studio.site.partials.apps')
            </section>

            <section id="gestionale" aria-label="Utilizzo gestionale" class="scroll-mt-6 space-y-4">
                @include('studio.site.partials.erp-usage', ['totals' => $erpTotals])
                <a class="pm-ghost-button inline-flex" href="{{ route('studio.users.index') }}">Utenti, attività e ultimi accessi</a>
            </section>

            <section id="storico" aria-labelledby="history-title" class="scroll-mt-6 space-y-4">
                <h2 id="history-title" class="text-xl font-black text-pm-text">Storico e lettura dei dati</h2>
                <details class="pm-panel p-4 sm:p-5">
                    <summary class="cursor-pointer text-sm font-bold text-pm-text">Report sito e significato delle statistiche</summary>
                    <div class="mt-4">@include('studio.site.partials.site-history')</div>
                </details>
                <details class="pm-panel p-4 sm:p-5">
                    <summary class="cursor-pointer text-sm font-bold text-pm-text">Report gestionale e copertura dei controlli</summary>
                    <div class="mt-4">@include('studio.site.partials.erp-history', ['runs' => $erpRuns])</div>
                </details>
            </section>
        </div>
    </div>
</x-layouts::app>
