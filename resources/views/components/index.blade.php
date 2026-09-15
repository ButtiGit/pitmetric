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
            <div><p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">COMPONENTS</p><x-pitmetric.page-header :title="$it ? 'Componenti reali' : 'Real components'" :description="$it ? 'Ogni componente conserva utilizzo, manutenzione, costo e storico dei montaggi fisici sui mezzi.' : 'Every component keeps usage, maintenance, cost and physical installation history across vehicles.'" /></div>

            @if (session('status'))<div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>@endif
            @if (session('error'))<div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle px-4 py-3 text-sm font-semibold text-pm-danger">{{ session('error') }}</div>@endif
            @if ($errors->any())<div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <section class="pm-panel p-5 sm:p-6">
                <div><h2 class="text-lg font-black text-pm-text">{{ $it ? 'Aggiungi componente' : 'Add component' }}</h2><p class="mt-1 text-sm text-pm-text-secondary">{{ $it ? 'Se inserisci il prezzo, PitMetric crea automaticamente anche la voce nel registro spese.' : 'If you enter the price, PitMetric automatically creates the matching expense entry.' }}</p></div>
                <form method="POST" action="{{ route('components.store') }}" class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @csrf
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Nome' : 'Name' }}</span><input class="pm-input" name="name" required maxlength="120" value="{{ old('name') }}" placeholder="Engine #01"></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Tipo' : 'Type' }}</span><input class="pm-input" name="type_name" required maxlength="100" value="{{ old('type_name') }}" placeholder="Engine"></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Metrica principale' : 'Primary metric' }}</span><select class="pm-input" name="metric_key" required>@foreach ($metrics as $metric)<option value="{{ $metric->key }}" @selected(old('metric_key', 'runtime') === $metric->key)>{{ $metric->name }} · {{ $metric->display_unit }}</option>@endforeach</select></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Seriale / codice' : 'Serial / code' }}</span><input class="pm-input" name="serial_number" maxlength="120" value="{{ old('serial_number') }}"></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Marca' : 'Manufacturer' }}</span><input class="pm-input" name="manufacturer" maxlength="100" value="{{ old('manufacturer') }}"></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Modello' : 'Model' }}</span><input class="pm-input" name="model" maxlength="100" value="{{ old('model') }}"></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Data acquisto' : 'Purchase date' }}</span><input class="pm-input" name="purchase_date" type="date" value="{{ old('purchase_date') }}"></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Costo acquisto (€)' : 'Purchase cost (€)' }}</span><input class="pm-input" name="purchase_cost" type="number" min="0" max="1000000" step="0.01" value="{{ old('purchase_cost') }}"></label>
                    <label class="grid gap-2 md:col-span-2 xl:col-span-4"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><input class="pm-input" name="notes" maxlength="2000" value="{{ old('notes') }}"></label>
                    <div class="md:col-span-2 xl:col-span-4"><button class="pm-race-button" type="submit">{{ $it ? 'Salva componente' : 'Save component' }}</button></div>
                </form>
            </section>

            <section class="pm-panel p-5 sm:p-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div><h2 class="text-lg font-black text-pm-text">{{ $it ? 'Montaggio fisico' : 'Physical installation' }}</h2><p class="mt-1 text-sm text-pm-text-secondary">{{ $it ? 'Registra dove si trova davvero un componente. La configurazione rimane uno snapshot storico separato.' : 'Record where a component is physically installed. Configuration remains a separate historical snapshot.' }}</p></div>
                </div>
                <form method="POST" action="{{ route('component-installations.store') }}" class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @csrf
                    <label class="grid gap-2 xl:col-span-2"><span class="pm-label">{{ $it ? 'Componente disponibile' : 'Available component' }}</span><select class="pm-input" name="component_id" required><option value="">{{ $it ? 'Seleziona componente' : 'Select component' }}</option>@foreach ($availableComponents as $component)<option value="{{ $component->id }}" @selected(old('component_id') === (string) $component->id)>{{ $component->name }} · {{ $component->type->name }}</option>@endforeach</select></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Mezzo' : 'Vehicle' }}</span><select class="pm-input" name="vehicle_id" required><option value="">{{ $it ? 'Seleziona mezzo' : 'Select vehicle' }}</option>@foreach ($vehicles as $vehicle)<option value="{{ $vehicle->id }}" @selected((string) old('vehicle_id') === (string) $vehicle->id)>{{ $vehicle->name }}</option>@endforeach</select></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Posizione / ruolo' : 'Position / role' }}</span><input class="pm-input" name="position_or_role" maxlength="100" value="{{ old('position_or_role') }}" placeholder="Engine / rear axle"></label>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Montato il' : 'Installed at' }}</span><input class="pm-input" name="installed_at" type="datetime-local" required value="{{ old('installed_at', now()->format('Y-m-d\TH:i')) }}"></label>
                    <label class="grid gap-2 md:col-span-1 xl:col-span-3"><span class="pm-label">{{ $it ? 'Note montaggio' : 'Installation notes' }}</span><input class="pm-input" name="notes" maxlength="2000" value="{{ old('notes') }}"></label>
                    <div class="md:col-span-2 xl:col-span-4"><button class="pm-race-button" type="submit" @disabled($availableComponents->isEmpty() || $vehicles->isEmpty())>{{ $it ? 'Registra montaggio' : 'Record installation' }}</button></div>
                </form>
            </section>

            @if ($components->isEmpty())
                <x-pitmetric.empty-state :title="$it ? 'Nessun componente' : 'No components'" :description="$it ? 'Crea il primo componente per iniziare a costruire configurazioni reali.' : 'Create the first component to start building real configurations.'" />
            @else
                <section class="pm-panel overflow-hidden"><div class="overflow-x-auto"><table class="w-full min-w-[1180px] text-left text-sm"><thead class="border-b border-pm-border bg-pm-subtle text-[11px] uppercase tracking-[0.1em] text-pm-muted"><tr><th class="px-4 py-3">Component</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">{{ $it ? 'Montato su' : 'Installed on' }}</th><th class="px-4 py-3">Metric</th><th class="px-4 py-3">{{ $it ? 'Dall’ultimo service' : 'Since service' }}</th><th class="px-4 py-3">Lifetime</th><th class="px-4 py-3">{{ $it ? 'Acquisto' : 'Purchase' }}</th><th class="px-4 py-3"></th></tr></thead><tbody class="divide-y divide-pm-border">
                    @foreach ($components as $component)
                        @php($tracker = $component->trackers->first())
                        @php($installation = $component->activeInstallation)
                        <tr>
                            <td class="px-4 py-4"><p class="font-bold text-pm-text">{{ $component->name }}</p><p class="mt-1 text-xs text-pm-muted">{{ $component->serial_number ?: '—' }}</p></td>
                            <td class="px-4 py-4 text-pm-text-secondary">{{ $component->type->name }}</td>
                            <td class="px-4 py-4">@if ($installation)<p class="font-bold text-pm-text">{{ $installation->vehicle->name }}</p><p class="mt-1 text-xs text-pm-muted">{{ $installation->position_or_role ?: ($it ? 'Posizione non specificata' : 'Position not specified') }} · {{ $installation->installed_at->format('d/m/Y H:i') }}</p><details class="mt-2"><summary class="cursor-pointer text-xs font-bold text-pm-accent">{{ $it ? 'Rimuovi dal mezzo' : 'Remove from vehicle' }}</summary><form method="POST" action="{{ route('component-installations.remove', $installation) }}" class="mt-2 flex items-end gap-2">@csrf @method('PATCH')<label class="grid gap-1"><span class="text-[10px] uppercase text-pm-muted">{{ $it ? 'Rimosso il' : 'Removed at' }}</span><input class="pm-input py-2 text-xs" name="removed_at" type="datetime-local" required value="{{ now()->format('Y-m-d\TH:i') }}"></label><button class="pm-ghost-button" type="submit">{{ $it ? 'Conferma' : 'Confirm' }}</button></form></details>@else<span class="text-pm-muted">{{ $it ? 'Disponibile' : 'Available' }}</span>@endif</td>
                            <td class="px-4 py-4 text-pm-text-secondary">{{ $tracker?->metric?->name ?? '—' }}</td>
                            <td class="px-4 py-4 font-mono text-pm-text">{{ $tracker ? $formatUsage($usage[$tracker->id]['since_service'], $tracker->metric) : '—' }}</td>
                            <td class="px-4 py-4 font-mono text-pm-text">{{ $tracker ? $formatUsage($usage[$tracker->id]['lifetime'], $tracker->metric) : '—' }}</td>
                            <td class="px-4 py-4 text-pm-text-secondary">@if ($component->purchase_cost_cents !== null)<p class="font-mono font-bold text-pm-text">€ {{ number_format($component->purchase_cost_cents / 100, 2, ',', '.') }}</p><p class="mt-1 text-xs text-pm-muted">{{ $component->purchase_date?->format('d/m/Y') }}</p>@else — @endif</td>
                            <td class="px-4 py-4 text-right"><form method="POST" action="{{ route('components.destroy', $component) }}" onsubmit="return confirm(@js($it ? 'Archiviare questo componente?' : 'Archive this component?'))">@csrf @method('DELETE')<button class="text-xs font-bold text-pm-danger hover:underline" @disabled($installation)>{{ $it ? 'Archivia' : 'Archive' }}</button></form></td>
                        </tr>
                    @endforeach
                </tbody></table></div></section>
            @endif
        </div>
    </div>
</x-layouts::app>
