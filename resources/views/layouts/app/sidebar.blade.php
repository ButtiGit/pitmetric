<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>@include('partials.head')</head>
    <body class="min-h-screen overflow-x-hidden bg-[#0b0d10] text-zinc-100">
        <flux:sidebar sticky collapsible="mobile" class="pm-mobile-sidebar border-e border-[#242932] bg-[#111317]">
            <flux:sidebar.header class="border-b border-white/5 pb-4"><x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate /><flux:sidebar.collapse class="lg:hidden" /></flux:sidebar.header>

            <flux:sidebar.nav class="pt-4">
                @if (auth()->user()->hasManagerAccess() || auth()->user()->can('manage-updates'))
                    <flux:sidebar.group :heading="__('pitmetric.nav.platform')" class="grid gap-1">
                        <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="users" :href="route('team.index')" :current="request()->routeIs('team.*')">Team</flux:sidebar.item>
                        <flux:sidebar.item icon="calendar-days" :href="route('events.index')" :current="request()->routeIs('events.*')">{{ __('demo.nav.events') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="truck" :href="route('garage.index')" :current="request()->routeIs('garage.*')">{{ __('demo.nav.garage') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="wrench-screwdriver" :href="route('components.index')" :current="request()->routeIs('components.*') || request()->routeIs('component-installations.*')">{{ __('demo.nav.components') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="squares-2x2" :href="route('configurations.index')" :current="request()->routeIs('configurations.*')">{{ __('demo.nav.configurations') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="adjustments-horizontal" :href="route('setups.index')" :current="request()->routeIs('setups.*')">{{ app()->getLocale() === 'it' ? 'Setup tecnici' : 'Technical setups' }}</flux:sidebar.item>
                        <flux:sidebar.item icon="map" :href="route('circuits.index')" :current="request()->routeIs('circuits.*')">{{ __('demo.nav.circuits') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="flag" :href="route('sessions.index')" :current="request()->routeIs('sessions.*')">{{ __('demo.nav.sessions') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="clipboard-document-check" :href="route('maintenance.index')" :current="request()->routeIs('maintenance.*')">{{ __('demo.nav.maintenance') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="banknotes" :href="route('expenses.index')" :current="request()->routeIs('expenses.*')">{{ __('demo.nav.expenses') }}</flux:sidebar.item>
                    </flux:sidebar.group>
                @endif

                @can('manage-updates')
                    <flux:sidebar.group :heading="__('pitmetric.studio.eyebrow')" class="mt-4 grid gap-1">
                        <flux:sidebar.item icon="pencil-square" :href="route('studio.updates.index')" :current="request()->routeIs('studio.updates.*')">{{ __('pitmetric.studio.nav') }}</flux:sidebar.item>
                        <flux:sidebar.item icon="users" :href="route('studio.users.index')" :current="request()->routeIs('studio.users.*')">{{ __('users.nav') }}</flux:sidebar.item>
                    </flux:sidebar.group>
                @endcan
            </flux:sidebar.nav>

            <flux:spacer />
            <flux:sidebar.nav class="border-t border-white/5 pt-4">
                <flux:sidebar.item icon="envelope" :href="route('newsletter.edit')" :current="request()->routeIs('newsletter.edit')">{{ __('demo.nav.newsletter') }}</flux:sidebar.item>
                <flux:sidebar.item icon="globe-alt" :href="route('home')">{{ __('pitmetric.nav.home') }}</flux:sidebar.item>
                <flux:sidebar.item icon="newspaper" :href="route('updates.index')">{{ __('pitmetric.nav.updates') }}</flux:sidebar.item>
                <flux:sidebar.item icon="user" :href="route('about')">{{ __('pitmetric.nav.about') }}</flux:sidebar.item>
            </flux:sidebar.nav>

            <div class="mx-2 mb-3 mt-4 rounded-xl border border-white/8 bg-white/[0.02] p-1.5"><div class="grid grid-cols-2 gap-1.5">@foreach (['en' => 'EN', 'it' => 'IT'] as $locale => $label)<form method="POST" action="{{ route('locale.update') }}">@csrf<input type="hidden" name="locale" value="{{ $locale }}"><button class="w-full rounded-lg px-2 py-2 font-mono text-xs font-semibold tracking-[0.08em] transition {{ app()->getLocale() === $locale ? 'bg-[#E10600] text-white shadow-sm' : 'text-zinc-500 hover:bg-white/5 hover:text-zinc-200' }}">{{ $label }}</button></form>@endforeach</div></div>
            <x-desktop-user-menu class="hidden border-t border-white/5 pt-3 lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <flux:header data-pm-mobile-header class="pm-mobile-header sticky top-0 z-40 border-b border-white/5 bg-[#111317]/95 backdrop-blur-xl lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <a href="{{ auth()->user()->hasManagerAccess() || auth()->user()->can('manage-updates') ? route('dashboard') : route('home') }}" class="ml-1 inline-flex min-w-0 items-center" wire:navigate aria-label="PitMetric">
                <img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-7 max-w-[8.5rem] w-auto">
            </a>
            <flux:spacer />
            <flux:dropdown position="top" align="end"><flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" /><flux:menu><flux:menu.radio.group><div class="p-0 text-sm font-normal"><div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm"><flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" /><div class="grid min-w-0 flex-1 text-start text-sm leading-tight"><flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading><flux:text class="truncate">{{ auth()->user()->email }}</flux:text></div></div></div></flux:menu.radio.group><flux:menu.separator /><flux:menu.radio.group><flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item></flux:menu.radio.group><flux:menu.separator /><form method="POST" action="{{ route('logout') }}" class="w-full">@csrf<flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full cursor-pointer" data-test="logout-button">{{ __('Log out') }}</flux:menu.item></form></flux:menu></flux:dropdown>
        </flux:header>

        {{ $slot }}
        @persist('toast')<flux:toast.group><flux:toast /></flux:toast.group>@endpersist
        @fluxScripts
    </body>
</html>
