@php
    $it = app()->getLocale() === 'it';
    $bytes = function (int $value): string {
        if ($value < 1024) {
            return $value.' B';
        }

        if ($value < 1024 * 1024) {
            return number_format($value / 1024, 1, ',', '.').' KB';
        }

        return number_format($value / (1024 * 1024), 1, ',', '.').' MB';
    };
@endphp

<x-layouts::app title="Control Center">
    <div class="pitmetric-app bg-pm-page min-h-full">
        <div class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
            <x-pitmetric.page-header
                eyebrow="PITMETRIC CONTROL"
                title="Control Center"
                :description="$it
                    ? 'Audit, backup, documenti privati, alert operativi e readiness del team in un unico punto.'
                    : 'Audit, portable backup, private documents, operational alerts and team readiness in one place.'"
                :help="__('help.control_center.page')"
            >
                <x-slot:actions>
                    <span class="inline-flex items-center rounded-full border border-pm-accent/25 bg-pm-accent/10 px-3 py-1.5 text-xs font-semibold text-pm-accent">
                        {{ $it ? 'Solo owner e manager' : 'Owners and managers only' }}
                    </span>
                </x-slot:actions>
            </x-pitmetric.page-header>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-200">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-red-500/25 bg-red-500/10 px-4 py-3 text-sm text-red-100">
                    <p class="font-semibold">{{ $it ? 'Operazione non completata' : 'Operation not completed' }}</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <section class="pm-panel p-5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">READINESS</p>
                        <div class="mt-1 flex items-center gap-2">
                            <h2 class="text-xl font-bold text-zinc-950 dark:text-white">{{ $it ? 'Pronto per un team esterno' : 'External-team readiness' }}</h2>
                            <x-pitmetric.help-tooltip :text="__('help.control_center.readiness')" position="right" />
                        </div>
                        <p class="mt-2 max-w-2xl text-sm text-zinc-500 dark:text-zinc-400">{{ $it ? 'Controlli minimi prima di mettere il workspace in mano a un team reale.' : 'Minimum checks before handing the workspace to a real external team.' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-mono text-3xl font-black text-zinc-950 dark:text-white">{{ $readiness['completed'] }}/{{ $readiness['required'] }}</p>
                        <p class="text-xs uppercase tracking-[0.12em] text-zinc-500">{{ $readiness['percentage'] }}%</p>
                    </div>
                </div>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-white/10"><div class="h-full bg-pm-accent" style="width: {{ $readiness['percentage'] }}%"></div></div>
                <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($readiness['items'] as $item)
                        <div class="rounded-xl border border-zinc-200/80 p-4 dark:border-white/10">
                            <div class="flex items-start justify-between gap-3">
                                <div><p class="font-semibold text-zinc-950 dark:text-white">{{ $item['label'] }}</p><p class="mt-1 text-xs leading-5 text-zinc-500">{{ $item['detail'] }}</p></div>
                                <span class="rounded-full px-2 py-1 text-[10px] font-bold uppercase {{ $item['complete'] ? 'bg-emerald-500/15 text-emerald-300' : ($item['required'] ? 'bg-amber-500/15 text-amber-300' : 'bg-zinc-500/15 text-zinc-400') }}">{{ $item['complete'] ? 'OK' : ($item['required'] ? 'Required' : 'Optional') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-2">
                <section class="pm-panel p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">DATA HUB</p>
                    <div class="mt-1 flex items-center gap-2">
                        <h2 class="text-lg font-bold text-zinc-950 dark:text-white">{{ $it ? 'Backup portabile' : 'Portable workspace backup' }}</h2>
                        <x-pitmetric.help-tooltip :text="__('help.control_center.data_hub')" position="right" />
                    </div>
                    <p class="mt-2 text-sm leading-6 text-zinc-500 dark:text-zinc-400">{{ $it ? 'Esporta i dati operativi e le relazioni in JSON. I file privati restano separati. Il restore è accettato solo su un workspace vuoto.' : 'Export operational data and relationships to JSON. Private files stay separate. Restore is accepted only into an empty workspace.' }}</p>
                    @can('team-manage')
                        <a href="{{ route('control-center.export') }}" class="pm-race-button mt-5 inline-flex">{{ $it ? 'Esporta backup' : 'Export backup' }}</a>
                    @endcan
                    @can('team-own')
                        <form method="POST" action="{{ route('control-center.import') }}" enctype="multipart/form-data" class="mt-5 space-y-3 border-t border-white/10 pt-5">
                            @csrf
                            <label class="block space-y-1.5"><span class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">{{ $it ? 'File backup' : 'Backup file' }}</span><input class="pm-input block w-full" type="file" name="backup" accept="application/json,.json" required></label>
                            <div class="flex items-start gap-2">
                                <p class="text-xs leading-5 text-amber-300">{{ $it ? 'Gli ID vengono rimappati e le assegnazioni personali dei work order vengono azzerate.' : 'IDs are remapped and personal work-order assignments are reset.' }}</p>
                                <x-pitmetric.help-tooltip :text="__('help.control_center.restore')" position="right" />
                            </div>
                            <button class="pm-ghost-button" type="submit" onclick="return confirm('{{ $it ? 'Importare il backup nel workspace corrente?' : 'Import this backup into the current workspace?' }}')">{{ $it ? 'Importa backup' : 'Import backup' }}</button>
                        </form>
                    @endcan
                </section>

                <section class="pm-panel p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">OPERATIONAL ALERTS</p>
                            <div class="mt-1 flex items-center gap-2">
                                <h2 class="text-lg font-bold text-zinc-950 dark:text-white">{{ $unreadNotificationCount }} {{ $it ? 'non letti' : 'unread' }}</h2>
                                <x-pitmetric.help-tooltip :text="__('help.control_center.alerts')" position="right" />
                            </div>
                        </div>
                        @if ($unreadNotificationCount > 0)<form method="POST" action="{{ route('control-center.notifications.read-all') }}">@csrf<button class="pm-ghost-button" type="submit">{{ $it ? 'Segna letti' : 'Mark read' }}</button></form>@endif
                    </div>
                    <div class="mt-5 space-y-2">
                        @forelse ($notifications->take(8) as $notification)
                            @php $payload = $notification->data; $target = isset($payload['route_name']) ? route($payload['route_name'], $payload['route_params'] ?? []) : null; @endphp
                            <div class="rounded-xl border p-3 {{ $notification->read_at ? 'border-white/10' : 'border-pm-accent/30 bg-pm-accent/5' }}">
                                <p class="font-semibold text-zinc-950 dark:text-white">{{ $payload['title'] ?? 'PitMetric alert' }}</p>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $payload['message'] ?? '' }}</p>
                                <div class="mt-2 flex gap-3 text-xs">@if ($target)<a class="font-semibold text-pm-accent" href="{{ $target }}">{{ $it ? 'Apri' : 'Open' }}</a>@endif @if (!$notification->read_at)<form method="POST" action="{{ route('control-center.notifications.read', $notification->id) }}">@csrf<button class="font-semibold text-zinc-400" type="submit">{{ $it ? 'Letto' : 'Read' }}</button></form>@endif</div>
                            </div>
                        @empty
                            <p class="rounded-xl border border-dashed border-white/10 p-4 text-sm text-zinc-500">{{ $it ? 'Nessun alert operativo.' : 'No operational alerts.' }}</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <section class="pm-panel p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">PRIVATE DOCUMENTS</p>
                <div class="mt-1 flex items-center gap-2">
                    <h2 class="text-lg font-bold text-zinc-950 dark:text-white">{{ $it ? 'Archivio privato del team' : 'Private team archive' }}</h2>
                    <x-pitmetric.help-tooltip :text="__('help.control_center.documents')" position="right" />
                </div>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $it ? 'I file sono serviti solo attraverso rotte autenticate e restano sul disco privato.' : 'Files are served only through authenticated routes and remain on private storage.' }}</p>

                <div class="mt-5 grid gap-6 xl:grid-cols-[minmax(0,360px)_1fr]">
                    @can('team-write')
                        <form method="POST" action="{{ route('control-center.documents.store') }}" enctype="multipart/form-data" class="space-y-3">
                            @csrf
                            <label class="block space-y-1.5"><span class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">{{ $it ? 'Documento' : 'Document' }}</span><input class="pm-input block w-full" type="file" name="document" required></label>
                            <label class="block space-y-1.5"><span class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">{{ $it ? 'Nome' : 'Name' }}</span><input class="pm-input w-full" type="text" name="name" maxlength="180"></label>
                            <label class="block space-y-1.5">
                                <span class="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">
                                    {{ $it ? 'Collega a' : 'Attach to' }}
                                    <x-pitmetric.help-tooltip :text="__('help.control_center.attach_to')" position="right" />
                                </span>
                                <select class="pm-input w-full" name="attachable"><option value="">{{ $it ? 'Nessun record specifico' : 'No specific record' }}</option>@foreach ($vehicles as $vehicle)<option value="vehicle:{{ $vehicle->id }}">Vehicle · {{ $vehicle->name }}</option>@endforeach @foreach ($components as $component)<option value="component:{{ $component->id }}">Component · {{ $component->name }}</option>@endforeach @foreach ($events as $event)<option value="event:{{ $event->id }}">Event · {{ $event->name }}</option>@endforeach @foreach ($maintenanceRecords as $record)<option value="maintenance_record:{{ $record->id }}">Maintenance · {{ $record->description }}</option>@endforeach @foreach ($sessions as $session)<option value="session:{{ $session->id }}">Session #{{ $session->id }} · {{ $session->session_type }}</option>@endforeach</select>
                            </label>
                            <label class="block space-y-1.5"><span class="text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500">Note</span><textarea class="pm-input min-h-20 w-full" name="notes" maxlength="2000"></textarea></label>
                            <button class="pm-race-button" type="submit">{{ $it ? 'Carica in privato' : 'Upload privately' }}</button>
                        </form>
                    @endcan

                    <div class="space-y-2">
                        @forelse ($documents as $document)
                            <div class="flex flex-col gap-3 rounded-xl border border-white/10 p-3 sm:flex-row sm:items-center">
                                <div class="min-w-0 flex-1"><p class="truncate font-semibold text-zinc-950 dark:text-white">{{ $document->name }}</p><p class="mt-1 truncate text-xs text-zinc-500">{{ $document->original_name }} · {{ $bytes($document->size_bytes) }}@if ($document->attachable_type) · {{ $document->attachable_type }} #{{ $document->attachable_id }}@endif</p></div>
                                <div class="flex gap-2"><a class="pm-ghost-button" href="{{ route('control-center.documents.download', $document) }}">{{ $it ? 'Scarica' : 'Download' }}</a>@can('team-write')<form method="POST" action="{{ route('control-center.documents.destroy', $document) }}">@csrf @method('DELETE')<button class="pm-ghost-button" type="submit" onclick="return confirm('{{ $it ? 'Eliminare questo documento?' : 'Delete this document?' }}')">{{ $it ? 'Elimina' : 'Delete' }}</button></form>@endcan</div>
                            </div>
                        @empty
                            <p class="rounded-xl border border-dashed border-white/10 p-4 text-sm text-zinc-500">{{ $it ? 'Nessun documento privato caricato.' : 'No private documents uploaded.' }}</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="pm-panel overflow-hidden">
                <div class="border-b border-white/10 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">AUDIT TRAIL</p>
                    <div class="mt-1 flex items-center gap-2">
                        <h2 class="text-lg font-bold text-zinc-950 dark:text-white">{{ $it ? 'Chi ha cambiato cosa e quando' : 'Who changed what and when' }}</h2>
                        <x-pitmetric.help-tooltip :text="__('help.control_center.audit')" position="right" />
                    </div>
                    <form method="GET" action="{{ route('control-center.index') }}" class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                        <input class="pm-input xl:col-span-2" type="search" name="q" value="{{ $activityFilters['q'] ?? '' }}" placeholder="{{ $it ? 'Record, tipo o azione' : 'Record, type or action' }}">
                        <select class="pm-input" name="action"><option value="">{{ $it ? 'Tutte le azioni' : 'All actions' }}</option>@foreach (['created','updated','archived','deleted','restored','data_exported','data_imported'] as $action)<option value="{{ $action }}" @selected(($activityFilters['action'] ?? '') === $action)>{{ $action }}</option>@endforeach</select>
                        <select class="pm-input" name="actor"><option value="">{{ $it ? 'Tutti gli utenti' : 'All actors' }}</option>@foreach ($actors as $actor)<option value="{{ $actor->id }}" @selected((string) ($activityFilters['actor'] ?? '') === (string) $actor->id)>{{ $actor->name }}</option>@endforeach</select>
                        <div class="flex gap-2"><button class="pm-race-button" type="submit">{{ $it ? 'Filtra' : 'Filter' }}</button><a class="pm-ghost-button" href="{{ route('control-center.index') }}">Reset</a></div>
                        <input class="pm-input" type="date" name="from" value="{{ $activityFilters['from'] ?? '' }}"><input class="pm-input" type="date" name="to" value="{{ $activityFilters['to'] ?? '' }}">
                    </form>
                </div>
                <div class="divide-y divide-white/10">
                    @forelse ($activity as $log)
                        @php $changed = array_values(array_unique(array_merge(array_keys($log->before_values ?? []), array_keys($log->after_values ?? [])))); @endphp
                        <div class="grid gap-2 px-5 py-4 sm:grid-cols-[160px_1fr_auto]">
                            <div><p class="font-mono text-xs text-zinc-500">{{ $log->created_at?->format('d/m/Y H:i:s') }}</p><p class="mt-1 text-xs text-zinc-500">{{ $log->user?->name ?? 'System' }}</p></div>
                            <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><span class="rounded-md bg-white/10 px-2 py-1 font-mono text-[10px] font-bold uppercase">{{ $log->action }}</span><p class="truncate font-semibold text-zinc-950 dark:text-white">{{ $log->subject_label ?? class_basename((string) $log->subject_type) }}</p></div><p class="mt-1 text-xs text-zinc-500">{{ class_basename((string) $log->subject_type) }}@if ($log->subject_id) #{{ $log->subject_id }}@endif @if ($changed !== []) · {{ implode(', ', $changed) }}@endif</p></div>
                            <div class="text-xs text-zinc-500 sm:text-right">@if ($log->route)<p>{{ $log->route }}</p>@endif @if ($log->ip_address)<p class="font-mono">{{ $log->ip_address }}</p>@endif</div>
                        </div>
                    @empty
                        <div class="p-5 text-sm text-zinc-500">{{ $it ? 'Nessuna attività corrisponde ai filtri.' : 'No activity matches the current filters.' }}</div>
                    @endforelse
                </div>
                @if ($activity->hasPages())<div class="border-t border-white/10 px-5 py-4">{{ $activity->links() }}</div>@endif
            </section>
        </div>
    </div>
</x-layouts::app>
