<x-layouts::app :title="__('Dashboard')">
    @php($it = app()->getLocale() === 'it')
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-3 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-7" data-demo-dashboard data-user="{{ auth()->id() }}" data-pm-mobile-dashboard>
        <div class="mx-auto w-full max-w-[1360px] space-y-4 sm:space-y-5">
            @if (! $domainReady)
                <section class="pm-panel border-pm-warning/30 bg-pm-warning-subtle p-4 sm:p-5" role="status">
                    <div class="flex items-start gap-3">
                        <div class="grid size-9 shrink-0 place-items-center rounded-lg border border-pm-warning/30 bg-pm-warning-subtle text-sm font-black text-pm-warning">!</div>
                        <div class="min-w-0">
                            <h2 class="font-bold text-pm-text">{{ $it ? 'Aggiornamento dati in corso' : 'Data update in progress' }}</h2>
                            <p class="mt-1 text-sm leading-6 text-pm-text-secondary">{{ $it ? 'Il gestionale è raggiungibile, ma il Garage non è ancora pronto su questo deploy. Nessun dato è stato perso: completa l’aggiornamento del database sul server e poi ricarica la pagina.' : 'The manager is reachable, but Garage is not ready on this deployment yet. No data has been lost: complete the server database update and reload the page.' }}</p>
                        </div>
                    </div>
                </section>
            @endif

            <section class="pm-panel p-5 sm:p-8">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-accent sm:text-xs sm:tracking-[0.14em]"><span class="pm-status-dot"></span>{{ $domainReady ? ($it ? 'Garage reale · resto demo' : 'Real garage · remaining demo') : ($it ? 'Gestionale online · dati in aggiornamento' : 'Manager online · data updating') }}</div>
                        <h1 class="mt-3 text-2xl font-black tracking-[-0.035em] text-pm-text sm:mt-4 sm:text-4xl">{{ $it ? 'PitMetric sta diventando un prodotto reale.' : 'PitMetric is becoming a real product.' }}</h1>
                        <p class="mt-3 max-w-3xl text-sm leading-6 text-pm-text-secondary sm:text-base sm:leading-7">{{ $it ? 'I mezzi del Garage ora vengono salvati nel database e isolati nel tuo workspace personale. Componenti, configurazioni, circuiti, sessioni, manutenzione e spese restano temporaneamente nella demo locale finché non li colleghiamo al nuovo dominio.' : 'Garage vehicles are now stored in the database and isolated inside your personal workspace. Components, configurations, circuits, sessions, maintenance and expenses remain in the local demo until they are connected to the new domain.' }}</p>
                    </div>
                    <a href="{{ route('demo.garage') }}" class="pm-race-button w-full shrink-0 sm:w-auto">{{ $it ? 'Apri il garage reale' : 'Open the real garage' }}</a>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Mezzi reali' : 'Real vehicles' }}</p><p class="mt-3 text-3xl font-black text-pm-text sm:mt-4">{{ $vehicleCount }}</p></article>
                @foreach ([['configurations',$it?'Configurazioni demo':'Demo configurations'],['sessions',$it?'Sessioni demo':'Demo sessions'],['maintenance',$it?'Interventi demo':'Demo maintenance']] as [$key,$label])
                    <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $label }}</p><p class="mt-3 text-3xl font-black text-pm-text sm:mt-4" data-demo-count="{{ $key }}">0</p></article>
                @endforeach
            </section>

            <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['demo.garage','Garage',$it?'Dati server · workspace':'Server data · workspace'],
                    ['demo.components',$it?'Componenti':'Components',$it?'Demo locale':'Local demo'],
                    ['demo.configurations',$it?'Configurazioni':'Configurations',$it?'Demo locale':'Local demo'],
                    ['demo.sessions',$it?'Sessioni':'Sessions',$it?'Demo locale':'Local demo'],
                    ['demo.circuits',$it?'Circuiti':'Circuits',$it?'Demo locale':'Local demo'],
                    ['demo.maintenance',$it?'Manutenzione':'Maintenance',$it?'Demo locale':'Local demo'],
                    ['demo.expenses',$it?'Spese':'Expenses',$it?'Demo locale':'Local demo'],
                    ['newsletter.edit','Newsletter',$it?'Preferenza email':'Email preference'],
                ] as [$routeName,$title,$subtitle])
                    <a href="{{ route($routeName) }}" class="group rounded-xl border border-pm-border bg-pm-surface p-4 transition hover:border-pm-border-strong hover:bg-pm-hover sm:p-5"><div class="flex items-center justify-between gap-3"><h2 class="min-w-0 font-bold text-pm-text">{{ $title }}</h2><span class="shrink-0 text-pm-muted transition group-hover:translate-x-0.5 group-hover:text-pm-accent">→</span></div><p class="mt-2 text-sm text-pm-text-secondary">{{ $subtitle }}</p></a>
                @endforeach
            </section>

            <section class="pm-panel p-4 sm:p-6"><div class="flex items-start gap-3"><div class="grid size-9 shrink-0 place-items-center rounded-lg border border-pm-border bg-pm-success-subtle text-sm text-pm-success">✓</div><div class="min-w-0"><h2 class="font-bold text-pm-text">{{ $it ? 'Prima vertical slice completata' : 'First vertical slice completed' }}</h2><p class="mt-1 text-sm leading-6 text-pm-text-secondary">{{ $it ? 'Workspace e Garage sono ora persistenti. Il prossimo modulo da portare sul database è Componenti.' : 'Workspace and Garage are now persistent. Components is the next module to move onto the database.' }}</p></div></div></section>
        </div>
    </div>
</x-layouts::app>
