<section class="overflow-hidden rounded-2xl border border-pm-border bg-pm-panel">
    <div class="border-b border-pm-border bg-pm-subtle px-5 py-3 sm:px-7">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
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
                        </form>
                    </x-crud-modal>
                    <x-pitmetric.record-delete :action="route('events.destroy', $event)" :label="__('crud.archive')" :message="__('crud.confirm_archive')" />
                    <x-crud-modal id="event-status" :title="$it ? 'Stato weekend' : 'Weekend status'" :trigger="$it ? 'Cambia stato' : 'Change status'" trigger-class="pm-ghost-button">
                        @can('team-write')<form method="POST" action="{{ route('events.status', $event) }}" class="grid gap-4">
                            @csrf @method('PATCH')
                            <label class="grid gap-2"><span class="pm-label">Status</span><select class="pm-input" name="status"><option value="planned" @selected($event->status === 'planned')>{{ $it ? 'Pianificato' : 'Planned' }}</option><option value="active" @selected($event->status === 'active')>{{ $it ? 'In corso' : 'Active' }}</option><option value="completed" @selected($event->status === 'completed')>{{ $it ? 'Completato' : 'Completed' }}</option><option value="cancelled" @selected($event->status === 'cancelled')>{{ $it ? 'Annullato' : 'Cancelled' }}</option></select></label>
                            <div class="flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Aggiorna' : 'Update' }}</button></div>
                        </form>@endcan
                    </x-crud-modal>

                    <x-crud-modal id="add-event-entry" :title="$it ? 'Aggiungi iscrizione' : 'Add entry'" :description="$it ? 'Pilota, mezzo, configurazione base e quota in un solo passaggio.' : 'Driver, vehicle, baseline configuration and fee in one step.'" :trigger="$it ? '+ Iscrizione' : '+ Entry'">
                        @can('team-write')<form method="POST" action="{{ route('events.entries.store', $event) }}" class="grid gap-4 md:grid-cols-2">
                            @csrf
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Pilota' : 'Driver' }}</span><select class="pm-input" name="driver_id" required><option value="">--</option>@foreach ($drivers as $driver)<option value="{{ $driver->id }}">{{ $driver->display_name }}{{ $driver->racing_number ? ' #'.$driver->racing_number : '' }}</option>@endforeach</select></label>
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Mezzo' : 'Vehicle' }}</span><select class="pm-input" name="vehicle_id" required><option value="">--</option>@foreach ($vehicles as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->name }}</option>@endforeach</select></label>
                            <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Configurazione base' : 'Baseline configuration' }}</span><select class="pm-input" name="configuration_version_id" data-pm-vehicle-options required><option value="">--</option>@foreach ($versions as $version)<option value="{{ $version->id }}" data-vehicle-id="{{ $version->configuration->vehicle_id }}">{{ $version->configuration->vehicle->name }} · {{ $version->configuration->name }} v{{ $version->version_number }}</option>@endforeach</select></label>
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Numero entry' : 'Entry number' }}</span><input class="pm-input" name="entry_number" maxlength="20"></label>
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Quota / costo (€)' : 'Fee / cost (€)' }}</span><input class="pm-input" name="entry_cost" type="number" min="0" max="1000000" step="0.01"><span class="text-xs text-pm-muted">{{ $it ? 'Opzionale, viene registrata nei Costi.' : 'Optional, recorded in Expenses.' }}</span></label>
                            <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="2000"></textarea></label>
                            @if ($drivers->isEmpty() || $vehicles->isEmpty() || $versions->isEmpty())<div class="md:col-span-2 rounded-lg border border-pm-warning/30 p-3 text-sm text-pm-text-secondary">{{ $it ? 'Per iscrivere un equipaggio servono pilota, mezzo e configurazione.' : 'An entry needs a driver, vehicle and configuration.' }} <a class="text-pm-accent underline" href="{{ route('events.index') }}#create-driver">{{ $it ? 'Piloti' : 'Drivers' }}</a> · <a class="text-pm-accent underline" href="{{ route('garage.index') }}#create-vehicle">{{ $it ? 'Mezzi' : 'Vehicles' }}</a> · <a class="text-pm-accent underline" href="{{ route('configurations.index') }}#create-configuration">{{ $it ? 'Configurazioni' : 'Configurations' }}</a></div>@endif
                            <div class="flex justify-end md:col-span-2"><button class="pm-race-button" type="submit" @disabled($drivers->isEmpty() || $vehicles->isEmpty() || $versions->isEmpty())>{{ $it ? 'Aggiungi iscrizione' : 'Add entry' }}</button></div>
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
    <x-pitmetric.metric-card :label="$it ? 'Prossima attività' : 'Next activity'" :value="$nextScheduleItem ? $nextScheduleItem->starts_at->format('H:i') : '—'" :support="$nextScheduleItem ? ucfirst($nextScheduleItem->session_type).' · '.($nextScheduleItem->eventEntry?->driver?->display_name ?? 'Team') : ($it ? 'Nessuna sessione pianificata' : 'No session scheduled')" :variant="$nextScheduleItem?->status === 'live' ? 'danger' : ($nextScheduleItem?->status === 'ready' ? 'warning' : 'info')" />
    <x-pitmetric.metric-card :label="$it ? 'Lavori aperti' : 'Open work'" :value="(string) $openTasks" :support="$criticalTasks > 0 ? ($it ? $criticalTasks.' critici' : $criticalTasks.' critical') : ($it ? 'Nessun blocco critico' : 'No critical blockers')" :variant="$criticalTasks > 0 ? 'danger' : ($openTasks > 0 ? 'warning' : 'success')" />
    <x-pitmetric.metric-card :label="$it ? 'Manutenzione' : 'Maintenance'" :value="(string) $maintenanceSummary['attention']" :support="$it ? 'elementi da controllare sulle entry' : 'items needing attention on entries'" :variant="$maintenanceSummary['attention'] > 0 ? 'warning' : 'success'" />
    <x-pitmetric.metric-card :label="$it ? 'Costo evento' : 'Event cost'" :value="'€ '.number_format($eventSpend / 100, 2, ',', '.')" :support="$it ? 'operazioni e spese manuali' : 'operations and manual expenses'" />
</section>
