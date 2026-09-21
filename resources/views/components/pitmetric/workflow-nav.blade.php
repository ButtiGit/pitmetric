@php
    $coreRoutePatterns = [
        'garage.*',
        'components.*',
        'component-installations.*',
        'configurations.*',
        'events.*',
        'sessions.*',
        'maintenance.*',
    ];

    $showCoreWorkflow = request()->routeIs(...$coreRoutePatterns);
    $workflowSnapshot = null;

    if ($showCoreWorkflow && auth()->check()) {
        $user = auth()->user();
        $workspaceContext = app(\App\Services\WorkspaceContext::class);
        $hasDatabaseAccess = $user->hasDatabaseAccess() || $user->can('manage-updates');

        if ($hasDatabaseAccess && $workspaceContext->isCoreReady()) {
            $workspaceContext->personal($user);
            $workflowSnapshot = app(\App\Services\CoreWorkflowService::class)
                ->snapshot($workspaceContext->isEventsReady());
        }
    }

    $workflowSteps = [
        [
            'key' => 'vehicle',
            'label' => __('workflow.vehicle'),
            'route' => 'garage.index',
            'matches' => ['garage.*'],
        ],
        [
            'key' => 'components',
            'label' => __('workflow.components'),
            'route' => 'components.index',
            'matches' => ['components.*', 'component-installations.*'],
        ],
        [
            'key' => 'configuration',
            'label' => __('workflow.configuration'),
            'route' => 'configurations.index',
            'matches' => ['configurations.*'],
        ],
        [
            'key' => 'event',
            'label' => __('workflow.event'),
            'route' => 'events.index',
            'matches' => ['events.*'],
        ],
        [
            'key' => 'session',
            'label' => __('workflow.session'),
            'route' => 'sessions.index',
            'matches' => ['sessions.*'],
        ],
        [
            'key' => 'usage',
            'label' => __('workflow.usage'),
            'route' => null,
            'matches' => [],
            'automatic' => true,
        ],
        [
            'key' => 'maintenance',
            'label' => __('workflow.maintenance'),
            'route' => 'maintenance.index',
            'matches' => ['maintenance.*'],
        ],
    ];
@endphp

@if ($showCoreWorkflow)
    <section class="mx-4 mt-4 rounded-2xl border border-pm-border bg-pm-panel/90 p-3 shadow-sm sm:mx-6 lg:mx-8" aria-label="{{ __('workflow.navigation') }}">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-pm-accent">{{ __('workflow.core_flow') }}</p>
                <p class="mt-1 text-xs text-pm-muted">{{ __('workflow.core_flow_description') }}</p>
                @if ($workflowSnapshot !== null && ! $workflowSnapshot['complete'])
                    <p class="mt-2 text-xs font-semibold text-pm-text-secondary">
                        {{ __('workflow.next') }}:
                        <span class="text-pm-accent">{{ __('workflow.'.$workflowSnapshot['next']['stage']) }}</span>
                    </p>
                @elseif ($workflowSnapshot !== null)
                    <p class="mt-2 text-xs font-semibold text-pm-success">{{ __('workflow.ready') }}</p>
                @endif
            </div>

            <nav class="-mx-1 overflow-x-auto px-1 pb-1" aria-label="{{ __('workflow.navigation') }}">
                <ol class="flex min-w-max items-center gap-1.5">
                    @foreach ($workflowSteps as $index => $step)
                        @php
                            $active = $step['matches'] !== [] && request()->routeIs(...$step['matches']);
                            $automatic = $step['automatic'] ?? false;
                            $complete = $workflowSnapshot['steps'][$step['key']]['complete'] ?? false;
                            $isNext = $workflowSnapshot !== null && $workflowSnapshot['next']['stage'] === $step['key'] && ! $workflowSnapshot['complete'];
                            $routeName = $workflowSnapshot['steps'][$step['key']]['route'] ?? $step['route'];

                            $classes = match (true) {
                                $active => 'border-pm-accent/50 bg-pm-accent/10 text-pm-accent',
                                $complete => 'border-pm-success/30 bg-pm-success-subtle text-pm-success',
                                $isNext => 'border-pm-accent/40 bg-pm-accent/5 text-pm-text',
                                $automatic => 'border-pm-border bg-pm-subtle text-pm-muted',
                                default => 'border-pm-border bg-pm-subtle text-pm-text-secondary hover:border-pm-accent/30 hover:text-pm-text',
                            };
                        @endphp

                        <li class="flex items-center gap-1.5">
                            @if ($routeName !== null)
                                <a
                                    href="{{ route($routeName) }}"
                                    @if ($active) aria-current="step" @endif
                                    class="inline-flex h-9 items-center gap-2 rounded-lg border px-3 text-xs font-bold transition {{ $classes }}"
                                >
                                    <span class="font-mono text-[10px] opacity-60">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                    <span>{{ $step['label'] }}</span>
                                    @if ($complete)
                                        <span class="rounded bg-pm-success/10 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-[0.08em]">{{ __('workflow.done') }}</span>
                                    @elseif ($isNext)
                                        <span class="rounded bg-pm-accent/10 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-[0.08em]">{{ __('workflow.next_short') }}</span>
                                    @endif
                                </a>
                            @else
                                <span
                                    class="inline-flex h-9 items-center gap-2 rounded-lg border px-3 text-xs font-bold {{ $classes }}"
                                    title="{{ __('workflow.usage_automatic') }}"
                                >
                                    <span class="font-mono text-[10px] opacity-60">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                    <span>{{ $step['label'] }}</span>
                                    <span class="rounded bg-pm-success/10 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-[0.08em]">{{ $complete ? __('workflow.done') : __('workflow.automatic') }}</span>
                                </span>
                            @endif

                            @if (! $loop->last)
                                <span class="text-xs text-pm-muted" aria-hidden="true">&rarr;</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        </div>

        @cannot('team-write')
            <p class="mt-3 rounded-lg border border-pm-border bg-pm-subtle p-3 text-sm text-pm-muted" role="status">{{ __('workflow.read_only') }}</p>
        @endcannot
    </section>
@endif
