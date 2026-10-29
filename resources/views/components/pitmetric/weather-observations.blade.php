@props(['session', 'history', 'ready', 'page', 'it'])

@php
    $latest = $history->first();
    $current = $latest && ! $latest->is_reset ? $latest : null;
    $values = $current?->values ?? [];
    $fields = [
        ['name' => 'air_temperature_c', 'label' => $it ? 'Temperatura aria' : 'Air temperature', 'unit' => '°C', 'min' => -70, 'max' => 70],
        ['name' => 'track_temperature_c', 'label' => $it ? 'Temperatura asfalto' : 'Track temperature', 'unit' => '°C', 'min' => -50, 'max' => 100],
        ['name' => 'humidity_pct', 'label' => $it ? 'Umidità' : 'Humidity', 'unit' => '%', 'min' => 0, 'max' => 100],
        ['name' => 'wind_speed_kmh', 'label' => $it ? 'Vento' : 'Wind', 'unit' => 'km/h', 'min' => 0, 'max' => 350],
        ['name' => 'pressure_hpa', 'label' => $it ? 'Pressione' : 'Pressure', 'unit' => 'hPa', 'min' => 500, 'max' => 1200],
        ['name' => 'cloud_cover_pct', 'label' => $it ? 'Nuvolosità' : 'Cloud cover', 'unit' => '%', 'min' => 0, 'max' => 100],
        ['name' => 'precipitation_next_hour_mm', 'label' => $it ? 'Precipitazioni in 1h' : 'Precipitation in 1h', 'unit' => 'mm', 'min' => 0, 'max' => 500],
    ];
    $conditions = [
        'clear' => $it ? 'Sereno' : 'Clear',
        'partly_cloudy' => $it ? 'Parzialmente nuvoloso' : 'Partly cloudy',
        'cloudy' => $it ? 'Coperto' : 'Cloudy',
        'rain' => $it ? 'Pioggia' : 'Rain',
        'storm' => $it ? 'Temporale' : 'Storm',
        'snow' => $it ? 'Neve' : 'Snow',
        'other' => $it ? 'Altro' : 'Other',
    ];
    $fieldLabels = collect($fields)->pluck('label', 'name')->all();
    $dialogId = 'weather-observation-'.$session->id;
@endphp

