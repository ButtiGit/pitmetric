<x-layouts::app :title="__('Dashboard')">
    @php($it = app()->getLocale() === 'it')
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7" data-demo-dashboard data-user="{{ auth()->id() }}">
        <div class="mx-auto w-full max-w-[1360px] space-y-5">
            <section class="pm-panel p-6 sm:p-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-pm-accent"><span class="pm-status-dot"></span>{{ $it ? 'Demo gestionale' : 'Manager demo' }}</div>
                        <h1 class="mt-4 text-3xl font-black tracking-[-0.035em] text-pm-text sm:text-4xl">{{ $it ? 'Prova PitMetric con dati locali.' : 'Try PitMetric with local data.' }}</h1>
                        <p class="mt-3 max-w-3xl text-sm leading-7 text-pm-text-secondary sm:text-base">{{ $it ? 'Quello che inserisci nel gestionale demo viene salvato solo nel browser di questo dispositivo. Il tuo account serve per entrare, ma mezzi, componenti, sessioni e costi non vengono caricati sul server.' : 'Everything you enter in the demo manager is stored only in this browser on this device. Your account gets you in, but vehicles, components, sessions and costs are not uploaded to the server.' }}</p>
                    </div>
                    <a href="{{ route('demo.garage') }}" class="pm-race-button shrink-0">{{ $it ? 'Inizia dal garage' : 'Start in the garage' }}</a>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([['vehicles',$it?'Mezzi':'Vehicles'],['configurations',$it?'Configurazioni':'Configurations'],['sessions',$it?'Sessioni':'Sessions'],['maintenance',$it?'Interventi':'Maintenance']] as [$key,$label])
                    <article class="pm-stat-card"><p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $label }}</p><p class="mt-4 text-3xl font-black text-pm-text" data-demo-count="{{ $key }}">0</p></article>
                @endforeach
            </section>

            <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['demo.garage','Garage',$it?'Mezzi e anagrafica':'Vehicles'],
                    ['demo.components',$it?'Componenti':'Components',$it?'Motore, catena, gomme…':'Engine, chain, tyres…'],
                    ['demo.configurations',$it?'Configurazioni':'Configurations',$it?'Cosa è montato':'What is installed'],
                    ['demo.sessions',$it?'Sessioni':'Sessions',$it?'Giri e utilizzo':'Laps & usage'],
                    ['demo.circuits',$it?'Circuiti':'Circuits',$it?'Layout e lunghezza':'Layouts & length'],
                    ['demo.maintenance',$it?'Manutenzione':'Maintenance',$it?'Interventi e reset':'Service & resets'],
                    ['demo.expenses',$it?'Spese':'Expenses',$it?'Costi operativi':'Operating costs'],
                    ['newsletter.edit','Newsletter',$it?'Preferenza email':'Email preference'],
                ] as [$routeName,$title,$subtitle])
                    <a href="{{ route($routeName) }}" class="group rounded-xl border border-pm-border bg-pm-surface p-5 transition hover:border-pm-border-strong hover:bg-pm-hover"><div class="flex items-center justify-between"><h2 class="font-bold text-pm-text">{{ $title }}</h2><span class="text-pm-muted transition group-hover:translate-x-0.5 group-hover:text-pm-accent">→</span></div><p class="mt-2 text-sm text-pm-text-secondary">{{ $subtitle }}</p></a>
                @endforeach
            </section>

            <section class="pm-panel p-5 sm:p-6"><div class="flex items-start gap-3"><div class="grid size-9 shrink-0 place-items-center rounded-lg border border-pm-border bg-pm-subtle">🔒</div><div><h2 class="font-bold text-pm-text">{{ __('demo.locked') }}</h2><p class="mt-1 text-sm leading-6 text-pm-text-secondary">{{ __('demo.locked_copy') }}</p></div></div></section>
        </div>
    </div>
</x-layouts::app>
