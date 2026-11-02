@props(['readiness', 'filters'])

<details class="group/readiness pm-panel p-4 sm:p-5" @if (!empty($filters['vehicle_id'])) open @endif data-vehicle-readiness>
    <summary class="flex min-h-12 cursor-pointer flex-wrap items-center justify-between gap-3 text-pm-text">
        <strong class="text-lg font-black">{{ __('readiness.title') }}</strong>
        <span class="flex items-center gap-3 text-sm text-pm-text-secondary">{{ __('readiness.total', ['count' => $readiness['vehicles']->total()]) }} <span aria-hidden="true" class="text-xl transition-transform group-open/readiness:rotate-45">+</span></span>
    </summary>
    <div class="mt-4 space-y-4">
        <p class="text-sm leading-6 text-pm-text-secondary">{{ __('readiness.scope') }}</p>
        @if ($readiness['general_tasks'] > 0)<p class="rounded-lg border border-pm-border bg-pm-subtle p-3 text-sm text-pm-text-secondary">{{ __('readiness.general_tasks', ['count' => $readiness['general_tasks']]) }}</p>@endif
        <div class="grid gap-4 lg:grid-cols-2">
            @forelse ($readiness['cards'] as $card)
                @php($variant = ['attention' => 'danger', 'pending' => 'warning', 'incomplete' => 'neutral', 'clear' => 'success'][$card['state']])
                <article class="min-w-0 rounded-xl border border-pm-border bg-pm-subtle p-4 [overflow-wrap:anywhere]" data-readiness-vehicle="{{ $card['vehicle']->id }}" data-readiness-state="{{ $card['state'] }}">
                    <div class="flex flex-wrap items-center justify-between gap-2"><h3 class="min-w-0 break-words text-base font-black text-pm-text [overflow-wrap:anywhere]">{{ $card['vehicle']->name }}</h3><x-pitmetric.status-badge :label="__('readiness.'.$card['state'])" :variant="$variant" /></div>
                    <div class="mt-3 space-y-2 text-xs text-pm-text-secondary">
                        <p>{{ $card['component_count'] > 0 ? __('readiness.components', ['count' => $card['component_count']]) : __('readiness.no_components') }}</p>
                        <p>{{ $card['configuration'] ? __('readiness.configuration').': '.$card['configuration']->configuration->name.' · v'.$card['configuration']->version_number : __('readiness.no_configuration') }}</p>
                        @if ($card['health']['untracked'] > 0)<p class="font-bold text-pm-warning">{{ __('readiness.untracked') }}</p>@endif
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-xs text-pm-text-secondary">
                        <div><dt>{{ __('readiness.overdue') }}</dt><dd class="mt-1 text-xl font-black text-pm-text">{{ $card['health']['overdue'] }}</dd></div>
                        <div><dt>{{ __('readiness.due_soon') }}</dt><dd class="mt-1 text-xl font-black text-pm-text">{{ $card['health']['due_soon'] }}</dd></div>
                        <div><dt>{{ __('readiness.tasks') }}</dt><dd class="mt-1"><strong class="block text-xl font-black text-pm-text">{{ $card['open_tasks'] }}</strong><span>{{ $card['urgent_tasks'] }} {{ __('readiness.urgent_tasks') }}</span></dd></div>
                        <div><dt>{{ __('readiness.orders') }}</dt><dd class="mt-1"><strong class="block text-xl font-black text-pm-text">{{ $card['work_orders']->count() }}</strong><span>{{ $card['urgent_orders'] }} {{ __('readiness.urgent_orders') }}</span></dd></div>
                    </dl>
                    @if ($card['details'] || $card['work_orders']->isNotEmpty())
                        <details class="mt-4 border-t border-pm-border pt-3"><summary class="min-h-10 cursor-pointer text-sm font-semibold text-pm-text-secondary">{{ __('readiness.details') }}</summary>
                            <ul class="space-y-3 text-xs text-pm-text-secondary">
                                @foreach ($card['details'] as $detail)
                                    <li class="break-words [overflow-wrap:anywhere]"><strong>{{ $detail['name'] }}</strong>
                                        @if ($detail['overdue'])<p>{{ __('readiness.overdue') }}: {{ $detail['overdue'] }}</p>@endif
                                        @if ($detail['due_soon'])<p>{{ __('readiness.due_soon') }}: {{ $detail['due_soon'] }}</p>@endif
                                        @if ($detail['untracked'])<p>{{ __('readiness.untracked') }}</p>@endif
                                    </li>
                                @endforeach
                                @foreach ($card['work_orders']->take(3) as $order)
                                    <li class="break-words [overflow-wrap:anywhere]"><strong>{{ $order->title }}</strong><p>{{ $order->assignee?->name ?? __('readiness.unassigned') }} · {{ __('readiness.order_'.$order->status) }}</p>@if ($order->due_at)<p>{{ $order->due_at->format('d/m/Y H:i') }}</p>@endif</li>
                                @endforeach
                            </ul>
                            @if ($card['work_orders']->count() > 3)<p class="mt-3 text-xs text-pm-muted">{{ __('readiness.more_orders', ['count' => $card['work_orders']->count() - 3]) }}</p>@endif
                        </details>
                    @endif
                    <div class="mt-4 flex flex-wrap gap-2 border-t border-pm-border pt-3">
                        <a class="pm-ghost-button" href="{{ route('tasks.index', array_filter(['vehicle_id' => $card['vehicle']->id, 'event_id' => $filters['event_id'] ?? null, 'bucket' => $card['urgent_tasks'] > 0 ? 'urgent' : 'todo'])) }}">{{ __('readiness.open_tasks') }}</a>
                        <a class="pm-ghost-button" href="{{ route('maintenance.index') }}">{{ __('readiness.maintenance') }}</a>
                        @if (!$card['configuration'])<a class="pm-ghost-button" href="{{ route('configurations.index') }}">{{ __('readiness.configurations') }}</a>@endif
                    </div>
                </article>
            @empty
                <p class="text-sm text-pm-muted lg:col-span-2">{{ __('readiness.empty') }}</p>
            @endforelse
        </div>
        {{ $readiness['vehicles']->links() }}
        <p class="text-xs leading-5 text-pm-muted">{{ __('readiness.limits') }}</p>
    </div>
</details>
