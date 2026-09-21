<x-layouts::app :title="__('Dashboard')">
    @php
        $it = app()->getLocale() === 'it';
        $nextUrl = route('events.index');
        $nextLabel = $it ? 'Esplora i weekend gara' : 'Explore race weekends';
        $nextCopy = $it
            ? 'Scopri come PitMetric collega mezzi, componenti, configurazioni, attività in pista e manutenzione.'
            : 'See how PitMetric connects vehicles, components, configurations, track activity and maintenance.';

        if ($databaseAccessEnabled && $domainReady && $workflowSnapshot !== null) {
            $workflowStage = $workflowSnapshot['next']['stage'];

            if ($maintenanceSummary['overdue'] > 0) {
                $nextUrl = route('maintenance.index');
                $nextLabel = $it ? 'Gestisci manutenzione scaduta' : 'Handle overdue maintenance';
                $nextCopy = $it
                    ? 'Hai almeno un intervento oltre il limite. La priorità operativa è risolverlo prima della prossima uscita.'
                    : 'At least one service is beyond its limit. Resolve it before the next outing.';
            } elseif ($workflowStage === 'vehicle') {
                $nextUrl = route('garage.index');
                $nextLabel = $it ? 'Aggiungi il primo mezzo' : 'Add your first vehicle';
                $nextCopy = $it
                    ? 'Parti dal mezzo reale che userai: tutto il resto del flusso tecnico verrà collegato a lui.'
                    : 'Start with the real vehicle you operate: the rest of the technical workflow will attach to it.';
            } elseif ($workflowStage === 'components') {
                $nextUrl = route('components.index');
                $nextLabel = $it ? 'Installa un componente' : 'Install a component';
                $nextCopy = $it
                    ? 'Registra almeno un componente fisicamente installato sul mezzo. Le configurazioni fotografano solo ciò che è davvero montato.'
                    : 'Record at least one component physically installed on the vehicle. Configurations only snapshot what is really mounted.';
            } elseif ($workflowStage === 'configuration') {
                $nextUrl = route('configurations.index');
                $nextLabel = $it ? 'Allinea la configurazione' : 'Align the configuration';
                $nextCopy = $it
                    ? 'La build fisica deve avere una versione di configurazione aggiornata prima di poter essere usata in pista.'
                    : 'The physical build needs an up-to-date configuration version before it can be used on track.';
            } elseif ($workflowStage === 'event') {
                if ($focusEvent) {
                    $nextUrl = route('events.show', $focusEvent);
                    $nextLabel = $focusEvent->status === 'active'
                        ? ($it ? 'Apri il weekend in corso' : 'Open active weekend')
                        : ($it ? 'Prepara il prossimo weekend' : 'Prepare next weekend');
                    $nextCopy = $it
                        ? 'La base tecnica è pronta. Continua il lavoro dentro '.$focusEvent->name.' per entry, sessioni, task, note e costi.'
                        : 'The technical base is ready. Continue inside '.$focusEvent->name.' for entries, sessions, tasks, notes and costs.';
                } else {
                    $nextUrl = route('events.index');
                    $nextLabel = $it ? 'Pianifica un race weekend' : 'Plan a race weekend';
                    $nextCopy = $it
                        ? 'La base tecnica è pronta. Crea il prossimo weekend e usalo come contenitore del lavoro in pista.'
                        : 'The technical base is ready. Create the next weekend and use it as the container for trackside work.';
                }
            } elseif ($workflowStage === 'session') {
                $nextUrl = route('sessions.index');
                $nextLabel = $it ? 'Registra la prima sessione' : 'Record the first session';
                $nextCopy = $it
                    ? 'Mezzo e build sono pronti. Registra una sessione reale per iniziare ad alimentare utilizzo e storico tecnico.'
                    : 'Vehicle and build are ready. Record a real session to start feeding usage and technical history.';
            } elseif ($workflowStage === 'usage') {
                $nextUrl = route('sessions.index');
                $nextLabel = $it ? 'Controlla l’utilizzo registrato' : 'Review recorded usage';
                $nextCopy = $it
                    ? 'Hai una sessione finalizzata: verifica che abbia prodotto utilizzo misurabile sui componenti tracciati.'
                    : 'You have a finalized session: verify that it produced measurable usage on tracked components.';
            } elseif ($workflowStage === 'maintenance') {
                $nextUrl = route('maintenance.index');
                $nextLabel = $it ? 'Imposta la manutenzione' : 'Set up maintenance';
                $nextCopy = $it
                    ? 'Ora che l’utilizzo reale viene propagato, crea almeno una scadenza di manutenzione per trasformarlo in azioni operative.'
                    : 'Now that real usage propagates, create at least one maintenance schedule to turn it into operational actions.';
            }
        }
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-3 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-7" data-demo-dashboard data-user="{{ auth()->id() }}" data-pm-mobile-dashboard data-pm-page-content>
        <div class="mx-auto w-full max-w-[1360px] space-y-4 sm:space-y-5">
            @if ($databaseAccessEnabled && ! $domainReady)
                <section class="pm-panel border-pm-warning/30 bg-pm-warning-subtle p-4 sm:p-5" role="status">
                    <div class="flex items-start gap-3">
                        <div class="grid size-9 shrink-0 place-items-center rounded-lg border border-pm-warning/30 bg-pm-warning-subtle text-sm font-black text-pm-warning">!</div>
                        <div>
                            <h2 class="font-bold text-pm-text">{{ $it ? 'Aggiornamento database richiesto' : 'Database update required' }}</h2>
                            <p class="mt-1 text-sm leading-6 text-pm-text-secondary">{{ $it ? 'Il tuo account ha accesso ai dati reali, ma le tabelle del core non sono ancora disponibili. Completa il deploy/migrazione prima di registrare attività.' : 'Your account has real database access, but the core tables are not available yet. Complete the deployment/migration before recording activity.' }}</p>
                        </div>
                    </div>
                </section>
            @elseif ($databaseAccessEnabled && $domainReady && ! $eventsReady)
                <section class="pm-panel border-pm-warning/30 bg-pm-warning-subtle p-4 sm:p-5" role="status">
                    <div class="flex items-start gap-3">
                        <div class="grid size-9 shrink-0 place-items-center rounded-lg border border-pm-warning/30 bg-pm-warning-subtle text-sm font-black text-pm-warning">!</div>
                        <div>
                            <h2 class="font-bold text-pm-text">{{ $it ? 'Modulo Race Weekend in aggiornamento' : 'Race Weekend module updating' }}</h2>
                            <p class="mt-1 text-sm leading-6 text-pm-text-secondary">{{ $it ? 'Il core operativo resta disponibile, ma le nuove tabelle Evento non sono ancora migrate.' : 'The operational core remains available, but the new Event tables are not migrated yet.' }}</p>
                        </div>
                    </div>
                </section>
            @endif

            <section class="pm-panel p-5 sm:p-8" data-pm-dashboard-next>
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-accent sm:text-xs sm:tracking-[0.14em]"><span class="pm-status-dot"></span>{{ $it ? 'Prossima azione operativa' : 'Next operational action' }}</div>
                        <h1 class="mt-3 text-2xl font-black tracking-[-0.035em] text-pm-text sm:mt-4 sm:text-4xl" data-pm-page-header>{{ $nextLabel }}</h1>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-pm-text-secondary sm:text-base sm:leading-7">
                            {{ $databaseAccessEnabled ? ($domainReady ? $nextCopy : ($it ? 'Il tuo account usa il database persistente PitMetric. Completa il deploy prima di continuare.' : 'Your account uses the persistent PitMetric database. Complete the deployment before continuing.')) : $nextCopy }}
                        </p>
                    </div>
                    <a href="{{ $nextUrl }}" class="pm-race-button w-full shrink-0 sm:w-auto" data-pm-primary-action>{{ $nextLabel }}</a>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" data-pm-dashboard-stats>
                <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Mezzi' : 'Vehicles' }}</p><p class="mt-3 text-3xl font-black text-pm-text sm:mt-4" @if (! $databaseAccessEnabled) data-demo-count="vehicles" @endif>{{ $vehicleCount }}</p></article>
                <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Configurazioni' : 'Configurations' }}</p><p class="mt-3 text-3xl font-black text-pm-text sm:mt-4" @if (! $databaseAccessEnabled) data-demo-count="configurations" @endif>{{ $configurationCount }}</p></article>
                <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Sessioni' : 'Sessions' }}</p><p class="mt-3 text-3xl font-black text-pm-text sm:mt-4" @if (! $databaseAccessEnabled) data-demo-count="sessions" @endif>{{ $sessionCount }}</p></article>
                <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Interventi' : 'Maintenance' }}</p><p class="mt-3 text-3xl font-black text-pm-text sm:mt-4" @if (! $databaseAccessEnabled) data-demo-count="maintenance" @endif>{{ $maintenanceCount }}</p></article>
            </section>

            @if ($databaseAccessEnabled && $eventsReady)
                <section class="pm-panel overflow-hidden" data-pm-dashboard-event>
                    @if ($focusEvent)
                        <div class="grid gap-5 p-5 sm:p-6 xl:grid-cols-[1fr_auto] xl:items-center">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-accent">{{ $focusEvent->status === 'active' ? ($it ? 'WEEKEND IN CORSO' : 'ACTIVE WEEKEND') : ($it ? 'PROSSIMO WEEKEND' : 'NEXT WEEKEND') }}</p>
                                    <x-pitmetric.status-badge :label="$focusEvent->status" :variant="$focusEvent->status === 'active' ? 'success' : 'warning'" />
                                </div>
                                <h2 class="mt-2 text-2xl font-black text-pm-text">{{ $focusEvent->name }}</h2>
                                <p class="mt-2 text-sm text-pm-text-secondary">{{ $focusEvent->start_date->format('d/m/Y') }} - {{ $focusEvent->end_date->format('d/m/Y') }} · {{ $focusEvent->circuitLayout?->circuit?->name }} · {{ $focusEvent->circuitLayout?->name }}</p>
                            </div>
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <div class="grid grid-cols-3 gap-2 text-center sm:min-w-[300px]">
                                    <div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-xl font-black text-pm-text">{{ $focusEvent->entries_count }}</p><p class="mt-1 text-[10px] uppercase text-pm-muted">Entries</p></div>
                                    <div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-xl font-black text-pm-text">{{ $focusEvent->sessions_count }}</p><p class="mt-1 text-[10px] uppercase text-pm-muted">Sessions</p></div>
                                    <div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-xl font-black text-pm-text">{{ $focusEvent->tasks_count }}</p><p class="mt-1 text-[10px] uppercase text-pm-muted">Tasks</p></div>
                                </div>
                                <a href="{{ route('events.show', $focusEvent) }}" class="pm-race-button justify-center">{{ $it ? 'Apri workspace' : 'Open workspace' }}</a>
                            </div>
                        </div>
                    @else
                        <div class="flex flex-col gap-5 p-5 sm:p-6 lg:flex-row lg:items-center lg:justify-between">
                            <div><p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-accent">RACE WEEKEND</p><h2 class="mt-2 text-xl font-black text-pm-text">{{ $it ? 'Il prossimo evento non è ancora pianificato' : 'The next event is not planned yet' }}</h2><p class="mt-2 text-sm text-pm-text-secondary">{{ $it ? 'Crea un weekend per raccogliere nello stesso posto entry, sessioni, setup, lavori, note e costi.' : 'Create a weekend to keep entries, sessions, setups, work, notes and costs in one place.' }}</p></div>
                            <a href="{{ route('events.index') }}" class="pm-race-button justify-center">{{ $it ? 'Pianifica weekend' : 'Plan weekend' }}</a>
                        </div>
                    @endif
                </section>
            @endif

            @if ($databaseAccessEnabled && $domainReady)
                <section class="grid gap-4 xl:grid-cols-3">
                    <article class="pm-panel p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Salute manutenzione' : 'Maintenance health' }}</p><h2 class="mt-2 text-lg font-black text-pm-text">{{ $maintenanceSummary['attention'] > 0 ? ($it ? 'Richiede attenzione' : 'Needs attention') : ($it ? 'Operativo' : 'Operational') }}</h2></div>
                            <x-pitmetric.status-badge :label="$maintenanceSummary['overdue'] > 0 ? ($it ? 'Scaduta' : 'Overdue') : ($maintenanceSummary['due_soon'] > 0 ? ($it ? 'In scadenza' : 'Due soon') : 'OK')" :variant="$maintenanceSummary['overdue'] > 0 ? 'danger' : ($maintenanceSummary['due_soon'] > 0 ? 'warning' : 'success')" />
                        </div>
                        <div class="mt-5 grid grid-cols-3 gap-2 text-center"><div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-2xl font-black text-pm-danger">{{ $maintenanceSummary['overdue'] }}</p><p class="mt-1 text-[10px] uppercase text-pm-muted">Overdue</p></div><div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-2xl font-black text-pm-warning">{{ $maintenanceSummary['due_soon'] }}</p><p class="mt-1 text-[10px] uppercase text-pm-muted">Due soon</p></div><div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-2xl font-black text-pm-success">{{ $maintenanceSummary['ok'] }}</p><p class="mt-1 text-[10px] uppercase text-pm-muted">OK</p></div></div>
                        <a href="{{ route('maintenance.index') }}" class="mt-4 inline-flex text-sm font-bold text-pm-accent hover:underline">{{ $it ? 'Apri manutenzione' : 'Open maintenance' }}</a>
                    </article>

                    <article class="pm-panel p-5 sm:p-6">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Spesa del mese' : 'This month spend' }}</p>
                        <p class="mt-3 text-3xl font-black text-pm-text">€ {{ number_format($monthExpenseCents / 100, 2, ',', '.') }}</p>
                        <p class="mt-2 text-sm leading-6 text-pm-text-secondary">{{ $it ? 'Include spese manuali e costi collegati automaticamente a componenti, sessioni e manutenzione.' : 'Includes manual costs plus component, session and maintenance costs linked automatically.' }}</p>
                        <a href="{{ route('expenses.index') }}" class="mt-4 inline-flex text-sm font-bold text-pm-accent hover:underline">{{ $it ? 'Analizza i costi' : 'Review costs' }}</a>
                    </article>

                    <article class="pm-panel p-5 sm:p-6">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Ultima sessione' : 'Last session' }}</p>
                        @if ($lastSession)
                            <h2 class="mt-3 text-lg font-black text-pm-text">{{ $lastSession->vehicle->name }}</h2>
                            <p class="mt-1 text-sm text-pm-text-secondary">{{ $lastSession->configurationVersion->configuration->name }} v{{ $lastSession->configurationVersion->version_number }} · {{ ucfirst($lastSession->session_type) }}</p>
                            <p class="mt-2 text-xs text-pm-muted">{{ $lastSession->started_at?->format('d/m/Y H:i') }}{{ $lastSession->circuitLayout ? ' · '.$lastSession->circuitLayout->circuit->name.' / '.$lastSession->circuitLayout->name : '' }}</p>
                            @if ($lastSession->raceEvent)
                                <a href="{{ route('events.show', $lastSession->raceEvent) }}" class="mt-4 inline-flex text-sm font-bold text-pm-accent hover:underline">{{ $it ? 'Apri il weekend' : 'Open weekend' }}</a>
                            @else
                                <a href="{{ route('sessions.index') }}" class="mt-4 inline-flex text-sm font-bold text-pm-accent hover:underline">{{ $it ? 'Vai alle sessioni' : 'Open sessions' }}</a>
                            @endif
                        @else
                            <h2 class="mt-3 text-lg font-black text-pm-text">{{ $it ? 'Ancora nessuna sessione' : 'No session yet' }}</h2>
                            <p class="mt-2 text-sm text-pm-text-secondary">{{ $it ? 'Quando registri la prima sessione vedrai qui l’ultima attività operativa.' : 'After the first session, the latest operational activity will appear here.' }}</p>
                            <a href="{{ route('events.index') }}" class="mt-4 inline-flex text-sm font-bold text-pm-accent hover:underline">{{ $it ? 'Apri i weekend' : 'Open weekends' }}</a>
                        @endif
                    </article>
                </section>

                <section class="pm-panel p-5 sm:p-6" data-pm-dashboard-workflow>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-accent">WORKFLOW</p>
                            <h2 class="mt-2 text-xl font-black text-pm-text">{{ $it ? 'Prontezza operativa reale' : 'Real operational readiness' }}</h2>
                        </div>
                        <p class="max-w-2xl text-sm text-pm-text-secondary">{{ $it ? 'Ogni fase diventa pronta solo quando PitMetric verifica davvero la dipendenza precedente.' : 'Each stage becomes ready only when PitMetric verifies the previous operational dependency.' }}</p>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-7">
                        @foreach ([
                            ['vehicle', $it ? '1. Mezzo' : '1. Vehicle', $it ? 'Base fisica del programma' : 'Physical program base'],
                            ['components', $it ? '2. Componenti' : '2. Components', $it ? 'Parti realmente installate' : 'Physically installed parts'],
                            ['configuration', $it ? '3. Configurazione' : '3. Configuration', $it ? 'Snapshot della build reale' : 'Snapshot of the real build'],
                            ['event', $it ? '4. Weekend' : '4. Weekend', $it ? 'Contenitore operativo' : 'Operational container'],
                            ['session', $it ? '5. Sessione' : '5. Session', $it ? 'Attività reale finalizzata' : 'Finalized real activity'],
                            ['usage', $it ? '6. Utilizzo' : '6. Usage', $it ? 'Propagato automaticamente' : 'Automatically propagated'],
                            ['maintenance', $it ? '7. Manutenzione' : '7. Maintenance', $it ? 'Intervalli e azioni tecniche' : 'Service intervals and actions'],
                        ] as [$key, $title, $copy])
                            @php
                                $done = $workflowSnapshot['steps'][$key]['complete'] ?? false;
                                $isNext = !($workflowSnapshot['complete'] ?? false) && ($workflowSnapshot['next']['stage'] ?? null) === $key;
                                $routeName = $workflowSnapshot['steps'][$key]['route'] ?? null;
                                $statusLabel = $done
                                    ? ($it ? 'Pronto' : 'Ready')
                                    : ($isNext ? ($it ? 'Prossimo' : 'Next') : ($key === 'usage' ? ($it ? 'Auto' : 'Auto') : ($it ? 'Manca' : 'Missing')));
                            @endphp

                            @if ($routeName)
                                <a href="{{ route($routeName) }}" class="rounded-xl border {{ $done ? 'border-pm-success/25 bg-pm-success-subtle' : ($isNext ? 'border-pm-accent/40 bg-pm-accent/5' : 'border-pm-border bg-pm-subtle') }} p-4 transition hover:border-pm-border-strong">
                                    <div class="flex items-center justify-between gap-3"><p class="font-bold text-pm-text">{{ $title }}</p><span class="text-[10px] font-black uppercase tracking-[0.1em] {{ $done ? 'text-pm-success' : ($isNext ? 'text-pm-accent' : 'text-pm-warning') }}">{{ $statusLabel }}</span></div>
                                    <p class="mt-2 text-xs text-pm-text-secondary">{{ $copy }}</p>
                                </a>
                            @else
                                <div class="rounded-xl border {{ $done ? 'border-pm-success/25 bg-pm-success-subtle' : ($isNext ? 'border-pm-accent/40 bg-pm-accent/5' : 'border-pm-border bg-pm-subtle') }} p-4">
                                    <div class="flex items-center justify-between gap-3"><p class="font-bold text-pm-text">{{ $title }}</p><span class="text-[10px] font-black uppercase tracking-[0.1em] {{ $done ? 'text-pm-success' : ($isNext ? 'text-pm-accent' : 'text-pm-muted') }}">{{ $statusLabel }}</span></div>
                                    <p class="mt-2 text-xs text-pm-text-secondary">{{ $copy }}</p>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['events.index', $it ? 'Weekend gara' : 'Race weekends', $it ? 'Piloti, mezzi, sessioni, lavori e costi' : 'Drivers, vehicles, sessions, work and costs'],
                    ['garage.index', 'Garage', $it ? 'Gestisci i tuoi mezzi' : 'Manage your vehicles'],
                    ['components.index', $it ? 'Componenti' : 'Components', $it ? 'Traccia utilizzo, acquisto e usura' : 'Track usage, purchase and wear'],
                    ['configurations.index', $it ? 'Configurazioni' : 'Configurations', $it ? 'Versiona build e componenti' : 'Version builds and components'],
                    ['sessions.index', $it ? 'Sessioni' : 'Sessions', $it ? 'Consulta anche le attività fuori evento' : 'Review standalone activity too'],
                    ['circuits.index', $it ? 'Circuiti' : 'Circuits', $it ? 'Gestisci circuiti e layout' : 'Manage circuits and layouts'],
                    ['maintenance.index', $it ? 'Manutenzione' : 'Maintenance', $it ? 'Intervalli, alert e storico' : 'Schedules, alerts and history'],
                    ['expenses.index', $it ? 'Spese' : 'Expenses', $it ? 'Controlla il costo reale' : 'Track real cost'],
                ] as [$routeName, $title, $subtitle])
                    <a href="{{ route($routeName) }}" class="group rounded-xl border border-pm-border bg-pm-surface p-4 transition hover:border-pm-border-strong hover:bg-pm-hover sm:p-5"><div class="flex items-center justify-between gap-3"><h2 class="min-w-0 font-bold text-pm-text">{{ $title }}</h2><span class="h-px w-5 shrink-0 bg-pm-border-strong transition-all group-hover:w-8 group-hover:bg-pm-accent"></span></div><p class="mt-2 text-sm text-pm-text-secondary">{{ $subtitle }}</p></a>
                @endforeach
            </section>
        </div>
    </div>
</x-layouts::app>
