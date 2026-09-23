@php
    $it = app()->getLocale() === 'it';
    $canManage = auth()->user()->hasManagerAccess() || auth()->user()->can('manage-updates');
@endphp

<nav class="pm-mobile-bottom-nav lg:hidden" aria-label="{{ $it ? 'Navigazione mobile' : 'Mobile navigation' }}" data-pm-mobile-bottom-nav>
    <a href="{{ route('dashboard') }}" wire:navigate class="pm-mobile-nav-item {{ request()->routeIs('dashboard') ? 'is-current' : '' }}">
        <flux:icon.home class="size-5" />
        <span>{{ $it ? 'Home' : 'Home' }}</span>
    </a>
    <a href="{{ route('events.index') }}" wire:navigate class="pm-mobile-nav-item {{ request()->routeIs('events.*') || request()->routeIs('sessions.*') || request()->routeIs('circuits.*') ? 'is-current' : '' }}">
        <flux:icon.calendar-days class="size-5" />
        <span>{{ $it ? 'Weekend' : 'Weekend' }}</span>
    </a>
    <a href="{{ route('pit-mode.index') }}" wire:navigate class="pm-mobile-nav-item pm-mobile-nav-item--pit {{ request()->routeIs('pit-mode.*') ? 'is-current' : '' }}">
        <span class="pm-mobile-nav-pit-icon"><flux:icon.bolt class="size-5" /></span>
        <span>Pit</span>
    </a>
    <a href="{{ route('garage.index') }}" wire:navigate class="pm-mobile-nav-item {{ request()->routeIs('garage.*') || request()->routeIs('components.*') || request()->routeIs('configurations.*') || request()->routeIs('setups.*') || request()->routeIs('maintenance.*') ? 'is-current' : '' }}">
        <flux:icon.truck class="size-5" />
        <span>Garage</span>
    </a>
    <button type="button" class="pm-mobile-nav-item" data-pm-mobile-more-trigger aria-haspopup="dialog" aria-controls="pm-mobile-more-menu">
        <flux:icon.squares-2x2 class="size-5" />
        <span>{{ $it ? 'Altro' : 'More' }}</span>
    </button>
</nav>

<dialog id="pm-mobile-more-menu" class="pm-mobile-more-sheet lg:hidden" data-pm-mobile-more>
    <div class="pm-mobile-sheet-handle"></div>
    <div class="pm-mobile-sheet-header">
        <div>
            <p class="pm-mobile-cockpit-eyebrow">PITMETRIC</p>
            <h2>{{ $it ? 'Vai dove ti serve' : 'Go where you need' }}</h2>
        </div>
        <button type="button" class="pm-mobile-sheet-close" data-pm-mobile-more-close aria-label="{{ $it ? 'Chiudi' : 'Close' }}"><flux:icon.x-mark class="size-5" /></button>
    </div>

    <div class="pm-mobile-more-body">
        <section>
            <p class="pm-mobile-more-heading">{{ $it ? 'Attività' : 'Activity' }}</p>
            <div class="pm-mobile-more-grid">
                <a href="{{ route('events.index') }}" wire:navigate><flux:icon.calendar-days class="size-5" /><span>{{ $it ? 'Weekend gara' : 'Race weekends' }}</span></a>
                <a href="{{ route('sessions.index') }}" wire:navigate><flux:icon.flag class="size-5" /><span>{{ $it ? 'Sessioni' : 'Sessions' }}</span></a>
                <a href="{{ route('circuits.index') }}" wire:navigate><flux:icon.map class="size-5" /><span>{{ $it ? 'Circuiti' : 'Circuits' }}</span></a>
                <a href="{{ route('follow-ups.index') }}" wire:navigate><flux:icon.bell-alert class="size-5" /><span>{{ $it ? 'Avvisi' : 'Alerts' }}</span></a>
            </div>
        </section>

        <section>
            <p class="pm-mobile-more-heading">{{ $it ? 'Tecnica' : 'Technical' }}</p>
            <div class="pm-mobile-more-grid">
                <a href="{{ route('components.index') }}" wire:navigate><flux:icon.wrench-screwdriver class="size-5" /><span>{{ $it ? 'Componenti' : 'Components' }}</span></a>
                <a href="{{ route('configurations.index') }}" wire:navigate><flux:icon.squares-2x2 class="size-5" /><span>{{ $it ? 'Configurazioni' : 'Configurations' }}</span></a>
                <a href="{{ route('setups.index') }}" wire:navigate><flux:icon.adjustments-horizontal class="size-5" /><span>Setup</span></a>
                <a href="{{ route('maintenance.index') }}" wire:navigate><flux:icon.clipboard-document-check class="size-5" /><span>{{ $it ? 'Manutenzione' : 'Maintenance' }}</span></a>
            </div>
        </section>

        <section>
            <p class="pm-mobile-more-heading">Performance</p>
            <div class="pm-mobile-more-grid">
                <a href="{{ route('timing.index') }}" wire:navigate><flux:icon.clock class="size-5" /><span>{{ $it ? 'Tempi' : 'Timing' }}</span></a>
                <a href="{{ route('telemetry.index') }}" wire:navigate><flux:icon.signal class="size-5" /><span>{{ $it ? 'Telemetria' : 'Telemetry' }}</span></a>
                @if ($canManage)
                    <a href="{{ route('insights.index') }}" wire:navigate><flux:icon.chart-bar class="size-5" /><span>Intelligence</span></a>
                @endif
                <a href="{{ route('expenses.index') }}" wire:navigate><flux:icon.banknotes class="size-5" /><span>{{ $it ? 'Spese' : 'Expenses' }}</span></a>
            </div>
        </section>

        @if ($canManage)
            <section>
                <p class="pm-mobile-more-heading">{{ $it ? 'Gestione' : 'Management' }}</p>
                <div class="pm-mobile-more-grid">
                    <a href="{{ route('team.index') }}" wire:navigate><flux:icon.users class="size-5" /><span>Team</span></a>
                    @can('team-manage')
                        <a href="{{ route('control-center.index') }}" wire:navigate><flux:icon.shield-check class="size-5" /><span>Control Center</span></a>
                    @endcan
                    <a href="{{ route('profile.edit') }}" wire:navigate><flux:icon.cog class="size-5" /><span>{{ $it ? 'Impostazioni' : 'Settings' }}</span></a>
                </div>
            </section>
        @endif
    </div>
</dialog>
