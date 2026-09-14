<x-layouts::app :title="__('Dashboard')">
    @php
        $it = app()->getLocale() === 'it';
        $nextRoute = 'demo.sessions';
        $nextLabel = $it ? 'Registra sessione' : 'Record session';
        $nextCopy = $it ? 'La base operativa è pronta. Registra la prossima attività in pista.' : 'Your operational base is ready. Record the next track activity.';

        if ($databaseAccessEnabled && $domainReady) {
            if ($vehicleCount === 0) {
                $nextRoute = 'demo.garage';
                $nextLabel = $it ? 'Aggiungi il primo mezzo' : 'Add your first vehicle';
                $nextCopy = $it ? 'Il flusso parte dal mezzo su cui installerai componenti e configurazioni.' : 'The workflow starts with the vehicle that will receive components and configurations.';
            } elseif ($componentCount === 0) {
                $nextRoute = 'demo.components';
                $nextLabel = $it ? 'Aggiungi componenti' : 'Add components';
                $nextCopy = $it ? 'Inserisci almeno un componente tracciato prima di costruire una configurazione reale.' : 'Add at least one tracked component before building a real configuration.';
            } elseif ($configurationCount === 0) {
                $nextRoute = 'demo.configurations';
                $nextLabel = $it ? 'Crea configurazione' : 'Create configuration';
                $nextCopy = $it ? 'Collega componenti e mezzo in una configurazione versionata.' : 'Connect components and vehicle in a versioned configuration.';
            } elseif ($maintenanceSummary['overdue'] > 0) {
                $nextRoute = 'demo.maintenance';
                $nextLabel = $it ? 'Gestisci manutenzione scaduta' : 'Handle overdue maintenance';
                $nextCopy = $it ? 'Hai almeno un intervento oltre il limite. Risolvilo prima della prossima uscita.' : 'At least one service is beyond its limit. Resolve it before the next outing.';
            }
        }
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-3 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-7" data-demo-dashboard data-user="{{ auth()->id() }}" data-pm-mobile-dashboard>
        <div class="mx-auto w-full max-w-[1360px] space-y-4 sm:space-y-5">
            @if ($databaseAccessEnabled && ! $domainReady)
                <section class="pm-panel border-pm-warning/30 bg-pm-warning-subtle p-4 sm:p-5" role="status">
                    <div class="flex items-start gap-3">
                        <div class="grid size-9 shrink-0 place-items-center rounded-lg border border-pm-warning/30 bg-pm-warning-subtle text-sm font-black text-pm-warning">!</div>
                        <div>
                            <h2 class="font-bold text-pm-text">{{ $it ? 'Aggiornamento database richiesto' : 'Database update required' }}</h2>
                            <p class="mt-1 text-sm leading-6 text-pm-text-secondary">{{ $it ? 'Il tuo account ha accesso ai dati reali, ma le nuove tabelle del core non sono ancora disponibili. Completa il deploy/migrazione prima di registrare sessioni.' : 'Your account has real database access, but the new core tables are not available yet. Complete the deployment/migration before recording sessions.' }}</p>
                        </div>
                    </div>
                </section>
            @endif

            <section class="pm-panel p-5 sm:p-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-accent sm:text-xs sm:tracking-[0.14em]"><span class="pm-status-dot"></span>{{ $it ? 'Gestionale PitMetric' : 'PitMetric manager' }}</div>
                        <h1 class="mt-3 text-2xl font-black tracking-[-0.035em] text-pm-text sm:mt-4 sm:text-4xl">{{ $it ? 'Il prossimo passo è già chiaro.' : 'Your next step is already clear.' }}</h1>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-pm-text-secondary sm:text-base sm:leading-7">{{ $databaseAccessEnabled ? ($domainReady ? $nextCopy : ($it ? 'Il tuo account usa il database persistente PitMetric. Completa il deploy prima di continuare.' : 'Your account uses the persistent PitMetric database. Complete the deployment before continuing.')) : ($it ? 'Questa è la modalità locale di prova: i dati restano solo su questo browser finché l’account non viene attivato.' : 'This is local trial mode: data stays only in this browser until the account is activated.') }}</p>
                    </div>
                    <a href="{{ route($nextRoute) }}" class="pm-race-button w-full shrink-0 sm:w-auto">{{ $nextLabel }}</a>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Mezzi' : 'Vehicles' }}</p><p class="mt-3 text-3xl font-black text-pm-text sm:mt-4" @if (! $databaseAccessEnabled) data-demo-count="vehicles" @endif>{{ $vehicleCount }}</p></article>
                <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Configurazioni' : 'Configurations' }}</p><p class="mt-3 text-3xl font-black text-pm-text sm:mt-4" @if (! $databaseAccessEnabled) data-demo-count="configurations" @endif>{{ $configurationCount }}</p></article>
                <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Sessioni' : 'Sessions' }}</p><p class="mt-3 text-3xl font-black text-pm-text sm:mt-4" @if (! $databaseAccessEnabled) data-demo-count="sessions" @endif>{{ $sessionCount }}</p></article>
                <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Interventi' : 'Maintenance' }}</p><p class="mt-3 text-3xl font-black text-pm-text sm:mt-4" @if (! $databaseAccessEnabled) data-demo-count="maintenance" @endif>{{ $maintenanceCount }}</p></article>
            </section>

            @if ($databaseAccessEnabled && $domainReady)
                <section class="grid gap-4 xl:grid-cols-3">
                    <article class="pm-panel p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Salute manutenzione' : 'Maintenance health' }}</p><h2 class="mt-2 text-lg font-black text-pm-text">{{ $maintenanceSummary['attention'] > 0 ? ($it ? 'Richiede attenzione' : 'Needs attention') : ($it ? 'Operativo' : 'Operational') }}</h2></div>
                            <x-pitmetric.status-badge :label="$maintenanceSummary['overdue'] > 0 ? ($it ? 'Scaduta' : 'Overdue') : ($maintenanceSummary['due_soon'] > 0 ? ($it ? 'In scadenza' : 'Due soon') : 'OK')" :variant="$maintenanceSummary['overdue'] > 0 ? 'danger' : ($maintenanceSummary['due_soon'] > 0 ? 'warning' : 'success')" />
                        </div>
                        <div class="mt-5 grid grid-cols-3 gap-2 text-center"><div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-2xl font-black text-pm-danger">{{ $maintenanceSummary['overdue'] }}</p><p class="mt-1 text-[10px] uppercase text-pm-muted">Overdue</p></div><div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-2xl font-black text-pm-warning">{{ $maintenanceSummary['due_soon'] }}</p><p class="mt-1 text-[10px] uppercase text-pm-muted">Due soon</p></div><div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-2xl font-black text-pm-success">{{ $maintenanceSummary['ok'] }}</p><p class="mt-1 text-[10px] uppercase text-pm-muted">OK</p></div></div>
                        <a href="{{ route('demo.maintenance') }}" class="mt-4 inline-flex text-sm font-bold text-pm-accent hover:underline">{{ $it ? 'Apri manutenzione' : 'Open maintenance' }}</a>
                    </article>

                    <article class="pm-panel p-5 sm:p-6">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Spesa del mese' : 'This month spend' }}</p>
                        <p class="mt-3 text-3xl font-black text-pm-text">€ {{ number_format($monthExpenseCents / 100, 2, ',', '.') }}</p>
                        <p class="mt-2 text-sm leading-6 text-pm-text-secondary">{{ $it ? 'Include spese manuali e costi collegati automaticamente a componenti, sessioni e manutenzione.' : 'Includes manual costs plus component, session and maintenance costs linked automatically.' }}</p>
                        <a href="{{ route('demo.expenses') }}" class="mt-4 inline-flex text-sm font-bold text-pm-accent hover:underline">{{ $it ? 'Analizza i costi' : 'Review costs' }}</a>
                    </article>

                    <article class="pm-panel p-5 sm:p-6">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Ultima sessione' : 'Last session' }}</p>
                        @if ($lastSession)
                            <h2 class="mt-3 text-lg font-black text-pm-text">{{ $lastSession->vehicle->name }}</h2>
                            <p class="mt-1 text-sm text-pm-text-secondary">{{ $lastSession->configurationVersion->configuration->name }} v{{ $lastSession->configurationVersion->version_number }} · {{ ucfirst($lastSession->session_type) }}</p>
                            <p class="mt-2 text-xs text-pm-muted">{{ $lastSession->started_at?->format('d/m/Y H:i') }}@if ($lastSession->circuitLayout) · {{ $lastSession->circuitLayout->circuit->name }} / {{ $lastSession->circuitLayout->name }}@endif</p>
                        @else
                            <h2 class="mt-3 text-lg font-black text-pm-text">{{ $it ? 'Ancora nessuna sessione' : 'No session yet' }}</h2>
                            <p class="mt-2 text-sm text-pm-text-secondary">{{ $it ? 'Quando registri la prima sessione vedrai qui l’ultima attività operativa.' : 'After the first session, the latest operational activity will appear here.' }}</p>
                        @endif
                        <a href="{{ route('demo.sessions') }}" class="mt-4 inline-flex text-sm font-bold text-pm-accent hover:underline">{{ $it ? 'Vai alle sessioni' : 'Open sessions' }}</a>
                    </article>
                </section>

                <section class="pm-panel p-5 sm:p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-accent">WORKFLOW</p><h2 class="mt-2 text-xl font-black text-pm-text">{{ $it ? 'Prontezza operativa' : 'Operational readiness' }}</h2></div><p class="text-sm text-pm-text-secondary">{{ $it ? 'Completa la catena una volta, poi il lavoro quotidiano diventa sessione → alert → service → costi.' : 'Complete the chain once, then daily work becomes session → alerts → service → costs.' }}</p></div>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ([
                            [$vehicleCount > 0, 'demo.garage', $it ? '1. Mezzo' : '1. Vehicle', $it ? 'Base del programma' : 'Program base'],
                            [$componentCount > 0, 'demo.components', $it ? '2. Componenti' : '2. Components', $it ? 'Parti con utilizzo tracciato' : 'Usage-tracked parts'],
                            [$configurationCount > 0, 'demo.configurations', $it ? '3. Configurazione' : '3. Configuration', $it ? 'Build versionata' : 'Versioned build'],
                            [$sessionCount > 0, 'demo.sessions', $it ? '4. Sessione' : '4. Session', $it ? 'Uso reale propagato' : 'Real usage propagated'],
                        ] as [$done, $routeName, $title, $copy])
                            <a href="{{ route($routeName) }}" class="rounded-xl border {{ $done ? 'border-pm-success/25 bg-pm-success-subtle' : 'border-pm-border bg-pm-subtle' }} p-4 transition hover:border-pm-border-strong"><div class="flex items-center justify-between gap-3"><p class="font-bold text-pm-text">{{ $title }}</p><span class="text-[10px] font-black uppercase tracking-[0.1em] {{ $done ? 'text-pm-success' : 'text-pm-warning' }}">{{ $done ? ($it ? 'Pronto' : 'Ready') : ($it ? 'Manca' : 'Missing') }}</span></div><p class="mt-2 text-xs text-pm-text-secondary">{{ $copy }}</p></a>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                @foreach ([['demo.garage','Garage',$it?'Gestisci i tuoi mezzi':'Manage your vehicles'],['demo.components',$it?'Componenti':'Components',$it?'Traccia utilizzo, acquisto e usura':'Track usage, purchase and wear'],['demo.configurations',$it?'Configurazioni':'Configurations',$it?'Versiona build e componenti':'Version builds and components'],['demo.sessions',$it?'Sessioni':'Sessions',$it?'Registra attività, utilizzo e costi':'Record activity, usage and costs'],['demo.circuits',$it?'Circuiti':'Circuits',$it?'Gestisci circuiti e layout':'Manage circuits and layouts'],['demo.maintenance',$it?'Manutenzione':'Maintenance',$it?'Intervalli, alert e storico':'Schedules, alerts and history'],['demo.expenses',$it?'Spese':'Expenses',$it?'Controlla il costo reale':'Track real cost'],['newsletter.edit','Newsletter',$it?'Gestisci le preferenze email':'Manage email preferences']] as [$routeName,$title,$subtitle])
                    <a href="{{ route($routeName) }}" class="group rounded-xl border border-pm-border bg-pm-surface p-4 transition hover:border-pm-border-strong hover:bg-pm-hover sm:p-5"><div class="flex items-center justify-between gap-3"><h2 class="min-w-0 font-bold text-pm-text">{{ $title }}</h2><span class="h-px w-5 shrink-0 bg-pm-border-strong transition-all group-hover:w-8 group-hover:bg-pm-accent"></span></div><p class="mt-2 text-sm text-pm-text-secondary">{{ $subtitle }}</p></a>
                @endforeach
            </section>
        </div>
    </div>
</x-layouts::app>
