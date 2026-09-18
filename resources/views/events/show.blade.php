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
            <section class="overflow-hidden rounded-2xl border border-pm-border bg-pm-panel">
                <div class="border-b border-pm-border bg-pm-subtle px-5 py-3 sm:px-7">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="text-[11px] font-black uppercase tracking-[0.18em] text-pm-accent">TRACKSIDE MODE</span>
                            <x-pitmetric.status-badge :label="$event->status" :variant="$statusVariant" />
                        </div>
                        <a href="{{ route('events.index') }}" class="text-xs font-bold text-pm-muted hover:text-pm-text">{{ $it ? 'Tutti i weekend' : 'All weekends' }}</a>
                    </div>
                </div>

                <div class="p-5 sm:p-7">
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0">
                            <h1 class="text-3xl font-black tracking-[-0.035em] text-pm-text sm:text-4xl">{{ $event->name }}</h1>
                            <p class="mt-3 text-sm text-pm-text-secondary sm:text-base">{{ $event->start_date->format('d/m/Y') }} - {{ $event->end_date->format('d/m/Y') }} · {{ $event->circuitLayout?->circuit?->name }} · {{ $event->circuitLayout?->name }}</p>
                            @if ($event->championship || $event->round_label)
                                <p class="mt-1 text-sm text-pm-muted">{{ collect([$event->championship, $event->round_label])->filter()->join(' · ') }}</p>
                            @endif
                        </div>

                        @if ($canWrite)
                            <div class="flex flex-wrap gap-2">
                                <x-crud-modal id="edit-event" :title="$it ? 'Modifica weekend' : 'Edit weekend'" :trigger="$it ? 'Modifica weekend' : 'Edit weekend'" trigger-class="pm-ghost-button">
 <form method="POST" action="{{ route('events.update', $event) }}" class="grid gap-4 sm:grid-cols-2">@csrf @method('PUT')
 <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Nome weekend' : 'Weekend name' }}</span><input class="pm-input" name="name" type="text" value="{{ $event->name }}" required maxlength="140"></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Campionato' : 'Championship' }}</span><input class="pm-input" name="championship" type="text" value="{{ $event->championship }}" maxlength="120"></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Tappa' : 'Round' }}</span><input class="pm-input" name="round_label" type="text" value="{{ $event->round_label }}" maxlength="80"></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="4000">{{ $event->notes }}</textarea></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Inizio' : 'Start' }}</span><input class="pm-input" name="start_date" type="date" value="{{ $event->start_date->format('Y-m-d') }}" required></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Fine' : 'End' }}</span><input class="pm-input" name="end_date" type="date" value="{{ $event->end_date->format('Y-m-d') }}" required></label>
 <div class="sm:col-span-2 flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Salva modifiche' : 'Save changes' }}</button></div>
 </form></x-crud-modal>
                                <x-crud-modal id="event-status" :title="$it ? 'Stato weekend' : 'Weekend status'" :trigger="$it ? 'Cambia stato' : 'Change status'" trigger-class="pm-ghost-button">
                                    @can('team-write')<form method="POST" action="{{ route('events.status', $event) }}" class="grid gap-4">
                                        @csrf @method('PATCH')
                                        <label class="grid gap-2"><span class="pm-label">Status</span><select class="pm-input" name="status"><option value="planned" @selected($event->status === 'planned')>{{ $it ? 'Pianificato' : 'Planned' }}</option><option value="active" @selected($event->status === 'active')>{{ $it ? 'In corso' : 'Active' }}</option><option value="completed" @selected($event->status === 'completed')>{{ $it ? 'Completato' : 'Completed' }}</option><option value="cancelled" @selected($event->status === 'cancelled')>{{ $it ? 'Annullato' : 'Cancelled' }}</option></select></label>
                                        <div class="flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Aggiorna' : 'Update' }}</button></div>
                                    </form>@endcan
                                </x-crud-modal>

                                <x-crud-modal id="add-event-entry" :title="$it ? 'Aggiungi entry' : 'Add entry'" :description="$it ? 'Pilota, mezzo, configurazione base e quota in un solo passaggio.' : 'Driver, vehicle, baseline configuration and fee in one step.'" :trigger="$it ? '+ Entry' : '+ Entry'">
                                    @can('team-write')<form method="POST" action="{{ route('events.entries.store', $event) }}" class="grid gap-4 md:grid-cols-2">
                                        @csrf
                                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Pilota' : 'Driver' }}</span><select class="pm-input" name="driver_id" required><option value="">--</option>@foreach ($drivers as $driver)<option value="{{ $driver->id }}">{{ $driver->display_name }}{{ $driver->racing_number ? ' #'.$driver->racing_number : '' }}</option>@endforeach</select></label>
                                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Mezzo' : 'Vehicle' }}</span><select class="pm-input" name="vehicle_id" required><option value="">--</option>@foreach ($vehicles as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->name }}</option>@endforeach</select></label>
                                        <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Configurazione base' : 'Baseline configuration' }}</span><select class="pm-input" name="configuration_version_id" data-pm-vehicle-options required><option value="">--</option>@foreach ($versions as $version)<option value="{{ $version->id }}" data-vehicle-id="{{ $version->configuration->vehicle_id }}">{{ $version->configuration->vehicle->name }} · {{ $version->configuration->name }} v{{ $version->version_number }}</option>@endforeach</select></label>
                                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Numero entry' : 'Entry number' }}</span><input class="pm-input" name="entry_number" maxlength="20"></label>
                                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Quota / costo (€)' : 'Fee / cost (€)' }}</span><input class="pm-input" name="entry_cost" type="number" min="0" max="1000000" step="0.01"><span class="text-xs text-pm-muted">{{ $it ? 'Opzionale, viene registrata nei Costi.' : 'Optional, recorded in Expenses.' }}</span></label>
                                        <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="2000"></textarea></label>
                                        @if ($drivers->isEmpty() || $vehicles->isEmpty() || $versions->isEmpty())<div class="md:col-span-2 rounded-lg border border-pm-warning/30 p-3 text-sm text-pm-text-secondary">{{ $it ? 'Per iscrivere un equipaggio servono pilota, mezzo e configurazione.' : 'An entry needs a driver, vehicle and configuration.' }} <a class="text-pm-accent underline" href="{{ route('events.index') }}#create-driver">{{ $it ? 'Piloti' : 'Drivers' }}</a> · <a class="text-pm-accent underline" href="{{ route('garage.index') }}#create-vehicle">{{ $it ? 'Mezzi' : 'Vehicles' }}</a> · <a class="text-pm-accent underline" href="{{ route('configurations.index') }}#create-configuration">{{ $it ? 'Configurazioni' : 'Configurations' }}</a></div>@endif
                                        <div class="flex justify-end md:col-span-2"><button class="pm-race-button" type="submit" @disabled($drivers->isEmpty() || $vehicles->isEmpty() || $versions->isEmpty())>{{ $it ? 'Aggiungi entry' : 'Add entry' }}</button></div>
                                    </form>@endcan
                                </x-crud-modal>
                            </div>
                        @endif
                    </div>

                    @if ($event->notes)
                        <div class="mt-5 rounded-xl border border-pm-border bg-pm-subtle p-4 text-sm leading-6 text-pm-text-secondary">{{ $event->notes }}</div>
                    @endif
                </div>
            </section>

            @if (session('status'))<div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>@endif
            @if (session('error'))<div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle px-4 py-3 text-sm font-semibold text-pm-danger">{{ session('error') }}</div>@endif
            @if ((int) session('maintenance_attention', 0) > 0)<div class="rounded-xl border border-pm-warning/30 bg-pm-warning-subtle px-4 py-3 text-sm font-semibold text-pm-warning">{{ $it ? session('maintenance_attention').' elementi manutenzione richiedono attenzione dopo la sessione.' : session('maintenance_attention').' maintenance items need attention after the session.' }}</div>@endif
            @if ($errors->any())<div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <x-pitmetric.metric-card :label="$it ? 'Prossima attività' : 'Next activity'" :value="$nextScheduleItem ? $nextScheduleItem->starts_at->format('H:i') : '—'" :support="$nextScheduleItem ? ucfirst($nextScheduleItem->session_type).' · '.($nextScheduleItem->eventEntry?->driver?->display_name ?? ($it ? 'Team' : 'Team')) : ($it ? 'Nessuna sessione pianificata' : 'No session scheduled')" :variant="$nextScheduleItem?->status === 'live' ? 'danger' : ($nextScheduleItem?->status === 'ready' ? 'warning' : 'info')" />
                <x-pitmetric.metric-card :label="$it ? 'Lavori aperti' : 'Open work'" :value="(string) $openTasks" :support="$criticalTasks > 0 ? ($it ? $criticalTasks.' critici' : $criticalTasks.' critical') : ($it ? 'Nessun blocco critico' : 'No critical blockers')" :variant="$criticalTasks > 0 ? 'danger' : ($openTasks > 0 ? 'warning' : 'success')" />
                <x-pitmetric.metric-card :label="$it ? 'Manutenzione' : 'Maintenance'" :value="(string) $maintenanceSummary['attention']" :support="$it ? 'elementi da controllare sulle entry' : 'items needing attention on entries'" :variant="$maintenanceSummary['attention'] > 0 ? 'warning' : 'success'" />
                <x-pitmetric.metric-card :label="$it ? 'Costo evento' : 'Event cost'" :value="'€ '.number_format($eventSpend / 100, 2, ',', '.')" :support="$it ? 'operazioni e spese manuali' : 'operations and manual expenses'" />
            </section>

            <section class="grid gap-5 2xl:grid-cols-[1.45fr_0.55fr]">
                <div class="space-y-5">
                    <section class="pm-panel p-5 sm:p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">LIVE PLAN</p>
                                <h2 class="mt-2 text-xl font-black text-pm-text">{{ $it ? 'Programma Trackside' : 'Trackside schedule' }}</h2>
                                <p class="mt-1 text-sm text-pm-muted">{{ $it ? 'Quello che deve succedere dopo, non solo quello che è già successo.' : 'What needs to happen next, not only what already happened.' }}</p>
                            </div>

                            @if ($canWrite)
                                <x-crud-modal id="add-schedule-item" :title="$it ? 'Pianifica sessione' : 'Schedule session'" :description="$it ? 'Aggiungi la prossima attività del weekend e assegnala a una entry quando serve.' : 'Add the next weekend activity and assign it to an entry when needed.'" :trigger="$it ? '+ Pianifica' : '+ Schedule'">
                                    @can('team-write')<form method="POST" action="{{ route('events.schedule.store', $event) }}" class="grid gap-4 md:grid-cols-2">
                                        @csrf
                                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Tipo sessione' : 'Session type' }}</span><select class="pm-input" name="session_type" required><option value="practice">Practice</option><option value="qualifying">Qualifying</option><option value="heat">Heat</option><option value="prefinal">Prefinal</option><option value="final">Final</option><option value="race">Race</option><option value="test">Test</option></select></label>
                                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Entry' : 'Entry' }}</span><select class="pm-input" name="event_entry_id"><option value="">{{ $it ? 'Attività generale team' : 'Whole-team activity' }}</option>@foreach ($event->entries as $entry)<option value="{{ $entry->id }}">{{ $entry->driver->display_name }} · {{ $entry->vehicle->name }}</option>@endforeach</select></label>
                                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Data e ora previste' : 'Planned date and time' }}</span><input class="pm-input" type="datetime-local" name="starts_at" required value="{{ $defaultSessionAt->format('Y-m-d\TH:i') }}"></label>
                                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Durata prevista (min)' : 'Planned duration (min)' }}</span><input class="pm-input" type="number" name="duration_minutes" min="1" max="1440"></label>
                                        <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Etichetta' : 'Label' }}</span><input class="pm-input" name="label" maxlength="120" placeholder="{{ $it ? 'Es. Qualifica gruppo B' : 'e.g. Qualifying group B' }}"></label>
                                        <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Note operative' : 'Operational notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="2000"></textarea></label>
                                        <div class="flex justify-end md:col-span-2"><button class="pm-race-button" type="submit">{{ $it ? 'Aggiungi al programma' : 'Add to schedule' }}</button></div>
                                    </form>@endcan
                                </x-crud-modal>
                            @endif
                        </div>

                        @if ($nextScheduleItem)
                            <div class="mt-5 rounded-2xl border {{ $nextScheduleItem->status === 'live' ? 'border-pm-danger/40 bg-pm-danger-subtle' : 'border-pm-accent/35 bg-pm-accent/5' }} p-5">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2"><span class="text-xs font-black uppercase tracking-[0.14em] text-pm-accent">{{ $nextScheduleItem->status === 'live' ? ($it ? 'IN PISTA' : 'ON TRACK') : ($it ? 'PROSSIMA' : 'NEXT') }}</span><x-pitmetric.status-badge :label="$nextScheduleItem->status" :variant="$scheduleStatusVariant($nextScheduleItem->status)" /></div>
                                        <p class="mt-2 text-2xl font-black text-pm-text">{{ $nextScheduleItem->starts_at->format('H:i') }} · {{ $nextScheduleItem->label ?: ucfirst($nextScheduleItem->session_type) }}</p>
                                        <p class="mt-1 text-sm text-pm-text-secondary">{{ $nextScheduleItem->starts_at->format('d/m/Y') }}{{ $nextScheduleItem->duration_minutes ? ' · '.$nextScheduleItem->duration_minutes.' min' : '' }}{{ $nextScheduleItem->eventEntry ? ' · '.$nextScheduleItem->eventEntry->driver->display_name.' / '.$nextScheduleItem->eventEntry->vehicle->name : ' · Team' }}</p>
                                    </div>
                                    @if ($canWrite && $nextScheduleItem->eventEntry && ! in_array($nextScheduleItem->status, ['completed', 'cancelled'], true))
                                        @php($nextEntryVersions = $versions->filter(fn ($version) => (int) $version->configuration->vehicle_id === (int) $nextScheduleItem->eventEntry->vehicle_id))
                                        <x-crud-modal id="record-next-schedule" :title="$it ? 'Registra sessione completata' : 'Record completed session'" :description="($nextScheduleItem->eventEntry->driver->display_name).' · '.ucfirst($nextScheduleItem->session_type)" :trigger="$it ? 'Registra risultato' : 'Record result'">
                                            @can('team-write')<form method="POST" action="{{ route('sessions.store') }}" class="grid gap-4 md:grid-cols-2">
                                                @csrf
                                                <input type="hidden" name="event_id" value="{{ $event->id }}"><input type="hidden" name="event_entry_id" value="{{ $nextScheduleItem->event_entry_id }}"><input type="hidden" name="schedule_item_id" value="{{ $nextScheduleItem->id }}"><input type="hidden" name="session_type" value="{{ $nextScheduleItem->session_type }}">
                                                <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Configurazione usata' : 'Configuration used' }}</span><select class="pm-input" name="configuration_version_id" data-pm-vehicle-options required>@foreach ($nextEntryVersions as $version)<option value="{{ $version->id }}" data-vehicle-id="{{ $version->configuration->vehicle_id }}" @selected($version->id === $nextScheduleItem->eventEntry->configuration_version_id)>{{ $version->configuration->name }} v{{ $version->version_number }}</option>@endforeach</select></label>
                                                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Partenza effettiva' : 'Actual start' }}</span><input class="pm-input" type="datetime-local" name="started_at" required value="{{ $nextScheduleItem->starts_at->format('Y-m-d\TH:i') }}"></label>
                                                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Giri' : 'Laps' }}</span><input class="pm-input" type="number" name="completed_laps" min="0" max="10000"></label>
                                                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Durata effettiva (min)' : 'Actual duration (min)' }}</span><input class="pm-input" type="number" name="duration_minutes" min="0" max="1440" step="0.1" value="{{ $nextScheduleItem->duration_minutes }}"></label>
                                                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Costo sessione (€)' : 'Session cost (€)' }}</span><input class="pm-input" type="number" name="session_cost" min="0" max="1000000" step="0.01"></label>
                                                <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Note sessione' : 'Session notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="4000"></textarea></label>
                                                <div class="flex justify-end md:col-span-2"><button class="pm-race-button" type="submit">{{ $it ? 'Salva e chiudi attività' : 'Save and complete activity' }}</button></div>
                                            </form>@endcan
                                        </x-crud-modal>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="mt-5 space-y-2">
                            @forelse ($sortedSchedule as $item)
                                <article class="rounded-xl border border-pm-border bg-pm-subtle p-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2"><span class="font-mono text-sm font-black text-pm-text">{{ $item->starts_at->format('d/m H:i') }}</span><x-pitmetric.status-badge :label="$item->status" :variant="$scheduleStatusVariant($item->status)" /></div>
                                            <p class="mt-1 font-bold text-pm-text">{{ $item->label ?: ucfirst($item->session_type) }}</p>
                                            <p class="mt-1 text-xs text-pm-muted">{{ ucfirst($item->session_type) }}{{ $item->duration_minutes ? ' · '.$item->duration_minutes.' min' : '' }}{{ $item->eventEntry ? ' · '.$item->eventEntry->driver->display_name.' / '.$item->eventEntry->vehicle->name : ' · Team' }}{{ $item->session_id ? ' · '.($it ? 'sessione registrata' : 'session recorded') : '' }}</p>
                                            @if ($item->notes)<p class="mt-2 text-xs leading-5 text-pm-text-secondary">{{ $item->notes }}</p>@endif
                                        </div>

                                        @if ($canWrite)
                                            <x-crud-modal id="edit-schedule-{{ $item->id }}" :title="$it ? 'Aggiorna attività' : 'Update activity'" :description="$item->label ?: ucfirst($item->session_type)" :trigger="$it ? 'Gestisci' : 'Manage'" trigger-class="pm-ghost-button">
                                                @can('team-write')<form method="POST" action="{{ route('events.schedule.update', $item) }}" class="grid gap-4 md:grid-cols-2">
                                                    @csrf @method('PATCH')
                                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Tipo' : 'Type' }}</span><select class="pm-input" name="session_type" required>@foreach (['practice','qualifying','heat','prefinal','final','race','test'] as $type)<option value="{{ $type }}" @selected($item->session_type === $type)>{{ ucfirst($type) }}</option>@endforeach</select></label>
                                                    <label class="grid gap-2"><span class="pm-label">Status</span><select class="pm-input" name="status" required>@foreach (['planned','ready','live','completed','cancelled'] as $status)<option value="{{ $status }}" @selected($item->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Entry' : 'Entry' }}</span><select class="pm-input" name="event_entry_id"><option value="">Team</option>@foreach ($event->entries as $entry)<option value="{{ $entry->id }}" @selected($item->event_entry_id === $entry->id)>{{ $entry->driver->display_name }} · {{ $entry->vehicle->name }}</option>@endforeach</select></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Ora prevista' : 'Planned time' }}</span><input class="pm-input" type="datetime-local" name="starts_at" required value="{{ $item->starts_at->format('Y-m-d\TH:i') }}"></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Durata (min)' : 'Duration (min)' }}</span><input class="pm-input" type="number" name="duration_minutes" min="1" max="1440" value="{{ $item->duration_minutes }}"></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Etichetta' : 'Label' }}</span><input class="pm-input" name="label" maxlength="120" value="{{ $item->label }}"></label>
                                                    <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="2000">{{ $item->notes }}</textarea></label>
                                                    <div class="flex justify-end md:col-span-2"><button class="pm-race-button" type="submit">{{ $it ? 'Salva' : 'Save' }}</button></div>
                                                </form>@endcan
                                                @if (! $item->session_id)
                                                    @can('team-write')<form method="POST" action="{{ route('events.schedule.destroy', $item) }}" class="mt-5 border-t border-pm-border pt-4" onsubmit="return confirm(@js($it ? 'Rimuovere questa attività dal programma?' : 'Remove this activity from the schedule?'))">@csrf @method('DELETE')<button class="text-xs font-bold text-pm-danger hover:underline" type="submit">{{ $it ? 'Rimuovi dal programma' : 'Remove from schedule' }}</button></form>@endcan
                                                @endif
                                            </x-crud-modal>
                                        @endif
                                    </div>
                                </article>
                            @empty
                                <x-pitmetric.empty-state :title="$it ? 'Programma vuoto' : 'Schedule is empty'" :description="$it ? 'Pianifica le sessioni prima del weekend per vedere sempre cosa viene dopo.' : 'Schedule sessions before the weekend so the team always knows what comes next.'" />
                            @endforelse
                        </div>
                    </section>

                    <section class="pm-panel p-5 sm:p-6">
                        <div class="flex items-end justify-between gap-3"><div><p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-accent">ENTRIES</p><h2 class="mt-2 text-xl font-black text-pm-text">{{ $it ? 'Piloti e mezzi' : 'Drivers and vehicles' }}</h2></div><span class="text-sm text-pm-muted">{{ $event->entries->count() }}</span></div>
                        <div class="mt-5 grid gap-4 xl:grid-cols-2">
                            @forelse ($event->entries as $entry)
                                @php($entryVersions = $versions->filter(fn ($version) => (int) $version->configuration->vehicle_id === (int) $entry->vehicle_id))
                                <article class="rounded-xl border border-pm-border bg-pm-subtle p-4 sm:p-5">
                                    <div class="flex items-start justify-between gap-4"><div><div class="flex flex-wrap items-center gap-2"><h3 class="font-black text-pm-text">{{ $entry->driver->display_name }}</h3>@if ($entry->entry_number)<span class="rounded-lg border border-pm-border px-2 py-1 font-mono text-xs font-bold text-pm-text">#{{ $entry->entry_number }}</span>@endif</div><p class="mt-1 text-sm font-semibold text-pm-text-secondary">{{ $entry->vehicle->name }}</p><p class="mt-1 text-xs text-pm-muted">{{ $entry->configurationVersion->configuration->name }} v{{ $entry->configurationVersion->version_number }} · {{ $entry->configurationVersion->components->count() }} {{ $it ? 'componenti' : 'components' }}</p></div><span class="text-xs font-bold text-pm-muted">{{ $entry->sessions->count() }} {{ $it ? 'sessioni' : 'sessions' }}</span></div>
                                    @if ($entry->notes)<p class="mt-3 text-xs leading-5 text-pm-text-secondary">{{ $entry->notes }}</p>@endif

                                    @if ($canWrite)
                                        <div class="mt-4">
                                            <x-crud-modal id="entry-session-{{ $entry->id }}" :title="$it ? 'Registra sessione non pianificata' : 'Record unscheduled session'" :description="$entry->driver->display_name.' · '.$entry->vehicle->name" :trigger="$it ? '+ Sessione' : '+ Session'" trigger-class="pm-ghost-button">
                                                @can('team-write')<form method="POST" action="{{ route('sessions.store') }}" class="grid gap-4 md:grid-cols-2">
                                                    @csrf
                                                    <input type="hidden" name="event_id" value="{{ $event->id }}"><input type="hidden" name="event_entry_id" value="{{ $entry->id }}">
                                                    <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Configurazione' : 'Configuration' }}</span><select class="pm-input" name="configuration_version_id" data-pm-vehicle-options required>@foreach ($entryVersions as $version)<option value="{{ $version->id }}" data-vehicle-id="{{ $version->configuration->vehicle_id }}" @selected($version->id === $entry->configuration_version_id)>{{ $version->configuration->name }} v{{ $version->version_number }}</option>@endforeach</select></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Tipo' : 'Type' }}</span><select class="pm-input" name="session_type" required><option value="practice">Practice</option><option value="qualifying">Qualifying</option><option value="heat">Heat</option><option value="prefinal">Prefinal</option><option value="final">Final</option><option value="race">Race</option><option value="test">Test</option></select></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Data e ora' : 'Date and time' }}</span><input class="pm-input" type="datetime-local" name="started_at" required value="{{ $defaultSessionAt->format('Y-m-d\TH:i') }}"></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Giri' : 'Laps' }}</span><input class="pm-input" type="number" name="completed_laps" min="0" max="10000"></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Durata (min)' : 'Duration (min)' }}</span><input class="pm-input" type="number" name="duration_minutes" min="0" max="1440" step="0.1"></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Costo (€)' : 'Cost (€)' }}</span><input class="pm-input" type="number" name="session_cost" min="0" max="1000000" step="0.01"></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Descrizione costo' : 'Cost description' }}</span><input class="pm-input" name="cost_description" maxlength="180"></label>
                                                    <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="4000"></textarea></label>
                                                    <div class="flex justify-end md:col-span-2"><button class="pm-race-button" type="submit">{{ $it ? 'Registra sessione' : 'Record session' }}</button></div>
                                                </form>@endcan
                                            </x-crud-modal>
                                        </div>
                                    @endif
                                </article>
                            @empty
                                <div class="xl:col-span-2"><x-pitmetric.empty-state :title="$it ? 'Nessuna entry' : 'No entries'" :description="$it ? 'Aggiungi pilota, mezzo e configurazione per iniziare il weekend.' : 'Add a driver, vehicle and configuration to start the weekend.'" /></div>
                            @endforelse
                        </div>
                    </section>

                    <section class="pm-panel p-5 sm:p-6">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-muted">SESSION HISTORY</p>
                        <h2 class="mt-2 text-xl font-black text-pm-text">{{ $it ? 'Cronologia reale' : 'Actual session history' }}</h2>
                        <div class="mt-5 space-y-3">
                            @forelse ($sortedSessions as $sessionItem)
                                <article class="rounded-xl border {{ (string) request('recorded') === (string) $sessionItem->id ? 'border-pm-accent/50' : 'border-pm-border' }} bg-pm-subtle p-4">
                                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"><div><div class="flex flex-wrap items-center gap-2"><h3 class="font-black text-pm-text">{{ ucfirst($sessionItem->session_type) }} · {{ $sessionItem->eventEntry?->driver?->display_name ?? $sessionItem->vehicle->name }}</h3><x-pitmetric.status-badge :label="$sessionItem->status" variant="success" /></div><p class="mt-1 text-sm text-pm-text-secondary">{{ $sessionItem->vehicle->name }} · {{ $sessionItem->configurationVersion->configuration->name }} v{{ $sessionItem->configurationVersion->version_number }}</p><p class="mt-1 text-xs text-pm-muted">{{ $sessionItem->started_at?->format('d/m/Y H:i') }}</p></div><div class="flex flex-wrap gap-2">@foreach ($sessionItem->usageValues as $usage)<span class="rounded-lg border border-pm-border px-2 py-1 font-mono text-xs text-pm-text">{{ $usage->metric->name }}: {{ $formatUsage($usage->value, $usage->metric) }}</span>@endforeach</div></div>
                                </article>
                            @empty
                                <p class="rounded-xl border border-dashed border-pm-border p-4 text-sm text-pm-muted">{{ $it ? 'Nessuna sessione registrata.' : 'No sessions recorded.' }}</p>
                            @endforelse
                        </div>
                    </section>
                </div>

                <aside class="space-y-5">
                    <section class="pm-panel p-5">
                        <div class="flex items-end justify-between gap-3"><div><p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-accent">READINESS</p><h2 class="mt-2 text-lg font-black text-pm-text">{{ $it ? 'Stato manutenzione' : 'Maintenance state' }}</h2></div><a href="{{ route('maintenance.index') }}" class="text-xs font-bold text-pm-accent hover:underline">{{ $it ? 'Apri' : 'Open' }}</a></div>
                        <div class="mt-4 grid grid-cols-3 gap-2 text-center"><div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-xl font-black text-pm-text">{{ $maintenanceSummary['ok'] }}</p><p class="mt-1 text-[10px] font-bold uppercase text-pm-muted">OK</p></div><div class="rounded-xl border border-pm-warning/25 bg-pm-warning-subtle p-3"><p class="text-xl font-black text-pm-warning">{{ $maintenanceSummary['due_soon'] }}</p><p class="mt-1 text-[10px] font-bold uppercase text-pm-warning">Soon</p></div><div class="rounded-xl border border-pm-danger/25 bg-pm-danger-subtle p-3"><p class="text-xl font-black text-pm-danger">{{ $maintenanceSummary['overdue'] }}</p><p class="mt-1 text-[10px] font-bold uppercase text-pm-danger">Overdue</p></div></div>
                        <div class="mt-4 space-y-2">
                            @forelse ($attentionMaintenance as $schedule)
                                @php($state = $maintenanceStates[$schedule->id] ?? null)
                                <div class="rounded-xl border border-pm-warning/25 bg-pm-warning-subtle p-3"><div class="flex items-start justify-between gap-3"><div><p class="font-bold text-pm-text">{{ $schedule->tracker->component->name }}</p><p class="mt-1 text-xs text-pm-muted">{{ $schedule->name }}</p></div><x-pitmetric.status-badge :label="$state['status'] ?? 'untracked'" :variant="($state['status'] ?? '') === 'overdue' ? 'danger' : 'warning'" /></div>@if ($state)<p class="mt-2 text-xs text-pm-text-secondary">{{ $it ? 'Utilizzo dal service' : 'Usage since service' }}: {{ $formatUsage((int) $state['used'], $schedule->tracker->metric) }}</p>@endif</div>
                            @empty
                                <p class="rounded-xl border border-pm-success/20 bg-pm-success-subtle p-3 text-sm font-semibold text-pm-success">{{ $it ? 'Nessun componente delle entry richiede attenzione.' : 'No entry component currently needs attention.' }}</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="pm-panel p-5">
                        <div class="flex items-end justify-between gap-3"><div><p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-accent">{{ $it ? 'LAVORI' : 'WORK' }}</p><h2 class="mt-2 text-lg font-black text-pm-text">{{ $it ? 'Task del weekend' : 'Weekend tasks' }}</h2></div>
                            @if ($canWrite)<x-crud-modal id="add-event-task" :title="$it ? 'Nuovo lavoro' : 'New task'" :trigger="$it ? '+ Lavoro' : '+ Task'" trigger-class="pm-ghost-button">@can('team-write')<form method="POST" action="{{ route('events.tasks.store', $event) }}" class="grid gap-4">@csrf<label class="grid gap-2"><span class="pm-label">{{ $it ? 'Titolo' : 'Title' }}</span><input class="pm-input" name="title" maxlength="160" required></label><div class="grid gap-4 sm:grid-cols-2"><label class="grid gap-2"><span class="pm-label">Priority</span><select class="pm-input" name="priority"><option value="normal">Normal</option><option value="low">Low</option><option value="high">High</option><option value="critical">Critical</option></select></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Assegnazione' : 'Assignment' }}</span><select class="pm-input" name="event_entry_id"><option value="">Team</option>@foreach ($event->entries as $entry)<option value="{{ $entry->id }}">{{ $entry->driver->display_name }} / {{ $entry->vehicle->name }}</option>@endforeach</select></label></div><label class="grid gap-2"><span class="pm-label">Due</span><input class="pm-input" type="datetime-local" name="due_at"></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Dettagli' : 'Details' }}</span><textarea class="pm-input min-h-20" name="description" maxlength="3000"></textarea></label><div class="flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Aggiungi' : 'Add' }}</button></div></form>@endcan</x-crud-modal>@endif
                        </div>
                        <div class="mt-5 space-y-2">
                            @forelse ($sortedTasks as $task)
                                <div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><div class="flex items-start justify-between gap-3"><div><p class="font-bold text-pm-text {{ $task->status === 'done' ? 'line-through opacity-60' : '' }}">{{ $task->title }}</p><p class="mt-1 text-xs text-pm-muted">{{ ucfirst($task->priority) }}{{ $task->eventEntry ? ' · '.$task->eventEntry->driver->display_name : '' }}{{ $task->due_at ? ' · '.$task->due_at->format('d/m H:i') : '' }}</p></div><x-pitmetric.status-badge :label="$task->status" :variant="$task->status === 'done' ? 'success' : ($task->priority === 'critical' ? 'danger' : ($task->priority === 'high' ? 'warning' : 'neutral'))" /></div>
                                    @if ($canWrite)<div class="mt-3"><x-crud-modal id="update-task-{{ $task->id }}" :title="$it ? 'Aggiorna lavoro' : 'Update task'" :description="$task->title" :trigger="$it ? 'Aggiorna' : 'Update'" trigger-class="text-xs font-bold text-pm-accent hover:underline">@can('team-write')<form method="POST" action="{{ route('events.tasks.update', $task) }}" class="grid gap-4">@csrf @method('PATCH')<label class="grid gap-2"><span class="pm-label">Status</span><select class="pm-input" name="status"><option value="todo" @selected($task->status === 'todo')>Todo</option><option value="in_progress" @selected($task->status === 'in_progress')>In progress</option><option value="done" @selected($task->status === 'done')>Done</option></select></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Costo completamento (€)' : 'Completion cost (€)' }}</span><input class="pm-input" name="operation_cost" type="number" min="0" max="1000000" step="0.01"></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Descrizione costo' : 'Cost description' }}</span><input class="pm-input" name="cost_description" maxlength="180" placeholder="{{ $task->title }}"></label><div class="flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Salva' : 'Save' }}</button></div></form>@endcan</x-crud-modal></div>@endif
                                </div>
                            @empty<p class="text-sm text-pm-muted">{{ $it ? 'Nessun lavoro aperto.' : 'No tasks yet.' }}</p>@endforelse
                        </div>
                    </section>

                    <section class="pm-panel p-5">
                        <div class="flex items-end justify-between gap-3"><div><p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-muted">LOG</p><h2 class="mt-2 text-lg font-black text-pm-text">{{ $it ? 'Diario Trackside' : 'Trackside log' }}</h2></div>
                            @if ($canWrite)<x-crud-modal id="add-event-note" :title="$it ? 'Aggiungi nota' : 'Add note'" :trigger="$it ? '+ Nota' : '+ Note'" trigger-class="pm-ghost-button">@can('team-write')<form method="POST" action="{{ route('events.notes.store', $event) }}" class="grid gap-4">@csrf<label class="grid gap-2"><span class="pm-label">{{ $it ? 'Tipo nota' : 'Note type' }}</span><select class="pm-input" name="kind" required><option value="technical">{{ $it ? 'Tecnica' : 'Technical' }}</option><option value="driver_feedback">{{ $it ? 'Feedback pilota' : 'Driver feedback' }}</option><option value="incident">{{ $it ? 'Incidente' : 'Incident' }}</option><option value="operations">{{ $it ? 'Operazioni' : 'Operations' }}</option></select></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Nota' : 'Note' }}</span><textarea class="pm-input min-h-28" name="body" maxlength="5000" required></textarea></label><div class="grid gap-4 sm:grid-cols-2"><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Riferimento' : 'Reference' }}</span><select class="pm-input" name="event_entry_id"><option value="">{{ $it ? 'Generale' : 'General' }}</option>@foreach ($event->entries as $entry)<option value="{{ $entry->id }}">{{ $entry->driver->display_name }}</option>@endforeach</select></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Data e ora' : 'Date and time' }}</span><input class="pm-input" type="datetime-local" name="occurred_at" required value="{{ now()->format('Y-m-d\TH:i') }}"></label></div><div class="flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Aggiungi nota' : 'Add note' }}</button></div></form>@endcan</x-crud-modal>@endif
                        </div>
                        <div class="mt-5 space-y-2">@forelse ($sortedNotes as $note)<div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><div class="flex flex-wrap items-center gap-2"><span class="text-[10px] font-black uppercase tracking-[0.12em] text-pm-accent">{{ $noteLabels[$note->kind] ?? ucfirst($note->kind) }}</span><span class="text-xs text-pm-muted">{{ $note->occurred_at->format('d/m H:i') }}{{ $note->eventEntry ? ' · '.$note->eventEntry->driver->display_name : '' }}</span></div><p class="mt-2 text-sm leading-6 text-pm-text-secondary">{{ $note->body }}</p></div>@empty<p class="text-sm text-pm-muted">{{ $it ? 'Nessuna nota.' : 'No notes yet.' }}</p>@endforelse</div>
                    </section>

                    <section class="pm-panel p-5">
                        <div class="flex items-end justify-between gap-3"><div><p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'COSTI' : 'COSTS' }}</p><h2 class="mt-2 text-lg font-black text-pm-text">{{ $it ? 'Spese evento' : 'Event expenses' }}</h2></div><div class="text-right"><p class="text-xl font-black text-pm-text">€ {{ number_format($eventSpend / 100, 2, ',', '.') }}</p>@if ($canWrite)<x-crud-modal id="add-event-expense" :title="$it ? 'Spesa manuale' : 'Manual expense'" :trigger="$it ? '+ Spesa' : '+ Expense'" trigger-class="mt-2 text-xs font-bold text-pm-accent hover:underline">@can('team-write')<form method="POST" action="{{ route('events.expenses.store', $event) }}" class="grid gap-4 sm:grid-cols-2">@csrf<label class="grid gap-2"><span class="pm-label">{{ $it ? 'Importo (€)' : 'Amount (€)' }}</span><input class="pm-input" type="number" name="amount" min="0.01" max="1000000" step="0.01" required></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Categoria' : 'Category' }}</span><input class="pm-input" name="category" maxlength="80" required></label><label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ $it ? 'Descrizione' : 'Description' }}</span><input class="pm-input" name="description" maxlength="180" required></label><label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ $it ? 'Data e ora' : 'Date and time' }}</span><input class="pm-input" type="datetime-local" name="occurred_at" required value="{{ now()->format('Y-m-d\TH:i') }}"></label><div class="flex justify-end sm:col-span-2"><button class="pm-race-button" type="submit">{{ $it ? 'Registra' : 'Record' }}</button></div></form>@endcan</x-crud-modal>@endif</div></div>
                        <div class="mt-5 space-y-2">@forelse ($sortedExpenses->take(8) as $expense)<div class="flex items-start justify-between gap-3 rounded-xl border border-pm-border bg-pm-subtle p-3"><div><p class="font-bold text-pm-text">{{ $expense->description }}</p><p class="mt-1 text-xs text-pm-muted">{{ $expense->occurred_at->format('d/m H:i') }} · {{ ucfirst(str_replace('_', ' ', $expense->category)) }}{{ $expense->related_type ? ' · Auto linked' : '' }}</p></div><span class="font-mono text-sm font-bold text-pm-text">€ {{ number_format($expense->amount_cents / 100, 2, ',', '.') }}</span></div>@empty<p class="text-sm text-pm-muted">{{ $it ? 'Nessuna spesa.' : 'No expenses yet.' }}</p>@endforelse</div>
                    </section>
                </aside>
            </section>
        </div>

        @if ($canWrite)
            <div class="fixed inset-x-3 bottom-3 z-40 grid grid-cols-4 gap-2 rounded-2xl border border-pm-border bg-pm-panel/95 p-2 shadow-2xl backdrop-blur sm:hidden">
                <button type="button" class="rounded-xl bg-pm-subtle px-2 py-3 text-[10px] font-black uppercase tracking-[0.08em] text-pm-text" onclick="document.getElementById('add-schedule-item').showModal()">{{ $it ? 'Pianifica' : 'Schedule' }}</button>
                <button type="button" class="rounded-xl bg-pm-subtle px-2 py-3 text-[10px] font-black uppercase tracking-[0.08em] text-pm-text" onclick="document.getElementById('add-event-task').showModal()">{{ $it ? 'Lavoro' : 'Task' }}</button>
                <button type="button" class="rounded-xl bg-pm-subtle px-2 py-3 text-[10px] font-black uppercase tracking-[0.08em] text-pm-text" onclick="document.getElementById('add-event-note').showModal()">{{ $it ? 'Nota' : 'Note' }}</button>
                <button type="button" class="rounded-xl bg-pm-accent px-2 py-3 text-[10px] font-black uppercase tracking-[0.08em] text-white" onclick="document.getElementById('add-event-expense').showModal()">{{ $it ? 'Costo' : 'Cost' }}</button>
            </div>
        @endif
    </div>
</x-layouts::app>
