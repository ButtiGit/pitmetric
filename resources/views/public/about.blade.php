<x-layouts::public title="About" description="Simone Butticè, sviluppatore full-stack e creatore di PitMetric.">
    <section class="mx-auto max-w-5xl px-5 py-20 lg:px-8 lg:py-28">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#ff625e]">Creator</p>
        <h1 class="mt-4 text-4xl font-black tracking-tight text-white sm:text-5xl">Simone Butticè</h1>
        <p class="mt-6 max-w-3xl text-lg leading-8 text-zinc-400">Junior Full-Stack Web Developer con esperienza concreta nello sviluppo di gestionali web, API, database relazionali e applicazioni su misura. PitMetric nasce dall’unione tra software engineering e passione per il motorsport.</p>

        <div class="mt-14 grid gap-5 md:grid-cols-2">
            <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-7">
                <h2 class="text-xl font-bold text-white">Esperienza</h2>
                <p class="mt-4 font-semibold text-zinc-200">Junior Software Developer · Edisoft</p>
                <p class="mt-1 text-sm text-zinc-500">Settembre 2024 — presente</p>
                <p class="mt-5 text-sm leading-7 text-zinc-400">Gestionali PHP/JavaScript/MySQL, progettazione database, sistemi legacy con oltre 100 tabelle, REST API, autenticazione, integrazioni software, applicazioni Ionic, debugging, ottimizzazione SQL e sicurezza applicativa.</p>
            </article>
            <article class="rounded-2xl border border-white/10 bg-white/[0.03] p-7">
                <h2 class="text-xl font-bold text-white">Approccio</h2>
                <p class="mt-4 text-sm leading-7 text-zinc-400">Problem solving, precisione, affidabilità, curiosità, apprendimento rapido, iniziativa, autonomia, creatività e lavoro per obiettivi. L’obiettivo è costruire software utile, leggibile e sostenibile nel tempo.</p>
                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach (['Italiano · madrelingua', 'English · B2+', 'Git', 'Debugging', 'Requirements analysis'] as $item)
                        <span class="rounded-full border border-white/10 px-3 py-1.5 text-xs text-zinc-300">{{ $item }}</span>
                    @endforeach
                </div>
            </article>
        </div>

        <div class="mt-5 grid gap-5 lg:grid-cols-4">
            @foreach ([
                ['Backend', 'PHP · Laravel · REST API · JSON · auth'],
                ['Frontend', 'HTML5 · CSS3 · JavaScript · Livewire · Fetch API'],
                ['Database', 'MySQL · SQLite · SQL · JOIN · query optimization'],
                ['Mobile', 'Ionic · hybrid applications · API integration'],
            ] as [$heading, $copy])
                <article class="rounded-2xl border border-white/10 p-6"><h2 class="font-bold text-white">{{ $heading }}</h2><p class="mt-3 text-sm leading-6 text-zinc-400">{{ $copy }}</p></article>
            @endforeach
        </div>

        <div class="mt-16 rounded-3xl border border-[#E10600]/30 bg-[#E10600]/10 p-8 sm:p-10">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#ff6f6a]">Why PitMetric</p>
            <h2 class="mt-4 text-3xl font-bold text-white">Un progetto personale, con ambizione da prodotto reale.</h2>
            <p class="mt-5 max-w-3xl leading-7 text-zinc-300">PitMetric è il progetto con cui sto portando competenze da sviluppo gestionale in un dominio che mi interessa davvero: tracciare componenti, utilizzo, manutenzione e costi in modo affidabile, senza perdere lo storico tecnico del mezzo.</p>
        </div>
    </section>
</x-layouts::public>
