<x-layouts::app :title="__('Dashboard')">
    @php($it = app()->getLocale() === 'it')
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7" data-demo-dashboard data-user="{{ auth()->id() }}">
        <div class="mx-auto w-full max-w-[1360px] space-y-5">
            <section class="pm-panel p-6 sm:p-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-pm-accent"><span class="pm-status-dot"></span>{{ $it ? 'Garage reale · resto demo' : 'Real garage · remaining demo' }}</div>
                        <h1 class="mt-4 text-3xl font-black tracking-[-0.035em] text-pm-text sm:text-4xl">{{ $it ? 'PitMetric sta diventando un prodotto reale.' : 'PitMetric is becoming a real product.' }}</h1>
                        <p class="mt-3 max-w-3xl text-sm leading-7 text-pm-text-secondary sm:text-base">{{ $it ? 'I mezzi del Garage ora vengono salvati nel database e isolati nel tuo workspace personale. Componenti, configurazioni, circuiti, sessioni, manutenzione e spese restano temporaneamente nella demo locale finché non li colleghiamo al nuovo dominio.' : 'Garage vehicles are now stored in the database and isolated inside your personal workspace. Components, configurations, circuits, sessions, maintenance and expenses remain in the local demo until they are connected to the new domain.' }}</p>
                    </div>
                    <a href="{{ route('demo.garage') }}" class="pm-race-button shrink-0">{{ $it ? 'Apri il garage reale' : 'Open the real garage' }}</a>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Mezzi reali' : 'Real vehicles' }}</p><p class="mt-4 text-3xl font-black text-pm-text">{{ $vehicleCount }}</p></article>
                @foreach ([['configurations',$it?'Configurazioni demo':'Demo configurations'],['sessions',$it?'Sessioni demo':'Demo sessions'],['maintenance',$it?'Interventi demo':'Demo maintenance']] as [$key,$label])
                    <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $label }}</p><p class="mt-4 text-3xl font-black text-pm-text" data-demo-count="{{ $key }}">0</p></article>
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
                    <a href="{{ route($routeName) }}" class="group rounded-xl border border-pm-border bg-pm-surface p-5 transition hover:border-pm-border-strong hover:bg-pm-hover"><div class="flex items-center justify-between"><h2 class="font-bold text-pm-text">{{ $title }}</h2><span class="text-pm-muted transition group-hover:translate-x-0.5 group-hover:text-pm-accent">→</span></div><p class="mt-2 text-sm text-pm-text-secondary">{{ $subtitle }}</p></a>
                @endforeach
            </section>

            <section class="pm-panel p-5 sm:p-6"><div class="flex items-start gap-3"><div class="grid size-9 shrink-0 place-items-center rounded-lg border border-pm-border bg-pm-success-subtle text-sm text-pm-success">✓</div><div><h2 class="font-bold text-pm-text">{{ $it ? 'Prima vertical slice completata' : 'First vertical slice completed' }}</h2><p class="mt-1 text-sm leading-6 text-pm-text-secondary">{{ $it ? 'Workspace e Garage sono ora persistenti. Il prossimo modulo da portare sul database è Componenti.' : 'Workspace and Garage are now persistent. Components is the next module to move onto the database.' }}</p></div></div></section>
        </div>
    </div>
</x-layouts::app>
