<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>@include('partials.head')</head>
    <body class="min-h-screen overflow-x-hidden bg-[#0b0d10] text-zinc-100">
        <flux:sidebar sticky collapsible="mobile" class="pm-mobile-sidebar border-e border-[#242932] bg-[#111317]">
            <flux:sidebar.header class="border-b border-white/5 pb-4"><a href="{{ route('dashboard') }}" wire:navigate aria-label="PitMetric"><img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-8 w-auto max-w-44"></a><flux:sidebar.collapse class="lg:hidden" /></flux:sidebar.header>

            @php
                $activityOpen = request()->routeIs('events.*') || request()->routeIs('sessions.*') || request()->routeIs('circuits.*');
                $vehicleOpen = request()->routeIs('garage.*') || request()->routeIs('components.*') || request()->routeIs('component-installations.*') || request()->routeIs('configurations.*') || request()->routeIs('setups.*') || request()->routeIs('maintenance.*');
                $performanceOpen = request()->routeIs('timing.*') || request()->routeIs('telemetry.*') || request()->routeIs('insights.*');
                $managementOpen = request()->routeIs('expenses.*') || request()->routeIs('team.*') || request()->routeIs('control-center.*');
                $studioOpen = request()->routeIs('studio.*');
            @endphp

            <flux:sidebar.nav class="min-h-0 flex-1 overflow-y-auto pt-4 pe-1 [scrollbar-width:thin]">
                <flux:sidebar.group :heading="__('pitmetric.nav.platform')" class="grid gap-1">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:sidebar.item>

                    <details name="pitmetric-sidebar-section" class="group/sidebar-section mt-1" @if ($activityOpen) open @endif>
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-zinc-400 transition hover:bg-white/5 hover:text-zinc-100 [&::-webkit-details-marker]:hidden">
                            <flux:icon.calendar-days class="size-4 shrink-0" />
                            <span class="min-w-0 flex-1">{{ app()->getLocale() === 'it' ? 'Attività' : 'Activity' }}</span>
                            <flux:icon.chevron-right class="size-4 shrink-0 transition-transform duration-200 group-open/sidebar-section:rotate-90" />
                        </summary>
                        <div class="ml-3 mt-1 grid gap-1 border-l border-white/10 pl-2">
                            <flux:sidebar.item icon="calendar-days" :href="route('events.index')" :current="request()->routeIs('events.*')">{{ __('demo.nav.events') }}</flux:sidebar.item>
                            <flux:sidebar.item icon="flag" :href="route('sessions.index')" :current="request()->routeIs('sessions.*')">{{ __('demo.nav.sessions') }}</flux:sidebar.item>
                            <flux:sidebar.item icon="map" :href="route('circuits.index')" :current="request()->routeIs('circuits.*')">{{ __('demo.nav.circuits') }}</flux:sidebar.item>
                        </div>
                    </details>

                    <details name="pitmetric-sidebar-section" class="group/sidebar-section mt-1" @if ($vehicleOpen) open @endif>
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-zinc-400 transition hover:bg-white/5 hover:text-zinc-100 [&::-webkit-details-marker]:hidden">
                            <flux:icon.truck class="size-4 shrink-0" />
                            <span class="min-w-0 flex-1">{{ app()->getLocale() === 'it' ? 'Veicolo' : 'Vehicle' }}</span>
                            <flux:icon.chevron-right class="size-4 shrink-0 transition-transform duration-200 group-open/sidebar-section:rotate-90" />
                        </summary>
                        <div class="ml-3 mt-1 grid gap-1 border-l border-white/10 pl-2">
                            <flux:sidebar.item icon="truck" :href="route('garage.index')" :current="request()->routeIs('garage.*')">{{ __('demo.nav.garage') }}</flux:sidebar.item>
                            <flux:sidebar.item icon="wrench-screwdriver" :href="route('components.index')" :current="request()->routeIs('components.*') || request()->routeIs('component-installations.*')">{{ __('demo.nav.components') }}</flux:sidebar.item>
                            <flux:sidebar.item icon="squares-2x2" :href="route('configurations.index')" :current="request()->routeIs('configurations.*')">{{ __('demo.nav.configurations') }}</flux:sidebar.item>
                            <flux:sidebar.item icon="adjustments-horizontal" :href="route('setups.index')" :current="request()->routeIs('setups.*')">{{ app()->getLocale() === 'it' ? 'Setup tecnici' : 'Technical setups' }}</flux:sidebar.item>
                            <flux:sidebar.item icon="clipboard-document-check" :href="route('maintenance.index')" :current="request()->routeIs('maintenance.*')">{{ __('demo.nav.maintenance') }}</flux:sidebar.item>
                        </div>
                    </details>

                    <details name="pitmetric-sidebar-section" class="group/sidebar-section mt-1" @if ($performanceOpen) open @endif>
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-zinc-400 transition hover:bg-white/5 hover:text-zinc-100 [&::-webkit-details-marker]:hidden">
                            <flux:icon.chart-bar class="size-4 shrink-0" />
                            <span class="min-w-0 flex-1">Performance</span>
                            <flux:icon.chevron-right class="size-4 shrink-0 transition-transform duration-200 group-open/sidebar-section:rotate-90" />
                        </summary>
                        <div class="ml-3 mt-1 grid gap-1 border-l border-white/10 pl-2">
                            <flux:sidebar.item icon="clock" :href="route('timing.index')" :current="request()->routeIs('timing.*')">{{ app()->getLocale() === 'it' ? 'Tempi' : 'Timing' }}</flux:sidebar.item>
                            <flux:sidebar.item icon="signal" :href="route('telemetry.index')" :current="request()->routeIs('telemetry.*')">{{ app()->getLocale() === 'it' ? 'Telemetria' : 'Telemetry' }}</flux:sidebar.item>
                            @if (auth()->user()->hasManagerAccess() || auth()->user()->can('manage-updates'))
                                <flux:sidebar.item icon="chart-bar" :href="route('insights.index')" :current="request()->routeIs('insights.*')">Intelligence</flux:sidebar.item>
                            @endif
                        </div>
                    </details>

                    <details name="pitmetric-sidebar-section" class="group/sidebar-section mt-1" @if ($managementOpen) open @endif>
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-zinc-400 transition hover:bg-white/5 hover:text-zinc-100 [&::-webkit-details-marker]:hidden">
                            <flux:icon.folder class="size-4 shrink-0" />
                            <span class="min-w-0 flex-1">{{ app()->getLocale() === 'it' ? 'Gestione' : 'Management' }}</span>
                            <flux:icon.chevron-right class="size-4 shrink-0 transition-transform duration-200 group-open/sidebar-section:rotate-90" />
                        </summary>
                        <div class="ml-3 mt-1 grid gap-1 border-l border-white/10 pl-2">
                            <flux:sidebar.item icon="banknotes" :href="route('expenses.index')" :current="request()->routeIs('expenses.*')">{{ __('demo.nav.expenses') }}</flux:sidebar.item>
                            @if (auth()->user()->hasManagerAccess() || auth()->user()->can('manage-updates'))
                                <flux:sidebar.item icon="users" :href="route('team.index')" :current="request()->routeIs('team.*')">Team</flux:sidebar.item>
                                @can('team-manage')
                                    <flux:sidebar.item icon="shield-check" :href="route('control-center.index')" :current="request()->routeIs('control-center.*')">Control Center</flux:sidebar.item>
                                @endcan
                            @endif
                        </div>
                    </details>

                    @can('manage-updates')
                        <details name="pitmetric-sidebar-section" class="group/sidebar-section mt-1" @if ($studioOpen) open @endif>
                            <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-zinc-400 transition hover:bg-white/5 hover:text-zinc-100 [&::-webkit-details-marker]:hidden">
                                <flux:icon.cog-6-tooth class="size-4 shrink-0" />
                                <span class="min-w-0 flex-1">{{ __('pitmetric.studio.eyebrow') }}</span>
                                <flux:icon.chevron-right class="size-4 shrink-0 transition-transform duration-200 group-open/sidebar-section:rotate-90" />
                            </summary>
                            <div class="ml-3 mt-1 grid gap-1 border-l border-white/10 pl-2">
                                <flux:sidebar.item icon="pencil-square" :href="route('studio.updates.index')" :current="request()->routeIs('studio.updates.*')">{{ __('pitmetric.studio.nav') }}</flux:sidebar.item>
                                <flux:sidebar.item icon="users" :href="route('studio.users.index')" :current="request()->routeIs('studio.users.*')">{{ __('users.nav') }}</flux:sidebar.item>
                            </div>
                        </details>
                    @endcan
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.nav class="shrink-0 border-t border-white/5 pt-4">
                <flux:sidebar.item icon="envelope" :href="route('newsletter.edit')" :current="request()->routeIs('newsletter.edit')">{{ __('demo.nav.newsletter') }}</flux:sidebar.item>
                <flux:sidebar.item icon="globe-alt" :href="route('home')">{{ __('pitmetric.nav.home') }}</flux:sidebar.item>
                <flux:sidebar.item icon="newspaper" :href="route('updates.index')">{{ __('pitmetric.nav.updates') }}</flux:sidebar.item>
                <flux:sidebar.item icon="user" :href="route('about')">{{ __('pitmetric.nav.about') }}</flux:sidebar.item>
            </flux:sidebar.nav>

            <div class="mx-2 mb-3 mt-4 shrink-0 rounded-xl border border-white/8 bg-white/[0.02] p-1.5"><div class="grid grid-cols-2 gap-1.5">@foreach (['en' => 'EN', 'it' => 'IT'] as $locale => $label)<form method="POST" action="{{ route('locale.update') }}">@csrf<input type="hidden" name="locale" value="{{ $locale }}"><button class="w-full rounded-lg px-2 py-2 font-mono text-xs font-semibold tracking-[0.08em] transition {{ app()->getLocale() === $locale ? 'bg-[#E10600] text-white shadow-sm' : 'text-zinc-500 hover:bg-white/5 hover:text-zinc-200' }}">{{ $label }}</button></form>@endforeach</div></div>
            <x-desktop-user-menu class="hidden shrink-0 border-t border-white/5 pt-3 lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <flux:header data-pm-mobile-header class="pm-mobile-header sticky top-0 z-40 border-b border-white/5 bg-[#111317]/95 backdrop-blur-xl lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <a href="{{ route('dashboard') }}" class="ml-1 inline-flex min-w-0 items-center" wire:navigate aria-label="PitMetric">
                <img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-7 max-w-[8.5rem] w-auto">
            </a>
            <flux:spacer />
            <flux:dropdown position="top" align="end"><flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" /><flux:menu><flux:menu.radio.group><div class="p-0 text-sm font-normal"><div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm"><flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" /><div class="grid min-w-0 flex-1 text-start text-sm leading-tight"><flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading><flux:text class="truncate">{{ auth()->user()->email }}</flux:text></div></div></div></flux:menu.radio.group><flux:menu.separator /><flux:menu.radio.group><flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item></flux:menu.radio.group><flux:menu.separator /><form method="POST" action="{{ route('logout') }}" class="w-full">@csrf<flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">{{ __('Log out') }}</flux:menu.item></form></flux:menu></flux:dropdown>
        </flux:header>

        {{ $slot }}
        <x-pitmetric.context-help />
        @persist('toast')<flux:toast.group><flux:toast /></flux:toast.group>@endpersist
        @fluxScripts
    </body>
</html>
