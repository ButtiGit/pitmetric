<x-layouts::app :title="$event->name">
    @php
        $it = app()->getLocale() === 'it';
        $canWrite = Gate::allows('team-write');
        $eventSpend = (int) $event->expenses->sum('amount_cents');
        $openTasks = $event->tasks->where('status', '!=', 'done')->count();
        $criticalTasks = $event->tasks->where('status', '!=', 'done')->where('priority', 'critical')->count();
        $eventStart = $event->start_date->copy()->startOfDay();
        $eventEnd = $event->end_date->copy()->endOfDay();
        $defaultSessionAt = now()->betweenIncluded($eventStart, $eventEnd) ? now() : $eventStart->copy()->addHours(9);
        $statusVariant = match ($event->status) {
            'active' => 'success',
            'completed' => 'info',
            'cancelled' => 'danger',
            default => 'warning',
        };
        $sortedSchedule = $event->scheduleItems->sortBy('starts_at');
        $sortedTasks = $event->tasks->sortBy(fn ($task) => ($task->status === 'done' ? '2' : ($task->priority === 'critical' ? '0' : '1')).'-'.($task->due_at?->format('YmdHis') ?? '99999999999999'));
        $sortedNotes = $event->eventNotes->sortByDesc('occurred_at');
        $sortedExpenses = $event->expenses->sortByDesc('occurred_at');
        $sortedSessions = $event->sessions->sortByDesc('started_at');
        $formatUsage = static function (int $value, \App\Models\UsageMetricType $metric): string {
            return match ($metric->key) {
                'distance' => number_format($value / 1000, 1, ',', '.').' km',
                'runtime' => number_format($value / 3600, 1, ',', '.').' h',
                default => number_format($value, 0, ',', '.').' '.$metric->display_unit,
            };
        };
        $scheduleStatusVariant = static fn (string $status): string => match ($status) {
            'live' => 'danger',
            'ready' => 'warning',
            'completed' => 'success',
            'cancelled' => 'neutral',
            default => 'info',
        };
        $noteLabels = $it
            ? ['technical' => 'Tecnica', 'driver_feedback' => 'Feedback pilota', 'incident' => 'Incidente', 'operations' => 'Operazioni']
            : ['technical' => 'Technical', 'driver_feedback' => 'Driver feedback', 'incident' => 'Incident', 'operations' => 'Operations'];
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 pb-24 sm:px-6 sm:pb-8 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1500px] space-y-5">
            @include('events.partials.overview')

            <section class="grid gap-5 2xl:grid-cols-[1.45fr_0.55fr]">
                @include('events.partials.trackside')
                @include('events.partials.operations-sidebar')
            </section>
        </div>

        @include('events.partials.mobile-actions')
    </div>
</x-layouts::app>
