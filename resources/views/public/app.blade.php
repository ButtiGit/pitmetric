<x-layouts::public
    :title="app()->getLocale() === 'it' ? 'App Android' : 'Android App'"
    :description="app()->getLocale() === 'it' ? 'Scarica PitMetric per Android: dati essenziali leggibili al volo, lavoro offline e sincronizzazione automatica.' : 'Download PitMetric for Android: glanceable trackside data, offline work and automatic sync.'"
>
    @php($apkUrl = 'https://github.com/ButtiGit/pitmetric/releases/download/android-latest/PitMetric.apk')

    <section class="relative isolate overflow-hidden border-b border-white/10 bg-[#080A0D]">
        <div class="absolute inset-0 -z-20 bg-[radial-gradient(circle_at_70%_20%,rgba(225,6,0,.17),transparent_34%),radial-gradient(circle_at_20%_80%,rgba(225,6,0,.07),transparent_36%)]"></div>
        <div class="mx-auto grid min-h-[72vh] max-w-7xl items-center gap-14 px-5 py-20 lg:grid-cols-[1.02fr_.98fr] lg:px-8 lg:py-28">
            <div>
                <img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-12 w-auto sm:h-14">
                <div class="mt-7 flex flex-wrap items-center gap-3">
                    <p class="m-0 font-mono text-[11px] font-semibold uppercase tracking-[0.2em] text-[#E10600]">PitMetric / Android</p>
                    <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 font-mono text-[9px] uppercase tracking-[.14em] text-zinc-400">Beta 0.3 · UI refresh</span>
                </div>
                <h1 class="mt-5 max-w-3xl text-5xl font-black tracking-[-0.055em] text-white sm:text-6xl lg:text-7xl">
                    {{ app()->getLocale() === 'it' ? 'Quello che serve. Subito.' : 'What matters. Instantly.' }}
                </h1>
                <p class="mt-7 max-w-2xl text-base leading-8 text-zinc-300 sm:text-lg">
                    {{ app()->getLocale() === 'it'
                        ? 'PitMetric Android è stato ridisegnato per la pista: best lap, ultimo giro, trend e azioni essenziali si leggono in un colpo d’occhio. Sessioni, note, setup e manutenzione continuano a funzionare anche senza connessione.'
                        : 'PitMetric Android has been redesigned for trackside use: best lap, last lap, trend and essential actions are visible at a glance. Sessions, notes, setups and maintenance still work without a connection.' }}
                </p>

                <div class="mt-9 flex flex-wrap items-center gap-4">
                    <a href="{{ $apkUrl }}" class="inline-flex min-h-12 items-center justify-center bg-[#E10600] px-6 py-3 text-sm font-black uppercase tracking-[0.08em] text-white transition hover:bg-[#F01812]">
                        {{ app()->getLocale() === 'it' ? 'Scarica APK Android' : 'Download Android APK' }}
                    </a>
                    <span class="font-mono text-[11px] uppercase tracking-[0.12em] text-zinc-500">Latest build · Android · APK</span>
                </div>

                <p class="mt-5 max-w-xl text-xs leading-6 text-zinc-500">
                    {{ app()->getLocale() === 'it'
                        ? 'Il pulsante punta sempre all’ultima build Android pubblicata da PitMetric. Android potrebbe chiederti di autorizzare l’installazione da questa sorgente.'
                        : 'The button always points to the latest Android build published by PitMetric. Android may ask you to allow installation from this source.' }}
                </p>
            </div>

            <div class="relative mx-auto w-full max-w-[390px]">
                <div class="absolute -inset-8 rounded-[4rem] bg-[#E10600]/10 blur-3xl"></div>
                <div class="relative overflow-hidden rounded-[3rem] border border-white/15 bg-[#111318] p-[9px] shadow-2xl">
                    <div class="overflow-hidden rounded-[2.55rem] bg-[#F2F2F7] text-[#0B0B0F]">
                        <div class="flex items-center justify-between bg-[#F2F2F7]/95 px-5 pb-3 pt-6">
                            <img src="{{ asset('brand/pitmetric-primary-light.svg') }}" alt="PitMetric" class="h-6 w-auto">
                            <span class="grid h-9 w-9 place-items-center rounded-full bg-black/[.06] text-[#E10600]">↻</span>
                        </div>

                        <div class="px-4 pb-5">
                            <div class="flex items-end justify-between gap-3 py-3">
                                <div>
                                    <p class="m-0 text-[9px] font-bold uppercase tracking-[.12em] text-zinc-500">Race dashboard</p>
                                    <p class="mt-1 text-[36px] font-black tracking-[-.055em]">1:12.438</p>
                                    <p class="m-0 text-[11px] text-zinc-500">Personal best locale</p>
                                </div>
                                <span class="mb-1 rounded-full bg-[#E10600] px-4 py-3 text-[10px] font-black text-white">▶ START</span>
                            </div>

                            <div class="grid grid-cols-[1.25fr_.85fr] gap-2">
                                <div class="row-span-3 flex min-h-52 flex-col justify-end rounded-[1.4rem] bg-gradient-to-br from-[#EF1B15] to-[#B50904] p-4 text-white shadow-lg shadow-red-900/10">
                                    <p class="m-0 text-[8px] font-bold tracking-[.1em] text-white/65">BEST LAP</p>
                                    <strong class="mt-2 text-[34px] tracking-[-.06em]">1:12.438</strong>
                                    <span class="mt-1 text-[9px] text-white/65">18 giri registrati</span>
                                </div>
                                @foreach ([['ULTIMO', '1:12.901', '+0.463'], ['MEDIA', '1:13.220', '18 giri'], ['TREND', '−1.284', 'più veloce']] as [$label, $value, $small])
                                    <div class="flex min-h-16 flex-col justify-center rounded-[1.15rem] bg-white px-3 py-2">
                                        <span class="text-[7px] font-bold tracking-[.08em] text-zinc-500">{{ $label }}</span>
                                        <strong class="mt-0.5 text-[16px] tracking-[-.035em]">{{ $value }}</strong>
                                        <small class="text-[7px] text-zinc-400">{{ $small }}</small>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-3 overflow-hidden rounded-[1.35rem] bg-white">
                                @foreach ([['Cronometro', 'Nuovo tempo'], ['flag', 'Sessione'], ['note', 'Nota trackside']] as $index => [$icon, $label])
                                    <div class="flex items-center gap-3 border-b border-black/[.06] px-3 py-2.5 last:border-0">
                                        <span class="grid h-8 w-8 place-items-center rounded-xl {{ $index === 0 ? 'bg-[#E10600] text-white' : 'bg-[#FFF1F0] text-[#E10600]' }} text-[10px] font-black">{{ $index + 1 }}</span>
                                        <div class="min-w-0 flex-1"><strong class="block text-[11px]">{{ $label }}</strong><span class="text-[8px] text-zinc-400">{{ $index === 0 ? 'Cronometro o manuale' : ($index === 1 ? 'Salvataggio rapido' : 'Due tocchi') }}</span></div>
                                        <span class="text-zinc-300">›</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mx-3 mb-3 grid grid-cols-5 rounded-[1.35rem] border border-black/[.07] bg-white/90 px-1 py-2 shadow-xl shadow-black/10">
                            @foreach (['Home', 'Timer', 'Sessioni', 'Garage', 'Altro'] as $index => $label)
                                <div class="text-center {{ $index === 0 ? 'text-[#E10600]' : 'text-zinc-400' }}"><div class="text-base">{{ ['●','◴','⚑','◆','•••'][$index] }}</div><div class="mt-0.5 text-[7px] font-semibold">{{ $label }}</div></div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-5 py-20 lg:px-8 lg:py-24">
        <div class="grid gap-12 lg:grid-cols-[.65fr_1.35fr] lg:gap-20">
            <div>
                <p class="font-mono text-[11px] uppercase tracking-[0.2em] text-[#E10600]">Install / Android</p>
                <h2 class="mt-5 text-3xl font-black tracking-tight text-white">{{ app()->getLocale() === 'it' ? 'Installazione in tre tocchi.' : 'Install in three taps.' }}</h2>
            </div>
            <ol class="grid gap-0 border-t border-white/15">
                @foreach ((app()->getLocale() === 'it'
                    ? [
                        ['01', 'Scarica', 'Premi “Scarica APK Android” e attendi il download di PitMetric.apk.'],
                        ['02', 'Autorizza', 'Se Android lo richiede, consenti al browser di installare app da questa sorgente.'],
                        ['03', 'Installa', 'Apri PitMetric.apk, conferma l’installazione e avvia PitMetric dalla nuova icona.'],
                    ]
                    : [
                        ['01', 'Download', 'Tap “Download Android APK” and wait for PitMetric.apk.'],
                        ['02', 'Allow', 'If Android asks, allow your browser to install apps from this source.'],
                        ['03', 'Install', 'Open PitMetric.apk, confirm installation and launch PitMetric from its new icon.'],
                    ]) as [$number, $title, $copy])
                    <li class="grid gap-4 border-b border-white/15 py-7 sm:grid-cols-[4rem_10rem_1fr] sm:items-start">
                        <span class="font-mono text-xs text-[#E10600]">{{ $number }}</span>
                        <strong class="text-white">{{ $title }}</strong>
                        <p class="text-sm leading-6 text-zinc-400">{{ $copy }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    <section class="border-y border-white/10 bg-[#0D1014]">
        <div class="mx-auto grid max-w-7xl gap-10 px-5 py-16 lg:grid-cols-3 lg:px-8">
            <article><p class="font-mono text-[10px] uppercase tracking-[.18em] text-[#E10600]">01 / Glanceable</p><h3 class="mt-4 text-xl font-bold text-white">Dati in un colpo d’occhio</h3><p class="mt-3 text-sm leading-6 text-zinc-400">{{ app()->getLocale() === 'it' ? 'Best lap, ultimo giro e trend hanno una gerarchia visiva netta, pensata per essere letta velocemente in pista.' : 'Best lap, last lap and trend use a clear visual hierarchy designed for quick trackside reading.' }}</p></article>
            <article><p class="font-mono text-[10px] uppercase tracking-[.18em] text-[#E10600]">02 / Offline</p><h3 class="mt-4 text-xl font-bold text-white">SQLite + outbox</h3><p class="mt-3 text-sm leading-6 text-zinc-400">{{ app()->getLocale() === 'it' ? 'Le operazioni vengono conservate sul dispositivo prima di qualsiasi richiesta di rete.' : 'Operations are kept on-device before any network request.' }}</p></article>
            <article><p class="font-mono text-[10px] uppercase tracking-[.18em] text-[#E10600]">03 / Security</p><h3 class="mt-4 text-xl font-bold text-white">Biometria</h3><p class="mt-3 text-sm leading-6 text-zinc-400">{{ app()->getLocale() === 'it' ? 'Dopo il primo accesso puoi sbloccare la sessione con viso, impronta o credenziale del dispositivo.' : 'After the first sign-in, unlock the session with face, fingerprint or device credentials.' }}</p></article>
        </div>
    </section>
</x-layouts::public>