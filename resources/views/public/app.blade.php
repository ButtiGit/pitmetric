<x-layouts::public
    :title="app()->getLocale() === 'it' ? 'App Android' : 'Android App'"
    :description="app()->getLocale() === 'it' ? 'Scarica PitMetric per Android e lavora in pista anche senza connessione.' : 'Download PitMetric for Android and keep working trackside even without a connection.'"
>
    @php($apkUrl = 'https://github.com/ButtiGit/pitmetric/releases/download/android-latest/PitMetric.apk')

    <section class="relative isolate overflow-hidden border-b border-white/10 bg-[#080A0D]">
        <div class="absolute inset-0 -z-20 bg-[radial-gradient(circle_at_70%_20%,rgba(225,6,0,.17),transparent_34%),radial-gradient(circle_at_20%_80%,rgba(225,6,0,.07),transparent_36%)]"></div>
        <div class="mx-auto grid min-h-[72vh] max-w-7xl items-center gap-14 px-5 py-20 lg:grid-cols-[1.05fr_.95fr] lg:px-8 lg:py-28">
            <div>
                <img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-12 w-auto sm:h-14">
                <p class="mt-7 font-mono text-[11px] font-semibold uppercase tracking-[0.2em] text-[#E10600]">PitMetric / Android</p>
                <h1 class="mt-5 max-w-3xl text-5xl font-black tracking-[-0.055em] text-white sm:text-6xl lg:text-7xl">
                    {{ app()->getLocale() === 'it' ? 'La pista non aspetta il Wi-Fi.' : 'The track does not wait for Wi-Fi.' }}
                </h1>
                <p class="mt-7 max-w-2xl text-base leading-8 text-zinc-300 sm:text-lg">
                    {{ app()->getLocale() === 'it'
                        ? 'PitMetric Android salva sessioni, note, setup, manutenzione e media prima sul telefono. Quando torna la connessione, la coda viene sincronizzata automaticamente.'
                        : 'PitMetric Android stores sessions, notes, setups, maintenance and media on the phone first. When connectivity returns, the queue syncs automatically.' }}
                </p>

                <div class="mt-9 flex flex-wrap items-center gap-4">
                    <a href="{{ $apkUrl }}" class="inline-flex min-h-12 items-center justify-center bg-[#E10600] px-6 py-3 text-sm font-black uppercase tracking-[0.08em] text-white transition hover:bg-[#F01812]">
                        {{ app()->getLocale() === 'it' ? 'Scarica APK Android' : 'Download Android APK' }}
                    </a>
                    <span class="font-mono text-[11px] uppercase tracking-[0.12em] text-zinc-500">Beta · Android · APK</span>
                </div>

                <p class="mt-5 max-w-xl text-xs leading-6 text-zinc-500">
                    {{ app()->getLocale() === 'it'
                        ? 'Il file arriva direttamente dalla release ufficiale del repository PitMetric. Android potrebbe chiederti di autorizzare l’installazione da questa sorgente.'
                        : 'The file comes directly from the official PitMetric repository release. Android may ask you to allow installation from this source.' }}
                </p>
            </div>

            <div class="relative mx-auto w-full max-w-md">
                <div class="absolute -inset-5 rounded-[3rem] bg-[#E10600]/10 blur-3xl"></div>
                <div class="relative overflow-hidden rounded-[2.3rem] border border-white/10 bg-[#111318] p-3 shadow-2xl">
                    <div class="rounded-[1.9rem] border border-white/10 bg-[#0B0D10] p-5">
                        <div class="flex items-center justify-between border-b border-white/10 pb-5">
                            <div class="flex items-center gap-3">
                                <img src="{{ asset('brand/pitmetric-app-icon-dark.svg') }}" alt="" class="h-12 w-12 shrink-0">
                                <div><img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-6 w-auto"><p class="mt-1 text-xs text-zinc-500">Trackside / Offline</p></div>
                            </div>
                            <span class="rounded-full bg-emerald-500/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[.08em] text-emerald-400">Local first</span>
                        </div>
                        <div class="mt-5 grid grid-cols-2 gap-3">
                            @foreach ([
                                ['Pit Mode', 'Quick capture'],
                                ['Weekend', 'Schedule'],
                                ['Garage', 'Vehicles'],
                                ['Setup', 'Track changes'],
                                ['Maintenance', 'Work orders'],
                                ['Gallery', 'Photo + video'],
                            ] as [$label, $copy])
                                <div class="min-h-24 rounded-2xl border border-white/10 bg-white/[.025] p-4">
                                    <p class="font-semibold text-white">{{ $label }}</p>
                                    <p class="mt-2 text-xs text-zinc-500">{{ $copy }}</p>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-4 rounded-2xl border border-[#E10600]/20 bg-[#E10600]/[.06] p-4 text-sm leading-6 text-zinc-300">
                            {{ app()->getLocale() === 'it' ? '0 segnale? Continui a registrare. La sincronizzazione riparte quando la rete torna disponibile.' : 'No signal? Keep recording. Sync resumes when the network is available again.' }}
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
            <article><p class="font-mono text-[10px] uppercase tracking-[.18em] text-[#E10600]">01 / Offline</p><h3 class="mt-4 text-xl font-bold text-white">SQLite + outbox</h3><p class="mt-3 text-sm leading-6 text-zinc-400">{{ app()->getLocale() === 'it' ? 'Le operazioni vengono conservate sul dispositivo prima di qualsiasi richiesta di rete.' : 'Operations are kept on-device before any network request.' }}</p></article>
            <article><p class="font-mono text-[10px] uppercase tracking-[.18em] text-[#E10600]">02 / Security</p><h3 class="mt-4 text-xl font-bold text-white">Biometria</h3><p class="mt-3 text-sm leading-6 text-zinc-400">{{ app()->getLocale() === 'it' ? 'Dopo il primo accesso puoi sbloccare la sessione con viso, impronta o credenziale del dispositivo.' : 'After the first sign-in, unlock the session with face, fingerprint or device credentials.' }}</p></article>
            <article><p class="font-mono text-[10px] uppercase tracking-[.18em] text-[#E10600]">03 / Media</p><h3 class="mt-4 text-xl font-bold text-white">Foto + video</h3><p class="mt-3 text-sm leading-6 text-zinc-400">{{ app()->getLocale() === 'it' ? 'Titolo, descrizione e file restano disponibili in galleria anche senza segnale.' : 'Title, description and files remain available in the gallery even without signal.' }}</p></article>
        </div>
    </section>
</x-layouts::public>