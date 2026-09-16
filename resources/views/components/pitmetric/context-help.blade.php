@php
    $context = match (true) {
        request()->routeIs('dashboard') => 'dashboard',
        request()->routeIs('events.*') => 'events',
        request()->routeIs('garage.*') => 'garage',
        request()->routeIs('components.*'), request()->routeIs('component-installations.*') => 'components',
        request()->routeIs('configurations.*') => 'configurations',
        request()->routeIs('setups.*') => 'setups',
        request()->routeIs('circuits.*') => 'circuits',
        request()->routeIs('sessions.*') => 'sessions',
        request()->routeIs('maintenance.*') => 'maintenance',
        request()->routeIs('expenses.*') => 'expenses',
        request()->routeIs('team.*') => 'team',
        request()->routeIs('insights.*') => 'insights',
        request()->routeIs('control-center.*') => 'control_center',
        request()->routeIs('studio.updates.*') => 'studio_updates',
        request()->routeIs('studio.users.*') => 'user_admin',
        request()->routeIs('newsletter.*') => 'newsletter',
        default => null,
    };
@endphp

@if ($context !== null)
    <div class="fixed bottom-5 right-5 z-[70]" data-pitmetric-context-help>
        <flux:tooltip toggleable position="left">
            <button
                type="button"
                class="inline-flex size-11 items-center justify-center rounded-full border border-pm-accent/35 bg-[#111317]/95 text-base font-black text-pm-accent shadow-xl shadow-black/25 backdrop-blur transition hover:border-pm-accent hover:bg-pm-accent/10 focus:outline-none focus:ring-2 focus:ring-pm-accent/60"
                aria-label="{{ __('help.open') }}"
            >?</button>

            <flux:tooltip.content class="max-w-[22rem] space-y-1.5">
                <p class="text-sm font-semibold">{{ __('help.titles.'.$context) }}</p>
                <p class="text-sm leading-5">{{ __('help.pages.'.$context) }}</p>
            </flux:tooltip.content>
        </flux:tooltip>
    </div>
@endif
