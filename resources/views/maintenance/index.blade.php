<x-layouts::app :title="__('Maintenance')">
    @php
        $it = app()->getLocale() === 'it';
        $formatUsage = static function (int $value, \App\Models\UsageMetricType $metric): string {
            return match ($metric->key) {
                'distance' => number_format($value / 1000, 1, ',', '.').' km',
                'runtime' => number_format($value / 3600, 1, ',', '.').' h',
                default => number_format($value, 0, ',', '.').' '.$metric->display_unit,
            };
        };
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1360px] space-y-5">
            <div><p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">MAINTENANCE</p><x-pitmetric.page-header :title="$it ? 'Manutenzione basata sull’utilizzo' : 'Usage-based maintenance'" :description="$it ? 'Gli interventi azzerano l’intervallo corrente senza cancellare lo storico lifetime del componente.' : 'Services reset the current interval without erasing the component lifetime history.'" /></div>
            @if (session('status'))<div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <section class="pm-panel p-5 sm:p-6">
                <h2 class="text-lg font-black text-pm-text">{{ $it ? 'Nuovo piano manutenzione' : 'New maintenance schedule' }}</h2>
                <form method="POST" action="{{ route('maintenance.store') }}" class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">@csrf
                    <label class="grid gap-2 xl:col-span-2"><span class="pm-label">{{ $it ? 'Componente / metrica' : 'Component / metric' }}</span><select class="pm-input" name="component_tracker_id" required><option value="">{{ $it ? 'Seleziona' : 'Select' }}</option>@foreach ($trackers as $tracker)<option value="{{ $tracker->id }}">{{ $tracker->component->name }} · {{ $tracker->metric->name }} ({{ $tracker->metric->display_unit }})</option>@endforeach</select></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Nome intervento' : 'Service name' }}</span><input class="pm-input" name="name" required maxlength="120" placeholder="Engine rebuild"></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Intervallo' : 'Interval' }}</span><input class="pm-input" name="interval_display" type="number" required min="0.01" step="0.01"><span class="text-xs text-pm-muted">{{ $it ? 'Usa l’unità mostrata nella metrica.' : 'Use the unit shown by the metric.' }}</span></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Preavviso' : 'Warning before' }}</span><input class="pm-input" name="warning_display" type="number" min="0" step="0.01"></label>
                    <label class="grid gap-2 md:col-span-2 xl:col-span-3"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><input class="pm-input" name="notes" maxlength="2000"></label>
                    <div class="md:col-span-2 xl:col-span-4"><button class="pm-race-button" type="submit" @disabled($trackers->isEmpty())>{{ $it ? 'Crea piano' : 'Create schedule' }}</button></div>
                </form>
            </section>

            <section class="grid gap-4 lg:grid-cols-2">
                @forelse ($schedules as $schedule)
                    @php($state = $states[$schedule->id])
                    <article class="pm-panel p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-bold text-pm-accent">{{ $schedule->tracker->component->name }}</p><h2 class="mt-1 font-black text-pm-text">{{ $schedule->name }}</h2><p class="mt-1 text-sm text-pm-text-secondary">{{ $schedule->tracker->metric->name }}</p></div><x-pitmetric.status-badge :label="$state['status']" :variant="match ($state['status']) { 'overdue' => 'danger', 'due_soon' => 'warning', 'ok' => 'success', default => 'neutral' }" /></div>
                        <div class="mt-4 grid grid-cols-2 gap-3"><div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-[11px] uppercase tracking-[0.1em] text-pm-muted">{{ $it ? 'Da ultimo service' : 'Since service' }}</p><p class="mt-2 font-mono font-bold text-pm-text">{{ $formatUsage($state['used'], $schedule->tracker->metric) }}</p></div><div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-[11px] uppercase tracking-[0.1em] text-pm-muted">{{ $it ? 'Limite' : 'Limit' }}</p><p class="mt-2 font-mono font-bold text-pm-text">{{ $formatUsage($schedule->interval_value, $schedule->tracker->metric) }}</p></div></div>
                        <details class="mt-5 border-t border-pm-border pt-4"><summary class="cursor-pointer text-sm font-bold text-pm-text hover:text-pm-accent">{{ $it ? 'Registra intervento completato' : 'Record completed maintenance' }}</summary><form method="POST" action="{{ route('maintenance.complete', $schedule) }}" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf<label class="grid gap-2"><span class="pm-label">{{ $it ? 'Data e ora' : 'Date and time' }}</span><input class="pm-input" name="performed_at" type="datetime-local" required></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Costo (€)' : 'Cost (€)' }}</span><input class="pm-input" name="cost" type="number" min="0" step="0.01"></label><label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ $it ? 'Intervento' : 'Work performed' }}</span><input class="pm-input" name="description" required maxlength="180" value="{{ $schedule->name }}"></label><label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><input class="pm-input" name="notes" maxlength="2000"></label><div class="sm:col-span-2"><button class="pm-ghost-button" type="submit">{{ $it ? 'Completa e resetta intervallo' : 'Complete and reset interval' }}</button></div></form></details>
                    </article>
                @empty
                    <x-pitmetric.empty-state :title="$it ? 'Nessun piano manutenzione' : 'No maintenance schedules'" :description="$it ? 'Crea un piano su una metrica tracciata per ricevere uno stato affidabile.' : 'Create a schedule on a tracked metric to get a reliable maintenance status.'" />
                @endforelse
            </section>

            @if ($records->isNotEmpty())<section class="pm-panel p-5 sm:p-6"><h2 class="text-lg font-black text-pm-text">{{ $it ? 'Storico recente' : 'Recent history' }}</h2><div class="mt-4 space-y-3">@foreach ($records as $record)<div class="flex flex-col gap-1 rounded-xl border border-pm-border bg-pm-subtle p-3 sm:flex-row sm:items-center sm:justify-between"><div><p class="font-bold text-pm-text">{{ $record->component->name }} · {{ $record->description }}</p><p class="mt-1 text-xs text-pm-muted">{{ $record->performed_at->format('d/m/Y H:i') }}</p></div>@if ($record->cost_cents !== null)<p class="font-mono font-bold text-pm-text">€ {{ number_format($record->cost_cents / 100, 2, ',', '.') }}</p>@endif</div>@endforeach</div></section>@endif
        </div>
    </div>
</x-layouts::app>
