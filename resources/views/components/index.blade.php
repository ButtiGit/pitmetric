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
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1360px] space-y-5">
            <div><p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">COMPONENTS</p><x-pitmetric.page-header :title="$it ? 'Componenti reali' : 'Real components'" :description="$it ? 'Ogni componente vive nel database: utilizzo, storico service e costo di acquisto restano collegati allo stesso oggetto.' : 'Every component lives in the database: usage, service history and purchase cost stay linked to the same object.'" /></div>

            @if (session('status'))<div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>@endif
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

            @if ($components->isEmpty())
                <x-pitmetric.empty-state :title="$it ? 'Nessun componente' : 'No components'" :description="$it ? 'Crea il primo componente per iniziare a costruire configurazioni reali.' : 'Create the first component to start building real configurations.'" />
            @else
                <section class="pm-panel overflow-hidden"><div class="overflow-x-auto"><table class="w-full min-w-[900px] text-left text-sm"><thead class="border-b border-pm-border bg-pm-subtle text-[11px] uppercase tracking-[0.1em] text-pm-muted"><tr><th class="px-4 py-3">Component</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Metric</th><th class="px-4 py-3">{{ $it ? 'Dall’ultimo service' : 'Since service' }}</th><th class="px-4 py-3">Lifetime</th><th class="px-4 py-3">{{ $it ? 'Acquisto' : 'Purchase' }}</th><th class="px-4 py-3"></th></tr></thead><tbody class="divide-y divide-pm-border">
                    @foreach ($components as $component)
                        @php($tracker = $component->trackers->first())
                        <tr><td class="px-4 py-4"><p class="font-bold text-pm-text">{{ $component->name }}</p><p class="mt-1 text-xs text-pm-muted">{{ $component->serial_number ?: '—' }}</p></td><td class="px-4 py-4 text-pm-text-secondary">{{ $component->type->name }}</td><td class="px-4 py-4 text-pm-text-secondary">{{ $tracker?->metric?->name ?? '—' }}</td><td class="px-4 py-4 font-mono text-pm-text">{{ $tracker ? $formatUsage($usage[$tracker->id]['since_service'], $tracker->metric) : '—' }}</td><td class="px-4 py-4 font-mono text-pm-text">{{ $tracker ? $formatUsage($usage[$tracker->id]['lifetime'], $tracker->metric) : '—' }}</td><td class="px-4 py-4 text-pm-text-secondary">@if ($component->purchase_cost_cents !== null)<p class="font-mono font-bold text-pm-text">€ {{ number_format($component->purchase_cost_cents / 100, 2, ',', '.') }}</p><p class="mt-1 text-xs text-pm-muted">{{ $component->purchase_date?->format('d/m/Y') }}</p>@else — @endif</td><td class="px-4 py-4 text-right"><form method="POST" action="{{ route('components.destroy', $component) }}" onsubmit="return confirm(@js($it ? 'Archiviare questo componente?' : 'Archive this component?'))">@csrf @method('DELETE')<button class="text-xs font-bold text-pm-danger hover:underline">{{ $it ? 'Archivia' : 'Archive' }}</button></form></td></tr>
                    @endforeach
                </tbody></table></div></section>
            @endif
        </div>
    </div>
</x-layouts::app>
