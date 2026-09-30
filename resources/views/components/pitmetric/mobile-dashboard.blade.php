@if (request()->routeIs('dashboard'))
    @php
        $it = app()->getLocale() === 'it';
        $canManage = auth()->user()->hasManagerAccess() || auth()->user()->can('manage-updates');
    @endphp

    <section class="pm-mobile-cockpit lg:hidden" data-pm-mobile-cockpit data-user="{{ auth()->id() }}">
        <div class="pm-mobile-cockpit-hero">
            <div class="min-w-0">
                <p class="pm-mobile-cockpit-eyebrow">{{ $it ? 'IL TUO COCKPIT' : 'YOUR COCKPIT' }}</p>
                <h1 class="pm-mobile-cockpit-title">{{ $it ? 'Cosa devi fare adesso?' : 'What do you need right now?' }}</h1>
                <p class="pm-mobile-cockpit-copy">{{ $it ? 'Tocca un riquadro per aprire le azioni veloci. Puoi cambiare priorità e nascondere quello che non usi.' : 'Tap a card to reveal quick actions. Change priorities and hide what you do not use.' }}</p>
            </div>
            <button type="button" class="pm-mobile-cockpit-customize" data-pm-dashboard-customize aria-haspopup="dialog">
                <flux:icon.adjustments-horizontal class="size-5" />
                <span>{{ $it ? 'Personalizza' : 'Customize' }}</span>
            </button>
        </div>

        <div class="pm-mobile-focus-line">
            <span>{{ $it ? 'Priorità' : 'Focus' }}</span>
            <strong data-pm-dashboard-focus>{{ $it ? 'Pista' : 'Trackside' }}</strong>
        </div>

        <div class="pm-mobile-cockpit-grid" data-pm-cockpit-grid>
            <article class="pm-mobile-cockpit-card" data-pm-cockpit-card="trackside">
                <button type="button" class="pm-mobile-cockpit-trigger" data-pm-widget-trigger aria-expanded="false">
                    <span class="pm-mobile-cockpit-icon"><flux:icon.bolt class="size-6" /></span>
                    <span class="min-w-0 flex-1 text-left">
                        <span class="pm-mobile-cockpit-kicker">TRACKSIDE</span>
                        <span class="pm-mobile-cockpit-name">{{ $it ? 'Pista e Pit Mode' : 'Track & Pit Mode' }}</span>
                        <span class="pm-mobile-cockpit-description">{{ $it ? 'Tempi, pressioni, problemi, sessioni e avvisi.' : 'Lap times, pressures, issues, sessions and alerts.' }}</span>
                    </span>
                    <flux:icon.chevron-down class="pm-mobile-cockpit-chevron size-5" />
                </button>
                <div class="pm-mobile-cockpit-actions" data-pm-widget-actions>
                    <div>
                        <a href="{{ route('pit-mode.index') }}" class="pm-mobile-quick-action pm-mobile-quick-action--primary" wire:navigate><flux:icon.bolt class="size-5" /><span>{{ $it ? 'Apri Pit Mode' : 'Open Pit Mode' }}</span></a>
                        <a href="{{ route('sessions.index') }}" class="pm-mobile-quick-action" wire:navigate><flux:icon.flag class="size-5" /><span>{{ $it ? 'Sessioni' : 'Sessions' }}</span></a>
                        <a href="{{ route('timing.index') }}" class="pm-mobile-quick-action" wire:navigate><flux:icon.clock class="size-5" /><span>{{ $it ? 'Tempi' : 'Timing' }}</span></a>
                        <a href="{{ route('follow-ups.index') }}" class="pm-mobile-quick-action" wire:navigate><flux:icon.bell-alert class="size-5" /><span>{{ $it ? 'Avvisi' : 'Alerts' }}</span></a>
                    </div>
                </div>
            </article>

            <article class="pm-mobile-cockpit-card" data-pm-cockpit-card="event">
                <button type="button" class="pm-mobile-cockpit-trigger" data-pm-widget-trigger aria-expanded="false">
                    <span class="pm-mobile-cockpit-icon"><flux:icon.calendar-days class="size-6" /></span>
                    <span class="min-w-0 flex-1 text-left">
                        <span class="pm-mobile-cockpit-kicker">RACE WEEKEND</span>
                        <span class="pm-mobile-cockpit-name">{{ $it ? 'Weekend e attività' : 'Weekend & activity' }}</span>
                        <span class="pm-mobile-cockpit-description">{{ $it ? 'Prepara evento, circuito e lavoro in pista.' : 'Prepare the event, circuit and track activity.' }}</span>
                    </span>
                    <flux:icon.chevron-down class="pm-mobile-cockpit-chevron size-5" />
                </button>
                <div class="pm-mobile-cockpit-actions" data-pm-widget-actions>
                    <div>
                        <a href="{{ route('events.index') }}" class="pm-mobile-quick-action pm-mobile-quick-action--primary" wire:navigate><flux:icon.calendar-days class="size-5" /><span>{{ $it ? 'Weekend gara' : 'Race weekends' }}</span></a>
                        <a href="{{ route('sessions.index') }}" class="pm-mobile-quick-action" wire:navigate><flux:icon.flag class="size-5" /><span>{{ $it ? 'Sessioni' : 'Sessions' }}</span></a>
                        <a href="{{ route('circuits.index') }}" class="pm-mobile-quick-action" wire:navigate><flux:icon.map class="size-5" /><span>{{ $it ? 'Circuiti' : 'Circuits' }}</span></a>
                    </div>
                </div>
            </article>

            <article class="pm-mobile-cockpit-card" data-pm-cockpit-card="garage">
                <button type="button" class="pm-mobile-cockpit-trigger" data-pm-widget-trigger aria-expanded="false">
                    <span class="pm-mobile-cockpit-icon"><flux:icon.truck class="size-6" /></span>
                    <span class="min-w-0 flex-1 text-left">
                        <span class="pm-mobile-cockpit-kicker">GARAGE</span>
                        <span class="pm-mobile-cockpit-name">{{ $it ? 'Mezzo e configurazione' : 'Vehicle & configuration' }}</span>
                        <span class="pm-mobile-cockpit-description">{{ $it ? 'Mezzi, componenti, build e setup tecnici.' : 'Vehicles, components, builds and technical setups.' }}</span>
                    </span>
                    <flux:icon.chevron-down class="pm-mobile-cockpit-chevron size-5" />
                </button>
                <div class="pm-mobile-cockpit-actions" data-pm-widget-actions>
                    <div>
                        <a href="{{ route('garage.index') }}" class="pm-mobile-quick-action pm-mobile-quick-action--primary" wire:navigate><flux:icon.truck class="size-5" /><span>Garage</span></a>
                        <a href="{{ route('components.index') }}" class="pm-mobile-quick-action" wire:navigate><flux:icon.wrench-screwdriver class="size-5" /><span>{{ $it ? 'Componenti' : 'Components' }}</span></a>
                        <a href="{{ route('configurations.index') }}" class="pm-mobile-quick-action" wire:navigate><flux:icon.squares-2x2 class="size-5" /><span>{{ $it ? 'Configurazioni' : 'Configurations' }}</span></a>
                        <a href="{{ route('setups.index') }}" class="pm-mobile-quick-action" wire:navigate><flux:icon.adjustments-horizontal class="size-5" /><span>Setup</span></a>
                    </div>
                </div>
            </article>

            <article class="pm-mobile-cockpit-card" data-pm-cockpit-card="maintenance">
                <button type="button" class="pm-mobile-cockpit-trigger" data-pm-widget-trigger aria-expanded="false">
                    <span class="pm-mobile-cockpit-icon"><flux:icon.clipboard-document-check class="size-6" /></span>
                    <span class="min-w-0 flex-1 text-left">
                        <span class="pm-mobile-cockpit-kicker">SERVICE</span>
                        <span class="pm-mobile-cockpit-name">{{ $it ? 'Manutenzione' : 'Maintenance' }}</span>
                        <span class="pm-mobile-cockpit-description">{{ $it ? 'Scadenze, interventi e componenti da controllare.' : 'Schedules, service work and components to check.' }}</span>
                    </span>
                    <flux:icon.chevron-down class="pm-mobile-cockpit-chevron size-5" />
                </button>
                <div class="pm-mobile-cockpit-actions" data-pm-widget-actions>
                    <div>
                        <a href="{{ route('maintenance.index') }}" class="pm-mobile-quick-action pm-mobile-quick-action--primary" wire:navigate><flux:icon.clipboard-document-check class="size-5" /><span>{{ $it ? 'Apri manutenzione' : 'Open maintenance' }}</span></a>
                        <a href="{{ route('components.index') }}" class="pm-mobile-quick-action" wire:navigate><flux:icon.wrench-screwdriver class="size-5" /><span>{{ $it ? 'Componenti' : 'Components' }}</span></a>
                        <a href="{{ route('follow-ups.index') }}" class="pm-mobile-quick-action" wire:navigate><flux:icon.bell-alert class="size-5" /><span>{{ $it ? 'Avvisi aperti' : 'Open alerts' }}</span></a>
                    </div>
                </div>
            </article>

            <article class="pm-mobile-cockpit-card" data-pm-cockpit-card="performance">
                <button type="button" class="pm-mobile-cockpit-trigger" data-pm-widget-trigger aria-expanded="false">
                    <span class="pm-mobile-cockpit-icon"><flux:icon.chart-bar class="size-6" /></span>
                    <span class="min-w-0 flex-1 text-left">
                        <span class="pm-mobile-cockpit-kicker">PERFORMANCE</span>
                        <span class="pm-mobile-cockpit-name">{{ $it ? 'Tempi e dati' : 'Timing & data' }}</span>
                        <span class="pm-mobile-cockpit-description">{{ $it ? 'Timing, telemetria e lettura delle prestazioni.' : 'Timing, telemetry and performance analysis.' }}</span>
                    </span>
                    <flux:icon.chevron-down class="pm-mobile-cockpit-chevron size-5" />
                </button>
                <div class="pm-mobile-cockpit-actions" data-pm-widget-actions>
                    <div>
                        <a href="{{ route('timing.index') }}" class="pm-mobile-quick-action pm-mobile-quick-action--primary" wire:navigate><flux:icon.clock class="size-5" /><span>{{ $it ? 'Tempi' : 'Timing' }}</span></a>
                        <a href="{{ route('telemetry.index') }}" class="pm-mobile-quick-action" wire:navigate><flux:icon.signal class="size-5" /><span>{{ $it ? 'Telemetria' : 'Telemetry' }}</span></a>
                        @if ($canManage)
                            <a href="{{ route('insights.index') }}" class="pm-mobile-quick-action" wire:navigate><flux:icon.chart-bar class="size-5" /><span>Intelligence</span></a>
                        @endif
                    </div>
                </div>
            </article>

            <article class="pm-mobile-cockpit-card" data-pm-cockpit-card="management">
                <button type="button" class="pm-mobile-cockpit-trigger" data-pm-widget-trigger aria-expanded="false">
                    <span class="pm-mobile-cockpit-icon"><flux:icon.banknotes class="size-6" /></span>
                    <span class="min-w-0 flex-1 text-left">
                        <span class="pm-mobile-cockpit-kicker">MANAGEMENT</span>
                        <span class="pm-mobile-cockpit-name">{{ $it ? 'Costi e team' : 'Costs & team' }}</span>
                        <span class="pm-mobile-cockpit-description">{{ $it ? 'Spese, persone e controllo del workspace.' : 'Expenses, people and workspace control.' }}</span>
                    </span>
                    <flux:icon.chevron-down class="pm-mobile-cockpit-chevron size-5" />
                </button>
                <div class="pm-mobile-cockpit-actions" data-pm-widget-actions>
                    <div>
                        <a href="{{ route('expenses.index') }}" class="pm-mobile-quick-action pm-mobile-quick-action--primary" wire:navigate><flux:icon.banknotes class="size-5" /><span>{{ $it ? 'Spese' : 'Expenses' }}</span></a>
                        @if ($canManage)
                            <a href="{{ route('team.index') }}" class="pm-mobile-quick-action" wire:navigate><flux:icon.users class="size-5" /><span>Team</span></a>
                            @can('team-manage')
                                <a href="{{ route('control-center.index') }}" class="pm-mobile-quick-action" wire:navigate><flux:icon.shield-check class="size-5" /><span>Control Center</span></a>
                            @endcan
                        @endif
                    </div>
                </div>
            </article>
        </div>

        <dialog class="pm-mobile-settings-sheet" data-pm-dashboard-settings>
            <div class="pm-mobile-sheet-handle"></div>
            <div class="pm-mobile-sheet-header">
                <div>
                    <p class="pm-mobile-cockpit-eyebrow">{{ $it ? 'PERSONALIZZA HOME' : 'CUSTOMIZE HOME' }}</p>
                    <h2>{{ $it ? 'Metti prima ciò che usi davvero' : 'Put what you use first' }}</h2>
                </div>
                <button type="button" class="pm-mobile-sheet-close" data-pm-dashboard-settings-close aria-label="{{ $it ? 'Chiudi' : 'Close' }}"><flux:icon.x-mark class="size-5" /></button>
            </div>

            <div class="pm-mobile-settings-body">
                <p class="pm-mobile-settings-label">{{ $it ? 'Preset priorità' : 'Priority preset' }}</p>
                <div class="pm-mobile-preset-grid">
                    <button type="button" data-pm-dashboard-preset="trackside"><span>01</span><strong>{{ $it ? 'Pista' : 'Trackside' }}</strong></button>
                    <button type="button" data-pm-dashboard-preset="technical"><span>02</span><strong>{{ $it ? 'Tecnico' : 'Technical' }}</strong></button>
                    <button type="button" data-pm-dashboard-preset="performance"><span>03</span><strong>Performance</strong></button>
                    <button type="button" data-pm-dashboard-preset="complete"><span>04</span><strong>{{ $it ? 'Completo' : 'Complete' }}</strong></button>
                </div>

                <p class="pm-mobile-settings-label mt-5">{{ $it ? 'Widget visibili' : 'Visible widgets' }}</p>
                <div class="pm-mobile-widget-toggles">
                    @foreach ([
                        'trackside' => $it ? 'Pista e Pit Mode' : 'Track & Pit Mode',
                        'event' => $it ? 'Weekend e attività' : 'Weekend & activity',
                        'garage' => $it ? 'Mezzo e configurazione' : 'Vehicle & configuration',
                        'maintenance' => $it ? 'Manutenzione' : 'Maintenance',
                        'performance' => $it ? 'Tempi e dati' : 'Timing & data',
                        'management' => $it ? 'Costi e team' : 'Costs & team',
                    ] as $key => $label)
                        <label>
                            <span>{{ $label }}</span>
                            <input type="checkbox" value="{{ $key }}" data-pm-widget-toggle checked>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="pm-mobile-settings-footer">
                <button type="button" class="pm-mobile-settings-reset" data-pm-dashboard-reset>{{ $it ? 'Ripristina' : 'Reset' }}</button>
                <button type="button" class="pm-mobile-settings-done" data-pm-dashboard-settings-close>{{ $it ? 'Fatto' : 'Done' }}</button>
            </div>
        </dialog>
    </section>
@endif
