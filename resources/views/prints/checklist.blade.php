<x-prints.layout :$kind :$workspace :$reference :$generatedAt :$backUrl>
    <dl class="print-facts">
        <div><dt>{{ __('prints.event') }}</dt><dd>{{ $entry->raceEvent?->name ?? '—' }}</dd></div>
        <div><dt>{{ __('prints.driver') }}</dt><dd>{{ $entry->driver?->display_name ?? '—' }} · #{{ $entry->entry_number ?: '—' }}</dd></div>
        <div><dt>{{ __('prints.vehicle') }}</dt><dd>{{ $vehicle?->name ?? '—' }} · {{ $vehicle?->identifier ?: '—' }}</dd></div>
        <div><dt>{{ __('checklist.assigned_configuration') }}</dt><dd>{{ $entry->configurationVersion?->configuration?->name ?? '—' }} · v{{ $entry->configurationVersion?->version_number ?? '—' }}</dd></div>
    </dl>
    <p class="print-warning">{{ __('checklist.scope') }}</p>
    <section>
        <h2>{{ __('checklist.recorded_state') }}: {{ __('readiness.'.($card['state'] ?? 'incomplete')) }}</h2>
        <p>{{ $assignedAligned ? __('checklist.aligned') : __('checklist.not_aligned') }}</p>
        @if ($card)
            <p>{{ __('readiness.overdue') }}: {{ $card['health']['overdue'] }} · {{ __('readiness.due_soon') }}: {{ $card['health']['due_soon'] }} · {{ __('readiness.tasks') }}: {{ $card['open_tasks'] }} · {{ __('readiness.orders') }}: {{ $card['work_orders']->count() }}</p>
            @foreach ($card['details'] as $detail)
                <p>{{ $detail['name'] }} · {{ __('readiness.overdue') }}: {{ $detail['overdue'] }} · {{ __('readiness.due_soon') }}: {{ $detail['due_soon'] }} · {{ __('readiness.untracked') }}: {{ $detail['untracked'] }}</p>
            @endforeach
        @else
            <p>{{ __('checklist.inactive') }}</p>
        @endif
    </section>
    <section>
        <h2>{{ __('checklist.manual') }}</h2>
        <table><thead><tr><th scope="col">{{ __('checklist.check') }}</th><th scope="col">{{ __('checklist.operator_time') }}</th></tr></thead><tbody>
            @foreach (__('checklist.items') as $item)
                <tr><td><span class="print-check-box" aria-hidden="true"></span> {{ $item }}</td><td>________________</td></tr>
            @endforeach
        </tbody></table>
    </section>
    <section>
        <h2>{{ __('prints.installed_components') }}</h2>
        <table><thead><tr><th scope="col">{{ __('prints.component') }}</th><th scope="col">{{ __('prints.position') }}</th><th scope="col">{{ __('prints.serial') }}</th></tr></thead><tbody>
            @forelse ($vehicle?->componentInstallations ?? [] as $installation)
                <tr><td>{{ $installation->component?->name ?? '—' }}</td><td>{{ $installation->position_or_role ?: '—' }}</td><td>{{ $installation->component?->serial_number ?: '—' }}</td></tr>
            @empty
                <tr><td colspan="3">{{ __('prints.no_components') }}</td></tr>
            @endforelse
        </tbody></table>
    </section>
    <section>
        <h2>{{ __('readiness.orders') }}</h2>
        @forelse ($card['work_orders'] ?? [] as $order)
            <p>{{ $order->title }} · {{ __('readiness.order_'.$order->status) }} · {{ $order->assignee?->name ?? __('readiness.unassigned') }} · {{ $order->due_at?->copy()->timezone('Europe/Rome')->format('d/m/Y H:i') ?? '—' }}</p>
        @empty
            <p>{{ __('checklist.no_orders') }}</p>
        @endforelse
    </section>
    <section><h2>{{ __('checklist.kart_tasks') }}</h2><x-prints.checklist-tasks :tasks="$tasks" /></section>
    <section><h2>{{ __('checklist.general_tasks') }}</h2><p class="print-caption">{{ __('checklist.general_help') }}</p><x-prints.checklist-tasks :tasks="$generalTasks" /></section>
    @if ($entry->notes)
        <section><h2>{{ __('prints.notes') }}</h2><p class="print-note">{{ $entry->notes }}</p></section>
    @endif
</x-prints.layout>
