<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>@include('partials.head')</head>
    <body data-pm-app-shell class="min-h-screen overflow-x-hidden bg-[#0b0d10] text-zinc-100">
        @php
            $demoUrl = fn (string $target) => $target === 'dashboard'
                ? route('demo.public')
                : route('demo.manager', ['section' => $target]);
            $activityOpen = in_array($section, ['events', 'sessions', 'circuits'], true);
            $vehicleOpen = in_array($section, ['garage', 'components', 'configurations', 'setups', 'maintenance'], true);
            $performanceOpen = in_array($section, ['timing', 'telemetry', 'insights'], true);
            $managementOpen = in_array($section, ['expenses', 'team'], true);
            $it = app()->getLocale() === 'it';
        @endphp

        <flux:sidebar sticky collapsible="mobile" class="pm-mobile-sidebar border-e border-[#242932] bg-[#111317]">
            <flux:sidebar.header class="border-b border-white/5 pb-4">
                <a href="{{ $demoUrl('dashboard') }}" aria-label="PitMetric Demo"><img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-8 w-auto max-w-44"></a>
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <div class="mx-2 mt-4 rounded-xl border border-[#E10600]/25 bg-[#E10600]/8 px-3 py-3">
                <div class="flex items-center gap-2"><span class="size-2 rounded-full bg-[#E10600]"></span><p class="text-[10px] font-black uppercase tracking-[0.14em] text-[#ff625e]">Demo · Read only</p></div>
                <p class="mt-2 text-sm font-bold text-zinc-100">Race Team Demo</p>
                <p class="mt-1 text-xs leading-5 text-zinc-500">{{ $it ? 'Stessa interfaccia del gestionale reale, dati isolati.' : 'Same interface as the real manager, isolated data.' }}</p>
            </div>

            <flux:sidebar.nav class="min-h-0 flex-1 overflow-y-auto pt-4 pe-1 [scrollbar-width:thin]">
                <flux:sidebar.group :heading="__('pitmetric.nav.platform')" class="grid gap-1">
                    <flux:sidebar.item icon="home" :href="$demoUrl('dashboard')" :current="$section === 'dashboard'">{{ __('Dashboard') }}</flux:sidebar.item>

                    <details name="pitmetric-sidebar-section" class="group/sidebar-section mt-1" @if ($activityOpen) open @endif>
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-zinc-400 transition hover:bg-white/5 hover:text-zinc-100 [&::-webkit-details-marker]:hidden">
                            <flux:icon.calendar-days class="size-4 shrink-0" />
                            <span class="min-w-0 flex-1">{{ $it ? 'Attività' : 'Activity' }}</span>
                            <flux:icon.chevron-right class="size-4 shrink-0 transition-transform duration-200 group-open/sidebar-section:rotate-90" />
                        </summary>
                        <div class="ml-3 mt-1 grid gap-1 border-l border-white/10 pl-2">
                            <flux:sidebar.item icon="calendar-days" :href="$demoUrl('events')" :current="$section === 'events'">{{ __('demo.nav.events') }}</flux:sidebar.item>
                            <flux:sidebar.item icon="flag" :href="$demoUrl('sessions')" :current="$section === 'sessions'">{{ __('demo.nav.sessions') }}</flux:sidebar.item>
                            <flux:sidebar.item icon="map" :href="$demoUrl('circuits')" :current="$section === 'circuits'">{{ __('demo.nav.circuits') }}</flux:sidebar.item>
                        </div>
                    </details>

                    <details name="pitmetric-sidebar-section" class="group/sidebar-section mt-1" @if ($vehicleOpen) open @endif>
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-zinc-400 transition hover:bg-white/5 hover:text-zinc-100 [&::-webkit-details-marker]:hidden">
                            <flux:icon.truck class="size-4 shrink-0" />
                            <span class="min-w-0 flex-1">{{ $it ? 'Veicolo' : 'Vehicle' }}</span>
                            <flux:icon.chevron-right class="size-4 shrink-0 transition-transform duration-200 group-open/sidebar-section:rotate-90" />
                        </summary>
                        <div class="ml-3 mt-1 grid gap-1 border-l border-white/10 pl-2">
                            <flux:sidebar.item icon="truck" :href="$demoUrl('garage')" :current="$section === 'garage'">{{ __('demo.nav.garage') }}</flux:sidebar.item>
                            <flux:sidebar.item icon="wrench-screwdriver" :href="$demoUrl('components')" :current="$section === 'components'">{{ __('demo.nav.components') }}</flux:sidebar.item>
                            <flux:sidebar.item icon="squares-2x2" :href="$demoUrl('configurations')" :current="$section === 'configurations'">{{ __('demo.nav.configurations') }}</flux:sidebar.item>
                            <flux:sidebar.item icon="adjustments-horizontal" :href="$demoUrl('setups')" :current="$section === 'setups'">{{ $it ? 'Setup tecnici' : 'Technical setups' }}</flux:sidebar.item>
                            <flux:sidebar.item icon="clipboard-document-check" :href="$demoUrl('maintenance')" :current="$section === 'maintenance'">{{ __('demo.nav.maintenance') }}</flux:sidebar.item>
                        </div>
                    </details>

                    <details name="pitmetric-sidebar-section" class="group/sidebar-section mt-1" @if ($performanceOpen) open @endif>
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-zinc-400 transition hover:bg-white/5 hover:text-zinc-100 [&::-webkit-details-marker]:hidden">
                            <flux:icon.chart-bar class="size-4 shrink-0" />
                            <span class="min-w-0 flex-1">Performance</span>
                            <flux:icon.chevron-right class="size-4 shrink-0 transition-transform duration-200 group-open/sidebar-section:rotate-90" />
                        </summary>
                        <div class="ml-3 mt-1 grid gap-1 border-l border-white/10 pl-2">
                            <flux:sidebar.item icon="clock" :href="$demoUrl('timing')" :current="$section === 'timing'">{{ $it ? 'Tempi' : 'Timing' }}</flux:sidebar.item>
                            <flux:sidebar.item icon="signal" :href="$demoUrl('telemetry')" :current="$section === 'telemetry'">{{ $it ? 'Telemetria' : 'Telemetry' }}</flux:sidebar.item>
                            <flux:sidebar.item icon="chart-bar" :href="$demoUrl('insights')" :current="$section === 'insights'">Intelligence</flux:sidebar.item>
                        </div>
                    </details>

                    <details name="pitmetric-sidebar-section" class="group/sidebar-section mt-1" @if ($managementOpen) open @endif>
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-zinc-400 transition hover:bg-white/5 hover:text-zinc-100 [&::-webkit-details-marker]:hidden">
                            <flux:icon.folder class="size-4 shrink-0" />
                            <span class="min-w-0 flex-1">{{ $it ? 'Gestione' : 'Management' }}</span>
                            <flux:icon.chevron-right class="size-4 shrink-0 transition-transform duration-200 group-open/sidebar-section:rotate-90" />
                        </summary>
                        <div class="ml-3 mt-1 grid gap-1 border-l border-white/10 pl-2">
                            <flux:sidebar.item icon="banknotes" :href="$demoUrl('expenses')" :current="$section === 'expenses'">{{ __('demo.nav.expenses') }}</flux:sidebar.item>
                            <flux:sidebar.item icon="users" :href="$demoUrl('team')" :current="$section === 'team'">Team</flux:sidebar.item>
                        </div>
                    </details>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.nav class="shrink-0 border-t border-white/5 pt-4">
                <flux:sidebar.item icon="user-plus" :href="route('register')">{{ $it ? 'Crea account' : 'Create account' }}</flux:sidebar.item>
                <flux:sidebar.item icon="arrow-right-end-on-rectangle" :href="route('login')">{{ __('pitmetric.nav.login') }}</flux:sidebar.item>
                <flux:sidebar.item icon="globe-alt" :href="route('home')">{{ __('pitmetric.nav.home') }}</flux:sidebar.item>
            </flux:sidebar.nav>

            <div class="mx-2 mb-3 mt-4 shrink-0 rounded-xl border border-white/8 bg-white/[0.02] p-3">
                <div class="flex items-center gap-3">
                    <flux:avatar initials="RD" />
                    <div class="min-w-0"><p class="truncate text-sm font-semibold text-zinc-100">Race Team Demo</p><p class="truncate text-xs text-zinc-500">demo@pitmetric.app</p></div>
                </div>
            </div>
        </flux:sidebar>

        <flux:header data-pm-mobile-header class="pm-mobile-header sticky top-0 z-40 border-b border-white/5 bg-[#111317]/95 backdrop-blur-xl lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <a href="{{ $demoUrl('dashboard') }}" class="ml-1 inline-flex min-w-0 items-center" aria-label="PitMetric"><img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-7 max-w-[8.5rem] w-auto"></a>
            <flux:spacer />
            <span class="rounded-full border border-[#E10600]/30 bg-[#E10600]/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.1em] text-[#ff625e]">Demo</span>
        </flux:header>

        {{ $slot }}
        @fluxScripts
    </body>
</html>
