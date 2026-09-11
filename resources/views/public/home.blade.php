<x-layouts::public>
    <section class="mx-auto max-w-7xl px-5 pb-20 pt-20 lg:px-8 lg:pt-28">
        <div class="max-w-4xl">
            <span class="inline-flex rounded-full border border-[#E10600]/40 bg-[#E10600]/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-[#ff625e]">Private beta · in development</span>
            <h1 class="mt-7 text-5xl font-black tracking-[-0.04em] text-white sm:text-6xl lg:text-7xl">Know every lap.<br><span class="text-[#E10600]">Track every component.</span></h1>
            <p class="mt-7 max-w-2xl text-lg leading-8 text-zinc-400">PitMetric è un software motorsport pensato per piloti, proprietari e piccoli team che vogliono sapere cosa è montato sul mezzo, quanto ha lavorato e quando richiede attenzione.</p>
            <div class="mt-9 flex flex-wrap gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-xl bg-[#E10600] px-5 py-3 font-semibold text-white transition hover:bg-[#F01812]">Apri dashboard</a>
                @else
                    <a href="{{ route('register') }}" class="rounded-xl bg-[#E10600] px-5 py-3 font-semibold text-white transition hover:bg-[#F01812]">Scopri PitMetric</a>
                    <a href="{{ route('login') }}" class="rounded-xl border border-white/15 px-5 py-3 font-semibold text-white transition hover:border-white/30">Accedi</a>
                @endauth
            </div>
        </div>

        <div class="mt-20 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Configuration', 'Registra la configurazione fisica del mezzo e i componenti realmente installati.'],
                ['Usage', 'Traccia distanza, ore, cicli, sessioni ed eventi senza limitarti ai soli chilometri.'],
                ['Maintenance', 'Collega l’utilizzo reale alle scadenze di controllo, revisione e sostituzione.'],
                ['Costs', 'Mantieni una vista chiara di spese, interventi e costo operativo del mezzo.'],
            ] as [$heading, $copy])
                <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-6">
                    <div class="mb-8 h-1 w-10 rounded-full bg-[#E10600]"></div>
                    <h2 class="text-lg font-bold text-white">{{ $heading }}</h2>
                    <p class="mt-3 text-sm leading-6 text-zinc-400">{{ $copy }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="border-y border-white/10 bg-white/[0.025]">
        <div class="mx-auto grid max-w-7xl gap-12 px-5 py-20 lg:grid-cols-[0.9fr_1.1fr] lg:px-8">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#ff625e]">Il principio</p>
                <h2 class="mt-4 text-3xl font-bold tracking-tight text-white">Lo storico deve restare vero.</h2>
                <p class="mt-5 leading-7 text-zinc-400">Quando una sessione viene registrata, PitMetric deve ricordare esattamente la configurazione usata in quel momento. Se il giorno dopo cambi motore, catena o gomme, le sessioni precedenti non vengono riscritte.</p>
            </div>
            <div class="rounded-3xl border border-white/10 bg-[#0d1014] p-6 sm:p-8">
                <div class="grid gap-3 font-mono text-sm">
                    <div class="rounded-xl border border-white/10 bg-white/[0.03] p-4"><span class="text-zinc-500">01</span> <span class="ml-3 text-white">Race Build V3</span></div>
                    <div class="ml-6 rounded-xl border border-white/10 bg-white/[0.03] p-4 text-zinc-300">Engine #02 · Chain #04 · Tyres #08</div>
                    <div class="rounded-xl border border-[#E10600]/35 bg-[#E10600]/10 p-4 text-[#ff7a76]">1.250 m × 40 giri = 50 km</div>
                    <div class="ml-6 rounded-xl border border-white/10 bg-white/[0.03] p-4 text-zinc-300">Propagazione automatica ai tracker compatibili</div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-5 py-20 lg:px-8">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#ff625e]">Roadmap</p><h2 class="mt-3 text-3xl font-bold text-white">Costruito per il lavoro reale in pista.</h2></div>
            <a href="{{ route('updates.index') }}" class="text-sm font-semibold text-zinc-300 hover:text-white">Segui gli aggiornamenti →</a>
        </div>
        <div class="mt-8 grid gap-4 md:grid-cols-3">
            @foreach ([
                ['01', 'Vehicle & configuration history', 'Snapshot versionate della configurazione per preservare lo storico.'],
                ['02', 'Multi-metric component tracking', 'Km, ore, cicli, sessioni ed eventi con regole indipendenti.'],
                ['03', 'Circuit-driven usage', 'Lunghezza layout × giri per generare utilizzo verificabile lato server.'],
            ] as [$number, $heading, $copy])
                <article class="rounded-2xl border border-white/10 p-6"><span class="font-mono text-xs text-[#ff625e]">{{ $number }}</span><h3 class="mt-5 font-bold text-white">{{ $heading }}</h3><p class="mt-3 text-sm leading-6 text-zinc-400">{{ $copy }}</p></article>
            @endforeach
        </div>
    </section>
</x-layouts::public>
