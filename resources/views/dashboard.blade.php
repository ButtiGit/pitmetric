<x-layouts::app :title="__('Dashboard')">
    @php($it = app()->getLocale() === 'it')
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-3 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-7" data-demo-dashboard data-user="{{ auth()->id() }}" data-pm-mobile-dashboard>
        <div class="mx-auto w-full max-w-[1360px] space-y-4 sm:space-y-5">
            @if (! $domainReady)
                <section class="pm-panel border-pm-warning/30 bg-pm-warning-subtle p-4 sm:p-5" role="status">
                    <div class="flex items-start gap-3">
                        <div class="grid size-9 shrink-0 place-items-center rounded-lg border border-pm-warning/30 bg-pm-warning-subtle text-sm font-black text-pm-warning">!</div>
                        <div class="min-w-0">
                            <h2 class="font-bold text-pm-text">{{ $it ? 'Aggiornamento in corso' : 'Update in progress' }}</h2>
                            <p class="mt-1 text-sm leading-6 text-pm-text-secondary">{{ $it ? 'Alcune funzioni del Garage sono temporaneamente non disponibili. Riprova tra poco dopo l’aggiornamento dell’applicazione.' : 'Some Garage features are temporarily unavailable. Try again after the application update is complete.' }}</p>
                        </div>
                    </div>
                </section>
            @endif

            <section class="pm-panel p-5 sm:p-8">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-accent sm:text-xs sm:tracking-[0.14em]"><span class="pm-status-dot"></span>{{ $it ? 'Gestionale PitMetric' : 'PitMetric manager' }}</div>
                        <h1 class="mt-3 text-2xl font-black tracking-[-0.035em] text-pm-text sm:mt-4 sm:text-4xl">{{ $it ? 'Tutto il tuo motorsport in un unico posto.' : 'Your motorsport work, all in one place.' }}</h1>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-pm-text-secondary sm:text-base sm:leading-7">{{ $it ? 'Organizza mezzi, componenti, configurazioni, circuiti, sessioni, manutenzione e costi con un flusso pensato per tenere ordine tra pista e officina.' : 'Organize vehicles, components, configurations, circuits, sessions, maintenance and costs with a workflow built for both track and workshop.' }}</p>
                    </div>
                    <a href="{{ route('demo.garage') }}" class="pm-race-button w-full shrink-0 sm:w-auto">{{ $it ? 'Apri il Garage' : 'Open Garage' }}</a>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Mezzi' : 'Vehicles' }}</p><p class="mt-3 text-3xl font-black text-pm-text sm:mt-4">{{ $vehicleCount }}</p></article>
                @foreach ([['configurations',$it?'Configurazioni':'Configurations'],['sessions',$it?'Sessioni':'Sessions'],['maintenance',$it?'Interventi':'Maintenance']] as [$key,$label])
                    <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $label }}</p><p class="mt-3 text-3xl font-black text-pm-text sm:mt-4" data-demo-count="{{ $key }}">0</p></article>
                @endforeach
            </section>

            <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['demo.garage','Garage',$it?'Gestisci i tuoi mezzi':'Manage your vehicles'],
                    ['demo.components',$it?'Componenti':'Components',$it?'Traccia utilizzo e usura':'Track usage and wear'],
                    ['demo.configurations',$it?'Configurazioni':'Configurations',$it?'Organizza build e componenti':'Organize builds and components'],
                    ['demo.sessions',$it?'Sessioni':'Sessions',$it?'Registra attività in pista':'Record track activity'],
                    ['demo.circuits',$it?'Circuiti':'Circuits',$it?'Gestisci circuiti e layout':'Manage circuits and layouts'],
                    ['demo.maintenance',$it?'Manutenzione':'Maintenance',$it?'Tieni lo storico interventi':'Keep service history'],
                    ['demo.expenses',$it?'Spese':'Expenses',$it?'Controlla i costi':'Track costs'],
                    ['newsletter.edit','Newsletter',$it?'Gestisci le preferenze email':'Manage email preferences'],
                ] as [$routeName,$title,$subtitle])
                    <a href="{{ route($routeName) }}" class="group rounded-xl border border-pm-border bg-pm-surface p-4 transition hover:border-pm-border-strong hover:bg-pm-hover sm:p-5"><div class="flex items-center justify-between gap-3"><h2 class="min-w-0 font-bold text-pm-text">{{ $title }}</h2><span class="shrink-0 text-pm-muted transition group-hover:translate-x-0.5 group-hover:text-pm-accent">→</span></div><p class="mt-2 text-sm text-pm-text-secondary">{{ $subtitle }}</p></a>
                @endforeach
            </section>

            <section class="pm-panel p-4 sm:p-6"><div class="flex items-start gap-3"><div class="grid size-9 shrink-0 place-items-center rounded-lg border border-pm-border bg-pm-success-subtle text-sm text-pm-success">✓</div><div class="min-w-0"><h2 class="font-bold text-pm-text">{{ $it ? 'Pronto per la prossima sessione' : 'Ready for the next session' }}</h2><p class="mt-1 text-sm leading-6 text-pm-text-secondary">{{ $it ? 'Usa le sezioni del gestionale per preparare il mezzo, registrare il lavoro svolto e mantenere sotto controllo utilizzo e costi.' : 'Use the manager sections to prepare the vehicle, record completed work and keep usage and costs under control.' }}</p></div></div></section>
        </div>
    </div>
</x-layouts::app>
