<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => 'Demo'])
    </head>
    <body class="min-h-screen overflow-x-hidden bg-[#0b0d10] text-zinc-100">
        @php
            $it = app()->getLocale() === 'it';
            $nav = [
                ['dashboard', $it ? 'Dashboard' : 'Dashboard'],
                ['events', $it ? 'Eventi' : 'Events'],
                ['garage', 'Garage'],
                ['components', $it ? 'Componenti' : 'Components'],
                ['configurations', $it ? 'Configurazioni' : 'Configurations'],
                ['setups', $it ? 'Setup tecnici' : 'Technical setups'],
                ['circuits', $it ? 'Circuiti' : 'Circuits'],
                ['sessions', $it ? 'Sessioni' : 'Sessions'],
                ['maintenance', $it ? 'Manutenzione' : 'Maintenance'],
                ['expenses', $it ? 'Costi e spese' : 'Costs & expenses'],
            ];
        @endphp

        <div class="min-h-screen lg:grid lg:grid-cols-[17rem_minmax(0,1fr)]">
            <aside class="hidden min-h-screen border-r border-white/10 bg-[#111317] lg:flex lg:flex-col lg:sticky lg:top-0 lg:h-screen">
                <div class="border-b border-white/5 px-5 py-5">
                    <a href="{{ route('home') }}" aria-label="PitMetric home" class="inline-flex items-center">
                        <img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-8 w-auto max-w-44">
                    </a>
                    <div class="mt-5 rounded-xl border border-white/10 bg-white/[0.025] p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-white">Race Team Demo</p>
                                <p class="mt-1 truncate text-xs text-zinc-500">KR2 / Rotax MAX EVO</p>
                            </div>
                            <span class="shrink-0 rounded-full border border-[#E10600]/35 bg-[#E10600]/10 px-2 py-1 font-mono text-[9px] font-bold uppercase tracking-[0.1em] text-[#ff625e]">Demo</span>
                        </div>
                    </div>
                </div>

                <nav class="min-h-0 flex-1 overflow-y-auto px-3 py-4" aria-label="Demo manager">
                    <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[0.16em] text-zinc-600">{{ $it ? 'Gestionale' : 'Manager' }}</p>
                    <div class="grid gap-1">
                        @foreach ($nav as [$key, $label])
                            <button type="button" data-public-demo-nav="{{ $key }}" class="flex min-w-0 items-center rounded-lg px-3 py-2.5 text-left text-sm font-medium text-zinc-400 transition hover:bg-white/5 hover:text-white">
                                <span class="min-w-0 flex-1 truncate">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                </nav>

                <div class="border-t border-white/5 p-4">
                    <div class="rounded-xl border border-white/10 bg-white/[0.025] p-3 text-xs leading-5 text-zinc-500">
                        {{ $it ? 'Modalità sola lettura: i pulsanti di scrittura sono disabilitati e nessun dato reale viene modificato.' : 'Read-only mode: write actions are disabled and no real data is modified.' }}
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        <a href="{{ route('register') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#E10600] px-3 text-xs font-bold text-white transition hover:bg-[#f01812]">{{ $it ? 'Registrati' : 'Register' }}</a>
                        <a href="{{ route('login') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-white/10 px-3 text-xs font-semibold text-zinc-300 transition hover:border-white/25 hover:text-white">{{ $it ? 'Accedi' : 'Log in' }}</a>
                    </div>
                </div>
            </aside>

            <div class="min-w-0">
                <header class="sticky top-0 z-40 border-b border-white/10 bg-[#111317]/95 px-4 py-3 backdrop-blur-xl lg:hidden">
                    <div class="flex items-center justify-between gap-3">
                        <a href="{{ route('home') }}" aria-label="PitMetric home" class="min-w-0">
                            <img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-7 w-auto max-w-[8.5rem]">
                        </a>
                        <span class="shrink-0 rounded-full border border-[#E10600]/35 bg-[#E10600]/10 px-2.5 py-1 font-mono text-[9px] font-bold uppercase tracking-[0.1em] text-[#ff625e]">{{ $it ? 'Demo sola lettura' : 'Read-only demo' }}</span>
                    </div>
                    <label class="mt-3 grid gap-1">
                        <span class="sr-only">{{ $it ? 'Sezione demo' : 'Demo section' }}</span>
                        <select id="public-demo-section" class="w-full rounded-lg border border-white/10 bg-[#0b0d10] px-3 py-2.5 text-sm font-semibold text-zinc-200 outline-none focus:border-[#E10600]">
                            @foreach ($nav as [$key, $label])
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </header>

                <main class="pitmetric-app min-w-0 overflow-x-hidden px-3 py-4 sm:px-5 sm:py-5 lg:px-8 lg:py-7">
                    <div class="mx-auto w-full max-w-[1360px]">
                        <section class="mb-4 flex min-w-0 flex-col gap-4 rounded-xl border border-[#E10600]/20 bg-[#E10600]/[0.055] p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-[#E10600]/15 px-2.5 py-1 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-[#ff625e]">{{ $it ? 'Ambiente demo' : 'Demo environment' }}</span>
                                    <span class="text-xs text-zinc-500">Race Team Demo</span>
                                </div>
                                <p class="mt-2 max-w-3xl break-words text-sm leading-6 text-zinc-300">{{ $it ? 'Stai esplorando il gestionale PitMetric vero e proprio con dati d’esempio. Puoi navigare tutte le sezioni qui disponibili, ma non aggiungere, modificare o eliminare contenuti.' : 'You are exploring the actual PitMetric manager with sample data. You can browse every available section here, but creating, editing and deleting content is disabled.' }}</p>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                <a href="{{ route('register') }}" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-[#E10600] px-4 text-sm font-bold text-white transition hover:bg-[#f01812]">{{ $it ? 'Crea il tuo workspace' : 'Create your workspace' }}</a>
                            </div>
                        </section>

                        <div id="pitmetric-public-demo"
                             data-locale="{{ app()->getLocale() === 'it' ? 'it' : 'en' }}"
                             data-demo-samples="Race Team Demo | Rotax MAX EVO #02 | Circuito di Busca | € 4,018.70"
                             class="min-w-0 space-y-4 sm:space-y-5"></div>
                    </div>
                </main>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
