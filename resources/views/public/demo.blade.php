<x-layouts::public :title="app()->getLocale() === 'it' ? 'Demo' : 'Demo'">
    @php
        $it = app()->getLocale() === 'it';
        $sessions = [
            ['date' => '24 Sep', 'track' => 'Circuito di Busca', 'layout' => 'Full', 'laps' => 42, 'best' => '52.184', 'distance' => '50.4 km'],
            ['date' => '18 Sep', 'track' => 'Kart Planet', 'layout' => 'Race', 'laps' => 37, 'best' => '48.932', 'distance' => '41.6 km'],
            ['date' => '07 Sep', 'track' => 'Circuito di Busca', 'layout' => 'Full', 'laps' => 31, 'best' => '52.611', 'distance' => '37.2 km'],
            ['date' => '30 Aug', 'track' => 'Kart Planet', 'layout' => 'Race', 'laps' => 44, 'best' => '49.105', 'distance' => '49.5 km'],
        ];
        $components = [
            ['name' => 'Rotax MAX EVO #02', 'type' => $it ? 'Motore' : 'Engine', 'usage' => '11.8 h', 'life' => '72%'],
            ['name' => 'Chain DID #04', 'type' => $it ? 'Trasmissione' : 'Drivetrain', 'usage' => '286 km', 'life' => '61%'],
            ['name' => 'Bridgestone YLR #08', 'type' => $it ? 'Pneumatici' : 'Tyres', 'usage' => '93 laps', 'life' => '44%'],
            ['name' => 'Brake Pads #03', 'type' => $it ? 'Freni' : 'Brakes', 'usage' => '417 km', 'life' => '79%'],
        ];
        $maintenance = [
            ['title' => $it ? 'Controllo catena' : 'Chain inspection', 'due' => $it ? 'tra 64 km' : 'in 64 km', 'status' => $it ? 'Prossimo' : 'Upcoming'],
            ['title' => $it ? 'Revisione motore' : 'Engine service', 'due' => $it ? 'tra 3.2 h' : 'in 3.2 h', 'status' => $it ? 'Pianificata' : 'Planned'],
            ['title' => $it ? 'Sostituzione pastiglie' : 'Brake pad replacement', 'due' => $it ? 'tra 183 km' : 'in 183 km', 'status' => $it ? 'Monitorata' : 'Monitored'],
        ];
    @endphp

    <div class="min-h-screen bg-[#07090c] pb-16">
        <section class="border-b border-white/10 bg-[radial-gradient(circle_at_top_right,rgba(225,6,0,.12),transparent_32%)]">
            <div class="mx-auto max-w-7xl px-5 py-10 lg:px-8 lg:py-14">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full border border-[#E10600]/45 bg-[#E10600]/10 px-3 py-1 font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-[#ff625e]">{{ $it ? 'Demo sola lettura' : 'Read-only demo' }}</span>
                            <span class="rounded-full border border-white/10 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.12em] text-zinc-500">Sample workspace</span>
                        </div>
                        <h1 class="mt-5 max-w-3xl break-words text-3xl font-black tracking-[-0.04em] text-white sm:text-4xl lg:text-5xl">{{ $it ? 'Esplora PitMetric con un garage già popolato.' : 'Explore PitMetric with a populated garage.' }}</h1>
                        <p class="mt-4 max-w-2xl text-sm leading-7 text-zinc-400 sm:text-base">{{ $it ? 'Puoi consultare mezzi, componenti, sessioni, manutenzione e costi d’esempio. In questa modalità non è possibile creare, modificare o eliminare dati.' : 'Browse sample vehicles, components, sessions, maintenance and costs. Creating, editing and deleting data is disabled in this mode.' }}</p>
                    </div>
                    <div class="flex w-full flex-wrap gap-2 lg:w-auto lg:justify-end">
                        <a href="{{ route('register') }}" class="inline-flex min-h-11 flex-1 items-center justify-center whitespace-nowrap bg-[#E10600] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#f01812] sm:flex-none">{{ $it ? 'Crea account' : 'Create account' }}</a>
                        <a href="{{ route('login') }}" class="inline-flex min-h-11 flex-1 items-center justify-center whitespace-nowrap border border-white/15 px-5 py-2.5 text-sm font-semibold text-zinc-200 transition hover:border-white/30 hover:text-white sm:flex-none">{{ $it ? 'Accedi' : 'Log in' }}</a>
                    </div>
                </div>
            </div>
        </section>

        <main class="mx-auto grid max-w-7xl gap-5 px-5 py-7 lg:grid-cols-[14rem_minmax(0,1fr)] lg:px-8 lg:py-10">
            <aside class="min-w-0 self-start border border-white/10 bg-[#0d1014] p-3 lg:sticky lg:top-24">
                <div class="mb-3 border-b border-white/10 px-3 pb-3">
                    <p class="break-words text-sm font-bold text-white">Race Team Demo</p>
                    <p class="mt-1 text-xs text-zinc-500">KR2 / Rotax MAX</p>
                </div>
                <nav class="grid grid-cols-2 gap-1 text-sm sm:grid-cols-4 lg:grid-cols-1" aria-label="Demo sections">
                    @foreach ([
                        ['overview', $it ? 'Panoramica' : 'Overview'],
                        ['garage', 'Garage'],
                        ['components', $it ? 'Componenti' : 'Components'],
                        ['sessions', $it ? 'Sessioni' : 'Sessions'],
                        ['maintenance', $it ? 'Manutenzione' : 'Maintenance'],
                        ['expenses', $it ? 'Costi' : 'Expenses'],
                    ] as [$anchor, $label])
                        <a href="#{{ $anchor }}" class="min-w-0 rounded px-3 py-2.5 font-semibold text-zinc-400 transition hover:bg-white/5 hover:text-white"><span class="block truncate">{{ $label }}</span></a>
                    @endforeach
                </nav>
            </aside>

            <div class="min-w-0 space-y-5">
                <section id="overview" class="scroll-mt-28">
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach ([
                            [$it ? 'Distanza stagione' : 'Season distance', '1,842 km', '+214 km'],
                            [$it ? 'Sessioni' : 'Sessions', '18', $it ? '4 questo mese' : '4 this month'],
                            [$it ? 'Costo / km' : 'Cost / km', '€ 2.18', '-6.4%'],
                            [$it ? 'Manutenzioni aperte' : 'Open maintenance', '3', $it ? '0 scadute' : '0 overdue'],
                        ] as [$label, $value, $note])
                            <article class="min-w-0 overflow-hidden border border-white/10 bg-[#0d1014] p-5">
                                <p class="break-words text-xs font-semibold uppercase tracking-[0.1em] text-zinc-500">{{ $label }}</p>
                                <p class="mt-3 break-words text-2xl font-black tracking-tight text-white">{{ $value }}</p>
                                <p class="mt-2 break-words text-xs text-zinc-500">{{ $note }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section id="garage" class="scroll-mt-28 border border-white/10 bg-[#0d1014] p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0"><p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-[#ff625e]">Garage</p><h2 class="mt-2 break-words text-xl font-bold text-white">KR2 / Rotax MAX EVO</h2><p class="mt-1 break-words text-sm text-zinc-500">Kart · 2025 · Race Build V3</p></div>
                        <span class="shrink-0 rounded-full border border-emerald-500/25 bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-300">{{ $it ? 'Pronto pista' : 'Track ready' }}</span>
                    </div>
                    <div class="mt-6 grid gap-3 sm:grid-cols-3">
                        @foreach ([[$it ? 'Configurazione' : 'Configuration', 'Race Build V3'], [$it ? 'Ultima uscita' : 'Last run', '24 Sep 2026'], [$it ? 'Utilizzo totale' : 'Total usage', '1,842 km']] as [$label, $value])
                            <div class="min-w-0 border border-white/10 bg-[#090b0e] p-4"><p class="text-xs text-zinc-600">{{ $label }}</p><p class="mt-2 break-words font-semibold text-zinc-200">{{ $value }}</p></div>
                        @endforeach
                    </div>
                </section>

                <section id="components" class="scroll-mt-28 border border-white/10 bg-[#0d1014] p-5 sm:p-6">
                    <div class="flex flex-wrap items-end justify-between gap-3"><div class="min-w-0"><p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-[#ff625e]">{{ $it ? 'Componenti' : 'Components' }}</p><h2 class="mt-2 break-words text-xl font-bold text-white">{{ $it ? 'Vita componenti' : 'Component life' }}</h2></div><span class="text-xs text-zinc-600">4 {{ $it ? 'monitorati' : 'tracked' }}</span></div>
                    <div class="mt-5 grid gap-3 md:grid-cols-2">
                        @foreach ($components as $component)
                            <article class="min-w-0 overflow-hidden border border-white/10 bg-[#090b0e] p-4">
                                <div class="flex min-w-0 items-start justify-between gap-3"><div class="min-w-0"><h3 class="break-words font-semibold text-white">{{ $component['name'] }}</h3><p class="mt-1 break-words text-xs text-zinc-500">{{ $component['type'] }} · {{ $component['usage'] }}</p></div><span class="shrink-0 font-mono text-xs text-zinc-300">{{ $component['life'] }}</span></div>
                                <div class="mt-4 h-1.5 overflow-hidden bg-white/5"><div class="h-full bg-[#E10600]" style="width: {{ $component['life'] }}"></div></div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section id="sessions" class="scroll-mt-28 overflow-hidden border border-white/10 bg-[#0d1014]">
                    <div class="p-5 sm:p-6"><p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-[#ff625e]">{{ $it ? 'Sessioni' : 'Sessions' }}</p><h2 class="mt-2 break-words text-xl font-bold text-white">{{ $it ? 'Ultime attività' : 'Latest activity' }}</h2></div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[660px] text-left text-sm">
                            <thead class="border-y border-white/10 bg-black/20 text-[10px] uppercase tracking-[0.1em] text-zinc-600"><tr><th class="px-5 py-3">{{ $it ? 'Data' : 'Date' }}</th><th class="px-5 py-3">{{ $it ? 'Circuito' : 'Circuit' }}</th><th class="px-5 py-3">Layout</th><th class="px-5 py-3">{{ $it ? 'Giri' : 'Laps' }}</th><th class="px-5 py-3">Best</th><th class="px-5 py-3">{{ $it ? 'Distanza' : 'Distance' }}</th></tr></thead>
                            <tbody class="divide-y divide-white/10">
                                @foreach ($sessions as $session)
                                    <tr><td class="px-5 py-4 whitespace-nowrap text-zinc-400">{{ $session['date'] }}</td><td class="px-5 py-4 font-semibold text-zinc-200">{{ $session['track'] }}</td><td class="px-5 py-4 text-zinc-500">{{ $session['layout'] }}</td><td class="px-5 py-4 text-zinc-300">{{ $session['laps'] }}</td><td class="px-5 py-4 font-mono text-white">{{ $session['best'] }}</td><td class="px-5 py-4 whitespace-nowrap text-zinc-400">{{ $session['distance'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="grid min-w-0 gap-5 xl:grid-cols-2">
                    <section id="maintenance" class="min-w-0 scroll-mt-28 border border-white/10 bg-[#0d1014] p-5 sm:p-6">
                        <p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-[#ff625e]">{{ $it ? 'Manutenzione' : 'Maintenance' }}</p><h2 class="mt-2 break-words text-xl font-bold text-white">{{ $it ? 'Prossime scadenze' : 'Upcoming work' }}</h2>
                        <div class="mt-5 divide-y divide-white/10 border-y border-white/10">
                            @foreach ($maintenance as $item)
                                <div class="flex min-w-0 flex-wrap items-center justify-between gap-3 py-4"><div class="min-w-0"><p class="break-words font-semibold text-zinc-200">{{ $item['title'] }}</p><p class="mt-1 break-words text-xs text-zinc-600">{{ $item['due'] }}</p></div><span class="shrink-0 text-xs font-semibold text-zinc-400">{{ $item['status'] }}</span></div>
                            @endforeach
                        </div>
                    </section>

                    <section id="expenses" class="min-w-0 scroll-mt-28 border border-white/10 bg-[#0d1014] p-5 sm:p-6">
                        <p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-[#ff625e]">{{ $it ? 'Costi' : 'Expenses' }}</p><h2 class="mt-2 break-words text-xl font-bold text-white">{{ $it ? 'Spesa stagione' : 'Season spend' }}</h2>
                        <p class="mt-5 break-words text-3xl font-black tracking-tight text-white">€ 4,018.70</p>
                        <div class="mt-5 space-y-3 text-sm">
                            @foreach ([[$it ? 'Consumabili' : 'Consumables', '€ 1,428.40'], [$it ? 'Manutenzione' : 'Maintenance', '€ 1,135.00'], [$it ? 'Pista e iscrizioni' : 'Track & entries', '€ 940.30'], [$it ? 'Ricambi' : 'Parts', '€ 515.00']] as [$label, $value])
                                <div class="flex min-w-0 items-center justify-between gap-4 border-b border-white/10 pb-3 last:border-0"><span class="min-w-0 break-words text-zinc-500">{{ $label }}</span><span class="shrink-0 font-mono text-zinc-200">{{ $value }}</span></div>
                            @endforeach
                        </div>
                    </section>
                </div>

                <div class="flex flex-col gap-4 border border-[#E10600]/25 bg-[#E10600]/5 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                    <div class="min-w-0"><p class="break-words font-bold text-white">{{ $it ? 'Questa demo è volutamente sola lettura.' : 'This demo is intentionally read-only.' }}</p><p class="mt-1 break-words text-sm leading-6 text-zinc-400">{{ $it ? 'Registrati per creare il tuo workspace e usare tutte le funzioni di inserimento e modifica.' : 'Create an account to get your own workspace and use all create and edit features.' }}</p></div>
                    <a href="{{ route('register') }}" class="inline-flex min-h-11 shrink-0 items-center justify-center whitespace-nowrap bg-[#E10600] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#f01812]">{{ $it ? 'Inizia ora' : 'Get started' }}</a>
                </div>
            </div>
        </main>
    </div>
</x-layouts::public>
