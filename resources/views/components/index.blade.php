<x-layouts::app :title="__('Components')">
    @php
        $it = app()->getLocale() === 'it';
        $formatUsage = static function (int $value, \App\Models\UsageMetricType $metric): string {
            return match ($metric->key) {
                'distance' => number_format($value / 1000, 1, ',', '.').' km',
                'runtime' => number_format($value / 3600, 1, ',', '.').' h',
                default => number_format($value, 0, ',', '.').' '.$metric->display_unit,
            };
        };
        $availableComponents = $components->filter(fn ($component) => $component->activeInstallation === null);
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1360px] space-y-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">COMPONENTS</p>
                    <x-pitmetric.page-header :title="$it ? 'Componenti reali' : 'Real components'" :description="$it ? 'Ogni componente conserva utilizzo, manutenzione, costo e storico dei montaggi fisici sui mezzi.' : 'Every component keeps usage, maintenance, cost and physical installation history across vehicles.'" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-crud-modal id="create-component" :title="$it ? 'Aggiungi componente' : 'Add component'" :description="$it ? 'Il costo di acquisto viene registrato subito anche nei Costi.' : 'Purchase cost is recorded in Expenses immediately.'" :trigger="$it ? '+ Componente' : '+ Component'">
                        <form method="POST" action="{{ route('components.store') }}" class="grid gap-4 md:grid-cols-2">
                            @csrf
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Nome' : 'Name' }}</span><input class="pm-input" name="name" required maxlength="120" value="{{ old('name') }}"></label>
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Tipo' : 'Type' }}</span><input class="pm-input" name="type_name" required maxlength="100" value="{{ old('type_name') }}"></label>
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Metrica principale' : 'Primary metric' }}</span><select class="pm-input" name="metric_key" required>@foreach ($metrics as $metric)<option value="{{ $metric->key }}" @selected(old('metric_key', 'runtime') === $metric->key)>{{ $metric->name }} · {{ $metric->display_unit }}</option>@endforeach</select></label>
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Seriale / codice' : 'Serial / code' }}</span><input class="pm-input" name="serial_number" maxlength="120" value="{{ old('serial_number') }}"></label>
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Marca' : 'Manufacturer' }}</span><input class="pm-input" name="manufacturer" maxlength="100" value="{{ old('manufacturer') }}"></label>
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Modello' : 'Model' }}</span><input class="pm-input" name="model" maxlength="100" value="{{ old('model') }}"></label>
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Data acquisto' : 'Purchase date' }}</span><input class="pm-input" name="purchase_date" type="date" value="{{ old('purchase_date') }}"></label>
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Costo acquisto (€)' : 'Purchase cost (€)' }}</span><input class="pm-input" name="purchase_cost" type="number" min="0" max="1000000" step="0.01" value="{{ old('purchase_cost') }}"></label>
                            <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="2000">{{ old('notes') }}</textarea></label>
                            <div class="md:col-span-2 flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Salva componente' : 'Save component' }}</button></div>
                        </form>
                    </x-crud-modal>

                    <x-crud-modal id="install-component" :title="$it ? 'Monta componente' : 'Install component'" :description="$it ? 'Registri posizione, data e costo dell’operazione in un solo passaggio.' : 'Record position, date and operation cost in one step.'" :trigger="$it ? 'Monta componente' : 'Install component'" trigger-class="pm-ghost-button">
                        <form method="POST" action="{{ route('component-installations.store') }}" class="grid gap-4 md:grid-cols-2">
                            @csrf
                            <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Componente disponibile' : 'Available component' }}</span><select class="pm-input" name="component_id" required><option value="">{{ $it ? 'Seleziona componente' : 'Select component' }}</option>@foreach ($availableComponents as $component)<option value="{{ $component->id }}">{{ $component->name }} · {{ $component->type->name }}</option>@endforeach</select></label>
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Mezzo' : 'Vehicle' }}</span><select class="pm-input" name="vehicle_id" required><option value="">{{ $it ? 'Seleziona mezzo' : 'Select vehicle' }}</option>@foreach ($vehicles as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->name }}</option>@endforeach</select></label>
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Posizione / ruolo' : 'Position / role' }}</span><input class="pm-input" name="position_or_role" maxlength="100"></label>
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Montato il' : 'Installed at' }}</span><input class="pm-input" name="installed_at" type="datetime-local" required value="{{ now()->format('Y-m-d\TH:i') }}"></label>
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Costo operazione (€)' : 'Operation cost (€)' }}</span><input class="pm-input" name="operation_cost" type="number" min="0" max="1000000" step="0.01"><span class="text-xs text-pm-muted">{{ $it ? 'Opzionale · entra automaticamente nei Costi' : 'Optional · automatically added to Expenses' }}</span></label>
                            <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Note montaggio' : 'Installation notes' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="2000"></textarea></label>
                            <div class="md:col-span-2 flex justify-end"><button class="pm-race-button" type="submit" @disabled($availableComponents->isEmpty() || $vehicles->isEmpty())>{{ $it ? 'Registra montaggio' : 'Record installation' }}</button></div>
                        </form>
                    </x-crud-modal>
                </div>
            </div>

            @if (session('status'))<div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>@endif
            @if (session('error'))<div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle px-4 py-3 text-sm font-semibold text-pm-danger">{{ session('error') }}</div>@endif
            @if ($errors->any())<div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            @if ($components->isEmpty())
                <x-pitmetric.empty-state :title="$it ? 'Nessun componente' : 'No components'" :description="$it ? 'Crea il primo componente per iniziare a costruire configurazioni reali.' : 'Create the first component to start building real configurations.'" />
            @else
                <section class="pm-panel overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1180px] text-left text-sm">
                            <thead class="border-b border-pm-border bg-pm-subtle text-[11px] uppercase tracking-[0.1em] text-pm-muted"><tr><th class="px-4 py-3">Component</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">{{ $it ? 'Montato su' : 'Installed on' }}</th><th class="px-4 py-3">Metric</th><th class="px-4 py-3">{{ $it ? 'Dall’ultimo service' : 'Since service' }}</th><th class="px-4 py-3">Lifetime</th><th class="px-4 py-3">{{ $it ? 'Acquisto' : 'Purchase' }}</th><th class="px-4 py-3"></th></tr></thead>
                            <tbody class="divide-y divide-pm-border">
                                @foreach ($components as $component)
                                    @php($tracker = $component->trackers->first())
                                    @php($installation = $component->activeInstallation)
                                    <tr id="component-{{ $component->id }}" class="scroll-mt-28 transition-colors target:bg-pm-accent/5">
                                        <td class="px-4 py-4"><p class="font-bold text-pm-text">{{ $component->name }}</p><p class="mt-1 text-xs text-pm-muted">{{ $component->serial_number ?: '—' }}</p></td>
                                        <td class="px-4 py-4 text-pm-text-secondary">{{ $component->type->name }}</td>
                                        <td class="px-4 py-4">
                                            @if ($installation)
                                                <p class="font-bold text-pm-text">{{ $installation->vehicle->name }}</p>
                                                <p class="mt-1 text-xs text-pm-muted">{{ $installation->position_or_role ?: ($it ? 'Posizione non specificata' : 'Position not specified') }} · {{ $installation->installed_at->format('d/m/Y H:i') }}</p>
                                                <div class="mt-2">
                                                    <x-crud-modal id="remove-installation-{{ $installation->id }}" :title="$it ? 'Rimuovi componente' : 'Remove component'" :description="$component->name.' · '.$installation->vehicle->name" :trigger="$it ? 'Rimuovi dal mezzo' : 'Remove from vehicle'" trigger-class="text-xs font-bold text-pm-accent hover:underline">
                                                        <form method="POST" action="{{ route('component-installations.remove', $installation) }}" class="grid gap-4 sm:grid-cols-2">
                                                            @csrf @method('PATCH')
                                                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Rimosso il' : 'Removed at' }}</span><input class="pm-input" name="removed_at" type="datetime-local" required value="{{ now()->format('Y-m-d\TH:i') }}"></label>
                                                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Costo operazione (€)' : 'Operation cost (€)' }}</span><input class="pm-input" name="operation_cost" type="number" min="0" max="1000000" step="0.01"><span class="text-xs text-pm-muted">{{ $it ? 'Opzionale · registrato nei Costi' : 'Optional · recorded in Expenses' }}</span></label>
                                                            <div class="sm:col-span-2 flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Conferma rimozione' : 'Confirm removal' }}</button></div>
                                                        </form>
                                                    </x-crud-modal>
                                                </div>
                                            @else
                                                <span class="text-pm-muted">{{ $it ? 'Disponibile' : 'Available' }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-pm-text-secondary">{{ $tracker?->metric?->name ?? '—' }}</td>
                                        <td class="px-4 py-4 font-mono text-pm-text">{{ $tracker ? $formatUsage($usage[$tracker->id]['since_service'], $tracker->metric) : '—' }}</td>
                                        <td class="px-4 py-4 font-mono text-pm-text">{{ $tracker ? $formatUsage($usage[$tracker->id]['lifetime'], $tracker->metric) : '—' }}</td>
                                        <td class="px-4 py-4 text-pm-text-secondary">@if ($component->purchase_cost_cents !== null)<p class="font-mono font-bold text-pm-text">€ {{ number_format($component->purchase_cost_cents / 100, 2, ',', '.') }}</p><p class="mt-1 text-xs text-pm-muted">{{ $component->purchase_date?->format('d/m/Y') }}</p>@else — @endif</td>
                                        <td class="px-4 py-4 text-right"><form method="POST" action="{{ route('components.destroy', $component) }}" onsubmit="return confirm(@js($it ? 'Archiviare questo componente?' : 'Archive this component?'))">@csrf @method('DELETE')<button class="text-xs font-bold text-pm-danger hover:underline" @disabled($installation)>{{ $it ? 'Archivia' : 'Archive' }}</button></form></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-layouts::app>
