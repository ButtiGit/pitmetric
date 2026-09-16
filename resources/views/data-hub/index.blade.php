@php
    $it = app()->getLocale() === 'it';
    $percent = $readiness['total'] > 0 ? (int) round(($readiness['completed'] / $readiness['total']) * 100) : 0;
@endphp

<x-layouts::app :title="$it ? 'Data Hub e readiness' : 'Data Hub & Readiness'">
    <div class="pitmetric-app bg-pm-page min-h-full">
        <div class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
            <x-pitmetric.page-header
                eyebrow="PITMETRIC OPERATIONS"
                :title="$it ? 'Data Hub e readiness' : 'Data Hub & Readiness'"
                :description="$it
                    ? 'Portabilità dati, documenti privati, audit trail, notifiche e checklist per lavorare con un team reale.'
                    : 'Data portability, private documents, audit trail, notifications and an onboarding checklist for real team operations.'"
            />

            @if (session('status'))
                <div class="rounded-xl border border-pm-success bg-pm-success-subtle px-4 py-3 text-sm font-medium text-pm-success">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-pm-danger bg-pm-danger-subtle px-4 py-3 text-sm text-pm-danger">
                    <p class="font-semibold">{{ $it ? 'Operazione non completata' : 'Action not completed' }}</p>
                    <ul class="mt-1 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <section class="grid gap-6 xl:grid-cols-[1.15fr_.85fr]">
                <div class="pm-panel p-5 sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">{{ $it ? 'ONBOARDING TEAM' : 'TEAM ONBOARDING' }}</p>
                            <h2 class="mt-1 text-xl font-black text-zinc-950 dark:text-white">{{ $workspace->name }}</h2>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $readiness['completed'] }}/{{ $readiness['total'] }} {{ $it ? 'passi operativi completati' : 'operational steps completed' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-black tabular-nums text-zinc-950 dark:text-white">{{ $percent }}%</p>
                            <p class="text-xs uppercase tracking-[0.14em] text-zinc-400">{{ $it ? 'readiness' : 'readiness' }}</p>
                        </div>
                    </div>
                    <div class="mt-5 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-white/10">
                        <div class="h-full bg-pm-accent" style="width: {{ $percent }}%"></div>
                    </div>
                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        @foreach ($readiness['items'] as $item)
                            <a href="{{ $item['url'] }}" wire:navigate class="flex items-center justify-between gap-3 rounded-xl border border-zinc-200/80 p-3 transition hover:border-pm-accent dark:border-white/10">
                                <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $item['label'] }}</span>
                                <x-pitmetric.status-badge :label="$item['done'] ? ($it ? 'Fatto' : 'Done') : ($it ? 'Da fare' : 'To do')" :variant="$item['done'] ? 'success' : 'info'" />
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="pm-panel p-5 sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">{{ $it ? 'NOTIFICHE' : 'NOTIFICATIONS' }}</p>
                            <h2 class="mt-1 text-lg font-black text-zinc-950 dark:text-white">{{ $unreadNotifications }} {{ $it ? 'non lette' : 'unread' }}</h2>
                        </div>
                        @if ($unreadNotifications > 0)
                            <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="pm-ghost-button" type="submit">{{ $it ? 'Segna tutte lette' : 'Mark all read' }}</button></form>
                        @endif
                    </div>
                    <div class="mt-4 max-h-80 space-y-2 overflow-auto">
                        @forelse ($notifications as $notification)
                            @php($data = $notification->data)
                            <div class="rounded-xl border p-3 {{ $notification->read_at ? 'border-zinc-200/70 dark:border-white/10' : 'border-pm-accent/60 bg-pm-accent/5' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-zinc-950 dark:text-white">{{ $data['title'] ?? ($it ? 'Notifica operativa' : 'Operational notification') }}</p>
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $notification->created_at?->format('d/m/Y H:i') }} @if(isset($data['priority'])) · {{ ucfirst($data['priority']) }} @endif</p>
                                    </div>
                                    @if (! $notification->read_at)
                                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf<button type="submit" class="text-xs font-semibold text-pm-accent">{{ $it ? 'Letta' : 'Read' }}</button></form>
                                    @endif
                                </div>
                                @if(isset($data['url']))<a class="mt-2 inline-flex text-xs font-semibold text-pm-accent" href="{{ $data['url'] }}">{{ $it ? 'Apri' : 'Open' }}</a>@endif
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $it ? 'Nessuna notifica operativa.' : 'No operational notifications.' }}</p>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <div class="pm-panel p-5 sm:p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">{{ $it ? 'IMPORT' : 'IMPORT' }}</p>
                    <h2 class="mt-1 text-lg font-black text-zinc-950 dark:text-white">{{ $it ? 'Porta dentro i dati esistenti' : 'Bring existing data in' }}</h2>
                    <p class="mt-2 text-sm leading-6 text-zinc-500 dark:text-zinc-400">{{ $it ? 'CSV fino a 1.000 righe. L’intero file viene validato in transazione: se una riga è errata non viene importato nulla.' : 'CSV up to 1,000 rows. The whole file is validated transactionally: if one row is invalid, nothing is imported.' }}</p>
                    <form class="mt-5 grid gap-4" method="POST" action="{{ route('data-hub.import') }}" enctype="multipart/form-data">
                        @csrf
                        <label class="space-y-1.5"><span class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-400">Dataset</span><select class="pm-input w-full" name="dataset" required><option value="vehicles">{{ $it ? 'Mezzi' : 'Vehicles' }}</option><option value="expenses">{{ $it ? 'Spese' : 'Expenses' }}</option></select></label>
                        <label class="space-y-1.5"><span class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-400">CSV</span><input class="pm-input w-full" type="file" name="file" accept=".csv,text/csv" required></label>
                        <div class="rounded-xl bg-zinc-50 p-3 font-mono text-xs leading-5 text-zinc-600 dark:bg-white/[0.03] dark:text-zinc-300">
                            <p><strong>vehicles:</strong> name,category,manufacturer,model,year,identifier,status,notes</p>
                            <p class="mt-1"><strong>expenses:</strong> amount,currency,category,description,occurred_at</p>
                        </div>
                        <button class="pm-race-button justify-center" type="submit">{{ $it ? 'Importa CSV' : 'Import CSV' }}</button>
                    </form>
                </div>

                <div class="pm-panel p-5 sm:p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">{{ $it ? 'EXPORT E BACKUP' : 'EXPORT & BACKUP' }}</p>
                    <h2 class="mt-1 text-lg font-black text-zinc-950 dark:text-white">{{ $it ? 'I dati restano tuoi' : 'Your data stays portable' }}</h2>
                    <p class="mt-2 text-sm leading-6 text-zinc-500 dark:text-zinc-400">{{ $it ? 'Esporta singoli registri in CSV oppure scarica un backup JSON completo del workspace, audit trail incluso.' : 'Export individual ledgers as CSV or download a complete JSON workspace backup, including the audit trail.' }}</p>
                    <div class="mt-5 grid gap-2 sm:grid-cols-2">
                        @foreach (['vehicles' => ($it ? 'Mezzi CSV' : 'Vehicles CSV'), 'expenses' => ($it ? 'Spese CSV' : 'Expenses CSV'), 'sessions' => ($it ? 'Sessioni CSV' : 'Sessions CSV'), 'maintenance' => ($it ? 'Manutenzione CSV' : 'Maintenance CSV')] as $dataset => $label)
                            <a class="pm-ghost-button justify-center" href="{{ route('data-hub.export', $dataset) }}">{{ $label }}</a>
                        @endforeach
                    </div>
                    <a class="pm-race-button mt-4 w-full justify-center" href="{{ route('data-hub.backup') }}">{{ $it ? 'Scarica backup workspace' : 'Download workspace backup' }}</a>
                    <p class="mt-3 text-xs text-zinc-400">{{ $it ? 'Il backup è applicativo e portabile. I backup infrastrutturali del server restano separati e vanno configurati sul provider/Coolify.' : 'This is a portable application backup. Infrastructure-level server backups remain separate and should be configured at the provider/Coolify level.' }}</p>
                </div>
            </section>

            <section class="pm-panel p-5 sm:p-6">
                <div class="grid gap-6 xl:grid-cols-[.9fr_1.1fr]">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">{{ $it ? 'DOCUMENTI PRIVATI' : 'PRIVATE DOCUMENTS' }}</p>
                        <h2 class="mt-1 text-lg font-black text-zinc-950 dark:text-white">{{ $it ? 'Fatture, foto e documenti tecnici' : 'Invoices, photos and technical documents' }}</h2>
                        <form class="mt-5 grid gap-4" method="POST" action="{{ route('data-hub.attachments.store') }}" enctype="multipart/form-data">
                            @csrf
                            <label class="space-y-1.5"><span class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ $it ? 'Collega a' : 'Link to' }}</span><select class="pm-input w-full" name="attachable_type" id="attachment-type" required>@foreach(['event' => ($it ? 'Weekend' : 'Weekend'), 'expense' => ($it ? 'Spesa' : 'Expense'), 'session' => ($it ? 'Sessione' : 'Session'), 'maintenance_record' => ($it ? 'Manutenzione' : 'Maintenance')] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                            <label class="space-y-1.5"><span class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ $it ? 'Record' : 'Record' }}</span><select class="pm-input w-full" name="attachable_id" required>@foreach($attachmentTargets as $type => $targets)@foreach($targets as $target)<option value="{{ $target['id'] }}" data-attachment-type="{{ $type }}">{{ ucfirst(str_replace('_',' ',$type)) }} · {{ $target['label'] }}</option>@endforeach@endforeach</select></label>
                            <label class="space-y-1.5"><span class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ $it ? 'Etichetta' : 'Label' }}</span><input class="pm-input w-full" name="label" maxlength="180" placeholder="{{ $it ? 'Es. Fattura pneumatici' : 'E.g. Tyre invoice' }}"></label>
                            <label class="space-y-1.5"><span class="text-xs font-semibold uppercase tracking-[0.14em] text-zinc-400">File</span><input class="pm-input w-full" type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.webp,.csv,.txt" required></label>
                            <button class="pm-race-button justify-center" type="submit">{{ $it ? 'Carica documento privato' : 'Upload private document' }}</button>
                        </form>
                    </div>

                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-[0.14em] text-zinc-400">{{ $it ? 'Ultimi documenti' : 'Recent documents' }}</h3>
                        <div class="mt-3 max-h-[30rem] divide-y divide-zinc-200/80 overflow-auto dark:divide-white/10">
                            @forelse($attachments as $attachment)
                                <div class="flex items-center justify-between gap-4 py-3">
                                    <div class="min-w-0"><p class="truncate font-semibold text-zinc-950 dark:text-white">{{ $attachment->label ?: $attachment->original_name }}</p><p class="mt-1 text-xs text-zinc-400">{{ ucfirst(str_replace('_',' ',$attachment->attachable_type)) }} #{{ $attachment->attachable_id }} · {{ number_format($attachment->size_bytes / 1024, 0, ',', '.') }} KB · {{ $attachment->uploader?->name }}</p></div>
                                    <div class="flex shrink-0 gap-2"><a class="pm-ghost-button" href="{{ route('data-hub.attachments.download', $attachment) }}">{{ $it ? 'Scarica' : 'Download' }}</a>@can('team-write')<form method="POST" action="{{ route('data-hub.attachments.destroy', $attachment) }}">@csrf @method('DELETE')<button class="pm-ghost-button" type="submit">{{ $it ? 'Elimina' : 'Delete' }}</button></form>@endcan</div>
                                </div>
                            @empty
                                <p class="py-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $it ? 'Nessun documento caricato.' : 'No documents uploaded.' }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            <section class="pm-panel overflow-hidden">
                <div class="border-b border-zinc-200/80 px-5 py-4 dark:border-white/10"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">AUDIT TRAIL</p><h2 class="mt-1 text-lg font-black text-zinc-950 dark:text-white">{{ $it ? 'Chi ha cambiato cosa' : 'Who changed what' }}</h2></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-zinc-50 text-xs uppercase tracking-[0.12em] text-zinc-500 dark:bg-white/[0.03] dark:text-zinc-400"><tr><th class="px-5 py-3">{{ $it ? 'Quando' : 'When' }}</th><th class="px-3 py-3">{{ $it ? 'Utente' : 'User' }}</th><th class="px-3 py-3">{{ $it ? 'Azione' : 'Action' }}</th><th class="px-3 py-3">{{ $it ? 'Entità' : 'Entity' }}</th><th class="px-5 py-3">{{ $it ? 'Campi' : 'Fields' }}</th></tr></thead>
                        <tbody class="divide-y divide-zinc-200/80 dark:divide-white/10">
                            @forelse($auditLogs as $log)
                                <tr><td class="px-5 py-3 whitespace-nowrap text-zinc-500 dark:text-zinc-400">{{ $log->created_at?->format('d/m/Y H:i') }}</td><td class="px-3 py-3 font-medium text-zinc-950 dark:text-white">{{ $log->actor?->name ?? 'System' }}</td><td class="px-3 py-3"><x-pitmetric.status-badge :label="ucfirst($log->action)" :variant="$log->action === 'deleted' ? 'danger' : ($log->action === 'updated' ? 'warning' : 'success')" /></td><td class="px-3 py-3 text-zinc-600 dark:text-zinc-300">{{ $log->entity_type }} #{{ $log->entity_id }}</td><td class="px-5 py-3 text-zinc-500 dark:text-zinc-400">{{ implode(', ', $log->metadata['changed_fields'] ?? []) ?: '—' }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-5 text-zinc-500 dark:text-zinc-400">{{ $it ? 'L’audit trail inizierà a popolarsi dalle prossime modifiche.' : 'The audit trail will populate as changes are made.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-layouts::app>