@if ($ready)
    <section class="pm-weather-glass relative mt-6 min-w-0 rounded-2xl p-4 sm:p-5" aria-labelledby="weather-observation-title" data-pm-weather-observations>
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.15em] text-white/70">{{ $it ? 'MISURAZIONI IN PISTA' : 'TRACKSIDE MEASUREMENTS' }}</p>
                <h3 id="weather-observation-title" class="mt-1 text-lg font-bold text-white">{{ $it ? 'Rilevazioni degli operatori' : 'Operator observations' }}</h3>
                <p class="mt-1 text-xs leading-5 text-white/75">{{ $it ? 'Valori misurati o corretti manualmente: separati dalle previsioni MET Norway.' : 'Manually measured or corrected values, separate from MET Norway forecasts.' }}</p>
            </div>
            @can('update', $session)
                <x-crud-modal :id="$dialogId" :title="$it ? 'Registra o correggi meteo' : 'Record or correct weather'" :description="$it ? 'I campi vuoti non saranno considerati misurazioni. Ogni salvataggio crea una revisione consultabile.' : 'Empty fields are not measured values. Every save creates a traceable revision.'" :trigger="$it ? 'Modifica rilevazioni' : 'Edit observations'" trigger-class="pm-weather-glass-button min-h-11 rounded-xl px-4 py-2.5 text-sm font-bold" :permission="null" size="max-w-2xl">
                    <form method="POST" action="{{ route('sessions.weather-observations.store', $session) }}" class="grid gap-3 sm:grid-cols-2">
                        @csrf
                        <input type="hidden" name="_pm_dialog" value="{{ $dialogId }}">
                        <input type="hidden" name="page" value="{{ $page }}">
                        @foreach ($fields as $field)
                            <label class="grid min-w-0 gap-1.5">
                                <span class="pm-label">{{ $field['label'] }} ({{ $field['unit'] }})</span>
                                <input class="pm-input min-h-11" type="number" inputmode="decimal" step="0.1" min="{{ $field['min'] }}" max="{{ $field['max'] }}" name="{{ $field['name'] }}" value="{{ old($field['name'], $values[$field['name']] ?? '') }}" placeholder="—">
                            </label>
                        @endforeach
                        <label class="grid gap-1.5">
                            <span class="pm-label">{{ $it ? 'Condizioni osservate' : 'Observed conditions' }}</span>
                            <select name="condition" class="pm-input min-h-11">
                                <option value="">{{ $it ? 'Non rilevate' : 'Not measured' }}</option>
                                @foreach ($conditions as $key => $label)
                                    <option value="{{ $key }}" @selected(old('condition', $values['condition'] ?? '') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="grid gap-1.5 sm:col-span-2">
                            <span class="pm-label">{{ $it ? 'Data e ora della misurazione' : 'Measurement date and time' }}</span>
                            <input class="pm-input min-h-11" type="datetime-local" name="measured_at" value="{{ old('measured_at', now()->timezone('Europe/Rome')->format('Y-m-d\TH:i')) }}">
                        </label>
                        <label class="grid gap-1.5 sm:col-span-2">
                            <span class="pm-label">{{ $it ? 'Note operatore' : 'Operator notes' }}</span>
                            <textarea class="pm-input min-h-24" name="notes" maxlength="1000" placeholder="{{ $it ? 'Es. asfalto misurato con termometro IR' : 'E.g. track measured with an IR thermometer' }}">{{ old('notes', $current?->notes) }}</textarea>
                        </label>
                        <p class="text-xs leading-5 text-pm-muted sm:col-span-2">{{ $it ? 'La temperatura dell’asfalto non viene mai dedotta dalla temperatura dell’aria.' : 'Track temperature is never inferred from air temperature.' }}</p>
                        <div class="flex justify-end sm:col-span-2"><button class="pm-race-button min-h-11" type="submit">{{ $it ? 'Salva rilevazione' : 'Save observation' }}</button></div>
                    </form>
                </x-crud-modal>
            @endcan
        </div>

        @if ($current)
            <p class="mt-3 text-xs text-white/75">{{ $it ? 'Rilevata' : 'Measured' }} {{ $current->measured_at->timezone('Europe/Rome')->format('d/m/Y H:i') }} · {{ $current->creator?->name ?? ($it ? 'Operatore' : 'Operator') }}</p>
        @endif

        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-2xl border border-white/25 bg-white/15 p-4 sm:col-span-2">
                <p class="text-[10px] font-bold uppercase tracking-[0.13em] text-white/80">{{ $it ? 'Temperatura asfalto · misurata' : 'Track temperature · measured' }}</p>
                <p class="mt-2 text-3xl font-light tracking-tight text-white">{{ isset($values['track_temperature_c']) ? number_format((float) $values['track_temperature_c'], 1, ',', '.') . ' °C' : '—' }}</p>
                <p class="mt-1 text-xs text-white/70">{{ $it ? 'Non fornita da MET Norway' : 'Not provided by MET Norway' }}</p>
            </div>
            @foreach ($fields as $field)
                @if ($field['name'] !== 'track_temperature_c' && isset($values[$field['name']]))
                    <div class="rounded-2xl border border-white/15 bg-white/10 p-3.5">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.1em] text-white/70">{{ $field['label'] }}</p>
                        <p class="mt-2 text-lg font-bold text-white">{{ number_format((float) $values[$field['name']], 1, ',', '.') }} {{ $field['unit'] }}</p>
                    </div>
                @endif
            @endforeach
            @if (isset($values['condition']))
                <div class="rounded-2xl border border-white/15 bg-white/10 p-3.5">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.1em] text-white/70">{{ $it ? 'Condizioni' : 'Conditions' }}</p>
                    <p class="mt-2 text-sm font-bold text-white">{{ $conditions[$values['condition']] ?? $values['condition'] }}</p>
                </div>
            @endif
        </div>

        @if ($current?->notes)
            <p class="mt-3 whitespace-pre-wrap text-sm text-white/85">{{ $current->notes }}</p>
        @elseif (! $current)
            <p class="mt-3 text-xs text-white/75">{{ $it ? 'Nessuna misurazione manuale attiva. Puoi aggiungerla anche senza una previsione online.' : 'No active manual readings. You can record them even without an online forecast.' }}</p>
        @endif

        @if ($history->isNotEmpty())
            <details class="mt-4 border-t border-white/20 pt-3 text-xs text-white/85">
                <summary class="w-fit cursor-pointer py-2 font-bold underline underline-offset-4">{{ $it ? 'Storico delle rilevazioni' : 'Observation history' }}</summary>
                <ol class="mt-2 grid gap-2">
                    @foreach ($history as $observation)
                        <li class="rounded-xl border border-white/15 bg-white/10 p-3">
                            <p class="font-bold">{{ $observation->is_reset ? ($it ? 'Ripristino valori manuali' : 'Manual values reset') : ($it ? 'Rilevazione operatore' : 'Operator observation') }} · {{ $observation->created_at->timezone('Europe/Rome')->format('d/m/Y H:i') }}</p>
                            <p class="mt-1 text-white/75">{{ $observation->creator?->name ?? ($it ? 'Operatore' : 'Operator') }} · {{ $it ? 'Misurata' : 'Measured' }} {{ $observation->measured_at->timezone('Europe/Rome')->format('d/m/Y H:i') }}</p>
                            @if ($observation->values)
                                <p class="mt-1 break-words text-white/85">@foreach ($observation->values as $name => $value){{ $loop->first ? '' : ' · ' }}{{ $fieldLabels[$name] ?? ($name === 'condition' ? ($it ? 'Condizioni' : 'Conditions') : $name) }}: {{ $name === 'condition' ? ($conditions[$value] ?? $value) : $value }}@endforeach</p>
                            @endif
                            @if ($observation->notes)<p class="mt-1 whitespace-pre-wrap text-white/75">{{ $observation->notes }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            </details>
        @endif

        @if ($current)
            @can('update', $session)
                <div class="mt-3">
                    <x-crud-modal id="reset-weather-observation-{{ $session->id }}" :title="$it ? 'Disattiva rilevazioni manuali' : 'Clear manual readings'" :description="$it ? 'I valori manuali non saranno più attivi. Nessuna previsione né revisione precedente verrà eliminata.' : 'Manual values will no longer be active. No forecast or previous revision will be deleted.'" :trigger="$it ? 'Disattiva rilevazioni manuali' : 'Clear manual readings'" trigger-class="text-xs font-bold text-white/80 underline underline-offset-4" :permission="null" size="max-w-lg">
                        <form method="POST" action="{{ route('sessions.weather-observations.store', $session) }}" class="grid gap-3">
                            @csrf
                            <input type="hidden" name="reset" value="1">
                            <input type="hidden" name="page" value="{{ $page }}">
                            <p class="text-sm text-pm-text-secondary">{{ $it ? 'Conferma il ripristino: lo storico resterà disponibile.' : 'Confirm the reset: the history remains available.' }}</p>
                            <button type="submit" class="pm-race-button min-h-11">{{ $it ? 'Conferma ripristino' : 'Confirm reset' }}</button>
                        </form>
                    </x-crud-modal>
                </div>
            @endcan
        @endif
    </section>
@endif
