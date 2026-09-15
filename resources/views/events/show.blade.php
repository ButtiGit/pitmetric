<x-layouts::app :title="$event->name">
    @php
        $it = app()->getLocale() === 'it';
        $eventSpend = (int) $event->expenses->sum('amount_cents');
        $openTasks = $event->tasks->where('status', '!=', 'done')->count();
        $eventStart = $event->start_date->copy()->startOfDay();
        $eventEnd = $event->end_date->copy()->endOfDay();
        $defaultSessionAt = now()->betweenIncluded($eventStart, $eventEnd) ? now() : $eventStart->copy()->addHours(9);
        $statusVariant = match ($event->status) {
            'active' => 'success',
            'completed' => 'info',
            'cancelled' => 'danger',
            default => 'warning',
        };
        $sortedTasks = $event->tasks->sortBy(fn ($task) => $task->status === 'done' ? 1 : 0);
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
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1440px] space-y-5">
            <section class="pm-panel p-5 sm:p-7">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('events.index') }}" class="text-xs font-bold uppercase tracking-[0.12em] text-pm-accent hover:underline">{{ $it ? 'Weekend' : 'Weekends' }}</a>
                            <x-pitmetric.status-badge :label="$event->status" :variant="$statusVariant" />
                        </div>
                        <h1 class="mt-3 text-3xl font-black tracking-[-0.03em] text-pm-text sm:text-4xl">{{ $event->name }}</h1>
                        <p class="mt-3 text-sm text-pm-text-secondary sm:text-base">{{ $event->start_date->format('d/m/Y') }} - {{ $event->end_date->format('d/m/Y') }} · {{ $event->circuitLayout?->circuit?->name }} · {{ $event->circuitLayout?->name }}</p>
                        @if ($event->championship || $event->round_label)<p class="mt-1 text-sm text-pm-muted">{{ collect([$event->championship, $event->round_label])->filter()->join(' · ') }}</p>@endif
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <x-crud-modal id="event-status" :title="$it ? 'Stato weekend' : 'Weekend status'" :description="$it ? 'Aggiorna lo stato solo quando cambia davvero, senza lasciare il select sempre in pagina.' : 'Update status only when it actually changes, without keeping the select always visible.'" :trigger="$it ? 'Cambia stato' : 'Change status'" trigger-class="pm-ghost-button">
                            <form method="POST" action="{{ route('events.status', $event) }}" class="grid gap-4">
                                @csrf @method('PATCH')
                                <label class="grid gap-2"><span class="pm-label">Status</span><select class="pm-input" name="status"><option value="planned" @selected($event->status === 'planned')>{{ $it ? 'Pianificato' : 'Planned' }}</option><option value="active" @selected($event->status === 'active')>{{ $it ? 'In corso' : 'Active' }}</option><option value="completed" @selected($event->status === 'completed')>{{ $it ? 'Completato' : 'Completed' }}</option><option value="cancelled" @selected($event->status === 'cancelled')>{{ $it ? 'Annullato' : 'Cancelled' }}</option></select></label>
                                <div class="flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Aggiorna stato' : 'Update status' }}</button></div>
                            </form>
                        </x-crud-modal>

                        <x-crud-modal id="add-event-entry" :title="$it ? 'Aggiungi entry' : 'Add entry'" :description="$it ? 'Pilota, mezzo, setup e quota di iscrizione nello stesso passaggio.' : 'Driver, vehicle, setup and entry fee in one step.'" :trigger="$it ? '+ Entry' : '+ Entry'">
                            <form method="POST" action="{{ route('events.entries.store', $event) }}" class="grid gap-4 md:grid-cols-2">
                                @csrf
                                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Pilota' : 'Driver' }}</span><select class="pm-input" name="driver_id" required><option value="">--</option>@foreach ($drivers as $driver)<option value="{{ $driver->id }}">{{ $driver->display_name }}{{ $driver->racing_number ? ' #'.$driver->racing_number : '' }}</option>@endforeach</select></label>
                                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Mezzo' : 'Vehicle' }}</span><select class="pm-input" name="vehicle_id" required><option value="">--</option>@foreach ($vehicles as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->name }}</option>@endforeach</select></label>
                                <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Setup di partenza' : 'Baseline setup' }}</span><select class="pm-input" name="configuration_version_id" required><option value="">--</option>@foreach ($versions as $version)<option value="{{ $version->id }}">{{ $version->configuration->vehicle->name }} · {{ $version->configuration->name }} v{{ $version->version_number }}</option>@endforeach</select></label>
                                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Numero entry' : 'Entry number' }}</span><input class="pm-input" name="entry_number" maxlength="20"></label>
                                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Quota / costo entry (€)' : 'Entry fee / cost (€)' }}</span><input class="pm-input" name="entry_cost" type="number" min="0" max="1000000" step="0.01"><span class="text-xs text-pm-muted">{{ $it ? 'Opzionale · finisce automaticamente nei Costi dell’evento' : 'Optional · automatically added to event Expenses' }}</span></label>
                                <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Note entry' : 'Entry notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="2000"></textarea></label>
                                <div class="md:col-span-2 flex justify-end"><button class="pm-race-button" type="submit" @disabled($drivers->isEmpty() || $vehicles->isEmpty() || $versions->isEmpty())>{{ $it ? 'Aggiungi entry' : 'Add entry' }}</button></div>
                            </form>
                        </x-crud-modal>
                    </div>
                </div>

                @if ($event->notes)<div class="mt-5 rounded-xl border border-pm-border bg-pm-subtle p-4 text-sm leading-6 text-pm-text-secondary">{{ $event->notes }}</div>@endif
            </section>

            @if (session('status'))<div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>@endif
            @if ((int) session('maintenance_attention', 0) > 0)<div class="rounded-xl border border-pm-warning/30 bg-pm-warning-subtle px-4 py-3 text-sm font-semibold text-pm-warning">{{ $it ? session('maintenance_attention').' interventi richiedono attenzione dopo la sessione.' : session('maintenance_attention').' maintenance items need attention after the session.' }} <a href="{{ route('maintenance.index') }}" class="underline">{{ $it ? 'Apri manutenzione' : 'Open maintenance' }}</a></div>@endif
            @if ($errors->any())<div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <x-pitmetric.metric-card :label="$it ? 'Entry' : 'Entries'" :value="(string) $event->entries->count()" :support="$it ? 'Pilota, mezzo e setup base' : 'Driver, vehicle and baseline setup'" />
                <x-pitmetric.metric-card :label="$it ? 'Sessioni' : 'Sessions'" :value="(string) $event->sessions->count()" :support="$it ? 'Cronologia del weekend' : 'Weekend timeline'" />
                <x-pitmetric.metric-card :label="$it ? 'Lavori aperti' : 'Open tasks'" :value="(string) $openTasks" :support="$it ? 'Da chiudere prima di ripartire' : 'Work still to close'" :variant="$openTasks > 0 ? 'warning' : 'success'" />
                <x-pitmetric.metric-card :label="$it ? 'Costo evento' : 'Event cost'" :value="'€ '.number_format($eventSpend / 100, 2, ',', '.')" :support="$it ? 'Costi manuali e operativi collegati' : 'Manual and linked operational costs'" />
            </section>

            <section class="grid gap-5 2xl:grid-cols-[1.35fr_0.65fr]">
                <div class="space-y-5">
                    <section class="pm-panel p-5 sm:p-6">
                        <div class="flex items-end justify-between gap-3"><div><p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-accent">ENTRIES</p><h2 class="mt-2 text-xl font-black text-pm-text">{{ $it ? 'Piloti, mezzi e setup' : 'Drivers, vehicles and setups' }}</h2></div><span class="text-sm text-pm-muted">{{ $event->entries->count() }}</span></div>
                        <div class="mt-5 space-y-4">
                            @forelse ($event->entries as $entry)
                                @php($entryVersions = $versions->filter(fn ($version) => (int) $version->configuration->vehicle_id === (int) $entry->vehicle_id))
                                <article class="rounded-xl border border-pm-border bg-pm-subtle p-4 sm:p-5">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div><div class="flex flex-wrap items-center gap-2"><h3 class="font-black text-pm-text">{{ $entry->driver->display_name }} · {{ $entry->vehicle->name }}</h3>@if ($entry->entry_number)<span class="rounded-lg border border-pm-border px-2 py-1 font-mono text-xs font-bold text-pm-text">#{{ $entry->entry_number }}</span>@endif</div><p class="mt-1 text-sm text-pm-text-secondary">{{ $entry->configurationVersion->configuration->name }} v{{ $entry->configurationVersion->version_number }} · {{ $entry->configurationVersion->components->count() }} {{ $it ? 'componenti' : 'components' }}</p>@if ($entry->notes)<p class="mt-2 text-xs text-pm-muted">{{ $entry->notes }}</p>@endif</div>
                                        <div class="flex items-center gap-2"><span class="text-xs font-bold text-pm-muted">{{ $entry->sessions->count() }} {{ $it ? 'sessioni' : 'sessions' }}</span>
                                            <x-crud-modal id="entry-session-{{ $entry->id }}" :title="$it ? 'Registra sessione' : 'Record session'" :description="$entry->driver->display_name.' · '.$entry->vehicle->name" :trigger="$it ? '+ Sessione' : '+ Session'" trigger-class="pm-ghost-button" size="max-w-4xl">
                                                <form method="POST" action="{{ route('sessions.store') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                                    @csrf
                                                    <input type="hidden" name="event_id" value="{{ $event->id }}"><input type="hidden" name="event_entry_id" value="{{ $entry->id }}">
                                                    <label class="grid gap-2 xl:col-span-2"><span class="pm-label">{{ $it ? 'Setup sessione' : 'Session setup' }}</span><select class="pm-input" name="configuration_version_id" required>@foreach ($entryVersions as $version)<option value="{{ $version->id }}" @selected($version->id === $entry->configuration_version_id)>{{ $version->configuration->name }} v{{ $version->version_number }}</option>@endforeach</select></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Tipo' : 'Type' }}</span><select class="pm-input" name="session_type" required><option value="practice">Practice</option><option value="qualifying">Qualifying</option><option value="heat">Heat</option><option value="prefinal">Prefinal</option><option value="final">Final</option><option value="race">Race</option><option value="test">Test</option></select></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Data e ora' : 'Date and time' }}</span><input class="pm-input" type="datetime-local" name="started_at" required value="{{ $defaultSessionAt->format('Y-m-d\TH:i') }}"></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Giri' : 'Laps' }}</span><input class="pm-input" type="number" name="completed_laps" min="0" max="10000"></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Durata min' : 'Duration min' }}</span><input class="pm-input" type="number" name="duration_minutes" min="0" max="1440" step="0.1"></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Costo sessione (€)' : 'Session cost (€)' }}</span><input class="pm-input" type="number" name="session_cost" min="0" max="1000000" step="0.01"><span class="text-xs text-pm-muted">{{ $it ? 'Opzionale · entra nei Costi evento' : 'Optional · added to event Expenses' }}</span></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Descrizione costo' : 'Cost description' }}</span><input class="pm-input" name="cost_description" maxlength="180"></label>
                                                    <label class="grid gap-2 md:col-span-2 xl:col-span-4"><span class="pm-label">{{ $it ? 'Note sessione' : 'Session notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="4000"></textarea></label>
                                                    <div class="md:col-span-2 xl:col-span-4 flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Registra sessione' : 'Record session' }}</button></div>
                                                </form>
                                            </x-crud-modal>
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <x-pitmetric.empty-state :title="$it ? 'Nessuna entry' : 'No entries'" :description="$it ? 'Aggiungi pilota, mezzo e setup prima di registrare le sessioni del weekend.' : 'Add driver, vehicle and setup before recording weekend sessions.'" />
                            @endforelse
                        </div>
                    </section>

                    <section class="pm-panel p-5 sm:p-6">
                        <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-muted">SESSION TIMELINE</p>
                        <h2 class="mt-2 text-xl font-black text-pm-text">{{ $it ? 'Cronologia in pista' : 'On-track timeline' }}</h2>
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

                <div class="space-y-5">
                    <section class="pm-panel p-5">
                        <div class="flex items-end justify-between gap-3"><div><p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-accent">{{ $it ? 'LAVORI' : 'WORK' }}</p><h2 class="mt-2 text-lg font-black text-pm-text">{{ $it ? 'Task del weekend' : 'Weekend tasks' }}</h2></div>
                            <x-crud-modal id="add-event-task" :title="$it ? 'Nuovo lavoro' : 'New task'" :description="$it ? 'Aggiungi il lavoro senza occupare spazio nella vista operativa.' : 'Add work without occupying space in the operational view.'" :trigger="$it ? '+ Lavoro' : '+ Task'" trigger-class="pm-ghost-button">
                                <form method="POST" action="{{ route('events.tasks.store', $event) }}" class="grid gap-4">@csrf<label class="grid gap-2"><span class="pm-label">{{ $it ? 'Titolo' : 'Title' }}</span><input class="pm-input" name="title" maxlength="160" required></label><div class="grid gap-4 sm:grid-cols-2"><label class="grid gap-2"><span class="pm-label">Priority</span><select class="pm-input" name="priority"><option value="normal">Normal</option><option value="low">Low</option><option value="high">High</option><option value="critical">Critical</option></select></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Assegnazione' : 'Assignment' }}</span><select class="pm-input" name="event_entry_id"><option value="">{{ $it ? 'Tutto il team' : 'Whole team' }}</option>@foreach ($event->entries as $entry)<option value="{{ $entry->id }}">{{ $entry->driver->display_name }} / {{ $entry->vehicle->name }}</option>@endforeach</select></label></div><label class="grid gap-2"><span class="pm-label">Due</span><input class="pm-input" type="datetime-local" name="due_at"></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Dettagli lavoro' : 'Work details' }}</span><textarea class="pm-input min-h-20" name="description" maxlength="3000"></textarea></label><div class="flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Aggiungi lavoro' : 'Add task' }}</button></div></form>
                            </x-crud-modal>
                        </div>

                        <div class="mt-5 space-y-2">
                            @forelse ($sortedTasks as $task)
                                <div class="rounded-xl border border-pm-border bg-pm-subtle p-3">
                                    <div class="flex items-start justify-between gap-3"><div><p class="font-bold text-pm-text {{ $task->status === 'done' ? 'line-through opacity-60' : '' }}">{{ $task->title }}</p><p class="mt-1 text-xs text-pm-muted">{{ ucfirst($task->priority) }}{{ $task->eventEntry ? ' · '.$task->eventEntry->driver->display_name : '' }}{{ $task->due_at ? ' · '.$task->due_at->format('d/m H:i') : '' }}</p>@if ($task->description)<p class="mt-2 text-xs leading-5 text-pm-text-secondary">{{ $task->description }}</p>@endif</div><x-pitmetric.status-badge :label="$task->status" :variant="$task->status === 'done' ? 'success' : ($task->priority === 'critical' ? 'danger' : ($task->priority === 'high' ? 'warning' : 'neutral'))" /></div>
                                    <div class="mt-3">
                                        <x-crud-modal id="update-task-{{ $task->id }}" :title="$it ? 'Aggiorna lavoro' : 'Update task'" :description="$task->title" :trigger="$it ? 'Aggiorna' : 'Update'" trigger-class="text-xs font-bold text-pm-accent hover:underline">
                                            <form method="POST" action="{{ route('events.tasks.update', $task) }}" class="grid gap-4">@csrf @method('PATCH')
                                                <label class="grid gap-2"><span class="pm-label">Status</span><select class="pm-input" name="status"><option value="todo" @selected($task->status === 'todo')>Todo</option><option value="in_progress" @selected($task->status === 'in_progress')>In progress</option><option value="done" @selected($task->status === 'done')>Done</option></select></label>
                                                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Costo completamento (€)' : 'Completion cost (€)' }}</span><input class="pm-input" name="operation_cost" type="number" min="0" max="1000000" step="0.01"><span class="text-xs text-pm-muted">{{ $it ? 'Usato quando salvi lo stato Done; il costo entra automaticamente nei Costi evento.' : 'Used when saving Done; the cost is automatically added to event Expenses.' }}</span></label>
                                                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Descrizione costo' : 'Cost description' }}</span><input class="pm-input" name="cost_description" maxlength="180" placeholder="{{ $task->title }}"></label>
                                                <div class="flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Salva aggiornamento' : 'Save update' }}</button></div>
                                            </form>
                                        </x-crud-modal>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-pm-muted">{{ $it ? 'Nessun lavoro aperto.' : 'No tasks yet.' }}</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="pm-panel p-5">
                        <div class="flex items-end justify-between gap-3"><div><p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'NOTE' : 'NOTES' }}</p><h2 class="mt-2 text-lg font-black text-pm-text">{{ $it ? 'Diario tecnico' : 'Technical log' }}</h2></div>
                            <x-crud-modal id="add-event-note" :title="$it ? 'Aggiungi nota tecnica' : 'Add technical note'" :trigger="$it ? '+ Nota' : '+ Note'" trigger-class="pm-ghost-button">
                                <form method="POST" action="{{ route('events.notes.store', $event) }}" class="grid gap-4">@csrf<label class="grid gap-2"><span class="pm-label">{{ $it ? 'Nota' : 'Note' }}</span><textarea class="pm-input min-h-28" name="body" maxlength="5000" required></textarea></label><div class="grid gap-4 sm:grid-cols-2"><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Riferimento' : 'Reference' }}</span><select class="pm-input" name="event_entry_id"><option value="">{{ $it ? 'Nota generale' : 'General note' }}</option>@foreach ($event->entries as $entry)<option value="{{ $entry->id }}">{{ $entry->driver->display_name }}</option>@endforeach</select></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Data e ora' : 'Date and time' }}</span><input class="pm-input" type="datetime-local" name="occurred_at" required value="{{ now()->format('Y-m-d\TH:i') }}"></label></div><div class="flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Aggiungi nota' : 'Add note' }}</button></div></form>
                            </x-crud-modal>
                        </div>
                        <div class="mt-5 space-y-2">@forelse ($sortedNotes as $note)<div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-sm leading-6 text-pm-text-secondary">{{ $note->body }}</p><p class="mt-2 text-xs text-pm-muted">{{ $note->occurred_at->format('d/m/Y H:i') }}{{ $note->eventEntry ? ' · '.$note->eventEntry->driver->display_name : '' }}</p></div>@empty<p class="text-sm text-pm-muted">{{ $it ? 'Nessuna nota tecnica.' : 'No technical notes yet.' }}</p>@endforelse</div>
                    </section>

                    <section class="pm-panel p-5">
                        <div class="flex items-end justify-between gap-3"><div><p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'COSTI' : 'COSTS' }}</p><h2 class="mt-2 text-lg font-black text-pm-text">{{ $it ? 'Spese evento' : 'Event expenses' }}</h2></div><div class="text-right"><p class="text-xl font-black text-pm-text">€ {{ number_format($eventSpend / 100, 2, ',', '.') }}</p>
                            <x-crud-modal id="add-event-expense" :title="$it ? 'Spesa manuale evento' : 'Manual event expense'" :description="$it ? 'Per carburante, viaggio o altri costi non già generati dalle operazioni.' : 'For fuel, travel or costs not already generated by operations.'" :trigger="$it ? '+ Spesa' : '+ Expense'" trigger-class="mt-2 text-xs font-bold text-pm-accent hover:underline">
                                <form method="POST" action="{{ route('events.expenses.store', $event) }}" class="grid gap-4 sm:grid-cols-2">@csrf<label class="grid gap-2"><span class="pm-label">{{ $it ? 'Importo (€)' : 'Amount (€)' }}</span><input class="pm-input" type="number" name="amount" min="0.01" max="1000000" step="0.01" required></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Categoria' : 'Category' }}</span><input class="pm-input" name="category" maxlength="80" required></label><label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ $it ? 'Descrizione' : 'Description' }}</span><input class="pm-input" name="description" maxlength="180" required></label><label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ $it ? 'Data e ora' : 'Date and time' }}</span><input class="pm-input" type="datetime-local" name="occurred_at" required value="{{ now()->format('Y-m-d\TH:i') }}"></label><div class="sm:col-span-2 flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Registra spesa' : 'Record expense' }}</button></div></form>
                            </x-crud-modal>
                        </div></div>
                        <div class="mt-5 space-y-2">@forelse ($sortedExpenses as $expense)<div class="flex items-start justify-between gap-3 rounded-xl border border-pm-border bg-pm-subtle p-3"><div><p class="font-bold text-pm-text">{{ $expense->description }}</p><p class="mt-1 text-xs text-pm-muted">{{ $expense->occurred_at->format('d/m H:i') }} · {{ ucfirst(str_replace('_', ' ', $expense->category)) }}{{ $expense->related_type ? ' · Auto linked' : '' }}</p></div><span class="font-mono text-sm font-bold text-pm-text">€ {{ number_format($expense->amount_cents / 100, 2, ',', '.') }}</span></div>@empty<p class="text-sm text-pm-muted">{{ $it ? 'Nessuna spesa ancora.' : 'No expenses yet.' }}</p>@endforelse</div>
                    </section>
                </div>
            </section>
        </div>
    </div>
</x-layouts::app>
