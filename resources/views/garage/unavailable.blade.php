<x-layouts::app :title="__('demo.nav.garage')">
    @php($it = app()->getLocale() === 'it')

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-3 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-7" data-pm-mobile-garage>
        <div class="mx-auto w-full max-w-[920px] space-y-4 sm:space-y-5">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">GARAGE</p>
                <h1 class="mt-2 text-2xl font-black tracking-[-0.03em] text-pm-text sm:text-3xl">{{ __('garage.title') }}</h1>
            </div>

            <section class="pm-panel border-pm-warning/30 bg-pm-warning-subtle p-5 sm:p-7" role="status">
                <div class="flex items-start gap-3 sm:gap-4">
                    <div class="grid size-10 shrink-0 place-items-center rounded-xl border border-pm-warning/30 bg-pm-warning-subtle text-lg font-black text-pm-warning">!</div>
                    <div class="min-w-0">
                        <h2 class="text-lg font-black text-pm-text">{{ $it ? 'Garage temporaneamente non disponibile' : 'Garage temporarily unavailable' }}</h2>
                        <p class="mt-2 text-sm leading-6 text-pm-text-secondary">{{ $it ? 'PitMetric sta completando un aggiornamento necessario per questa sezione. La pagina tornerà disponibile appena l’aggiornamento sarà terminato.' : 'PitMetric is completing an update required by this section. The page will be available again as soon as the update is finished.' }}</p>
                        <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                            <a href="{{ route('dashboard') }}" class="pm-ghost-button w-full sm:w-auto">{{ $it ? 'Torna alla dashboard' : 'Back to dashboard' }}</a>
                            <a href="{{ route('demo.garage') }}" class="pm-race-button w-full sm:w-auto">{{ $it ? 'Riprova' : 'Retry' }}</a>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-layouts::app>
