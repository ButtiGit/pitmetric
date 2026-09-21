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
        request()->routeIs('timing.*') => 'timing',
        request()->routeIs('telemetry.*') => 'telemetry',
        request()->routeIs('team.*') => 'team',
        request()->routeIs('insights.*') => 'insights',
        request()->routeIs('control-center.*') => 'control_center',
        request()->routeIs('profile.edit') => 'profile',
        request()->routeIs('appearance.edit') => 'appearance',
        request()->routeIs('security.edit') => 'security',
        request()->routeIs('studio.updates.*') => 'studio_updates',
        request()->routeIs('studio.users.*') => 'user_admin',
        request()->routeIs('newsletter.*') => 'newsletter',
        default => null,
    };

    $tourCopy = $context === null ? [] : [
        'labels' => [
            'dialog' => __('help.tour.labels.dialog'),
            'step' => __('help.tour.labels.step'),
            'close' => __('help.tour.labels.close'),
            'skip' => __('help.tour.labels.skip'),
            'back' => __('help.tour.labels.back'),
            'next' => __('help.tour.labels.next'),
            'done' => __('help.tour.labels.done'),
        ],
        'page' => [
            'title' => __('help.titles.'.$context),
            'description' => __('help.pages.'.$context),
            'workflow_title' => __('help.tour.page.workflow_title'),
            'workflow_body' => __('help.tour.page.workflow_body'),
            'action_title' => __('help.tour.page.action_title'),
            'action_body' => __('help.tour.page.action_body'),
            'detail_title' => __('help.tour.page.detail_title'),
            'detail_body' => __('help.tour.page.detail_body'),
            'help_title' => __('help.tour.page.help_title'),
            'help_body' => __('help.tour.page.help_body'),
        ],
        'full' => [
            'next_title' => __('help.tour.full.next_title'),
            'next_body' => __('help.tour.full.next_body'),
            'workflow_title' => __('help.tour.full.workflow_title'),
            'workflow_body' => __('help.tour.full.workflow_body'),
            'navigation_title' => __('help.tour.full.navigation_title'),
            'navigation_body' => __('help.tour.full.navigation_body'),
            'status_title' => __('help.tour.full.status_title'),
            'status_body' => __('help.tour.full.status_body'),
            'event_title' => __('help.tour.full.event_title'),
            'event_body' => __('help.tour.full.event_body'),
            'help_title' => __('help.tour.full.help_title'),
            'help_body' => __('help.tour.full.help_body'),
        ],
    ];
@endphp

@if ($context !== null)
    <div
        class="fixed bottom-5 right-5 z-[70]"
        data-pitmetric-context-help
        data-pm-guide
        data-pm-context="{{ $context }}"
        data-pm-user-id="{{ auth()->id() }}"
        data-pm-dashboard-url="{{ route('dashboard') }}"
    >
        <button
            type="button"
            class="inline-flex size-11 items-center justify-center rounded-full border border-pm-accent/35 bg-[#111317]/95 text-base font-black text-pm-accent shadow-xl shadow-black/25 backdrop-blur transition hover:border-pm-accent hover:bg-pm-accent/10 focus:outline-none focus:ring-2 focus:ring-pm-accent/60"
            aria-label="{{ __('help.open') }}"
            aria-expanded="false"
            aria-controls="pitmetric-guide-panel"
            data-pm-guide-toggle
        >?</button>

        <div
            id="pitmetric-guide-panel"
            class="pm-guide-panel"
            role="dialog"
            aria-label="{{ __('help.titles.'.$context) }}"
            data-pm-guide-panel
            hidden
        >
            <div class="pm-guide-panel__header">
                <p class="pm-guide-panel__eyebrow">{{ __('help.guide.eyebrow') }}</p>
                <h2 class="pm-guide-panel__title">{{ __('help.titles.'.$context) }}</h2>
                <p class="pm-guide-panel__copy">{{ __('help.pages.'.$context) }}</p>
            </div>

            <div class="pm-guide-panel__actions">
                <button type="button" class="pm-guide-action" data-pm-start-page-tour>
                    <span>{{ __('help.guide.page_tour') }}</span>
                    <span class="pm-guide-action__hint">{{ __('help.guide.page_tour_hint') }}</span>
                </button>
                <button type="button" class="pm-guide-action" data-pm-start-full-tour>
                    <span>{{ __('help.guide.full_tour') }}</span>
                    <span class="pm-guide-action__hint">{{ __('help.guide.full_tour_hint') }}</span>
                </button>
            </div>
        </div>

        <script type="application/json" data-pm-tour-copy>@json($tourCopy)</script>
    </div>
@endif
