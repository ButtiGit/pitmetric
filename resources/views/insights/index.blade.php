@php
    $it = app()->getLocale() === 'it';
    $summary = $intelligence['summary'];
    $money = fn (?int $cents) => $cents === null ? 'N/D' : number_format($cents / 100, 2, ',', '.').' €';
    $km = fn (int $meters) => number_format($meters / 1000, 1, ',', '.').' km';
    $hours = fn (int $seconds) => number_format($seconds / 3600, 1, ',', '.').' h';
@endphp

<x-layouts::app :title="$it ? 'Intelligence e report' : 'Intelligence & Reports'">
    <div class="pitmetric-app bg-pm-page min-h-full">
        <div class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
            <x-pitmetric.page-header
                :eyebrow="$it ? 'PITMETRIC INTELLIGENCE' : 'PITMETRIC INTELLIGENCE'"
                :title="$it ? 'Intelligence e report' : 'Intelligence & Reports'"
                :description="$it
                    ? 'Trasforma sessioni, chilometri, tempo pista e costi in indicatori operativi confrontabili.'
                    : 'Turn sessions, distance, track time and costs into comparable operational intelligence.'"
            />

            <section class="pm-panel p-4 sm:p-5">
                <form method="GET" action="{{ route('insights.index') }}" class="grid gap-4 sm:grid-cols-[1fr_1fr_auto_auto] sm:items-end">
                    <label class="space-y-1.5">
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-500 dark:text-zinc-400">{{ $it ? 'Dal' : 'From' }}</span>
                        <input class="pm-input w-full" type="date" name="from" value="{{ $filters['from'] }}">
                    </label>
                    <label class="space-y-1.5">
                        <span class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-500 dark:text-zinc-400">{{ $it ? 'Al' : 'To' }}</span>
                        <input class="pm-input w-full" type="date" name="to" value="{{ $filters['to'] }}">
                    </label>
                    <button class="pm-race-button justify-center" type="submit">{{ $it ? 'Applica' : 'Apply' }}</button>
                    <a class="pm-ghost-button justify-center" href="{{ route('insights.index') }}" wire:navigate>{{ $it ? 'Azzera' : 'Reset' }}</a>
                </form>
                <p class="mt-3 text-xs leading-5 text-zinc-500 dark:text-zinc-400">
                    {{ $it
                        ? 'Costo/km e costo/ora dividono tutte le spese registrate nel periodo per la distanza e il tempo delle sessioni finalizzate. Se manca il denominatore, PitMetric mostra N/D.'
                        : 'Cost/km and cost/hour divide all recorded expenses in the period by finalized-session distance and runtime. When the denominator is missing, PitMetric shows N/A.' }}
                </p>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                <x-pitmetric.metric-card :label="$it ? 'Sessioni' : 'Sessions'" :value="(string) $summary['sessions']" />
                <x-pitmetric.metric-card :label="$it ? 'Distanza' : 'Distance'" :value="$km($summary['distance_meters'])" />
                <x-pitmetric.metric-card :label="$it ? 'Tempo pista' : 'Track time'" :value="$hours($summary['duration_seconds'])" />
                <x-pitmetric.metric-card :label="$it ? 'Spesa' : 'Spend'" :value="$money($summary['cost_cents'])" />
                <x-pitmetric.metric-card :label="$it ? 'Costo / km' : 'Cost / km'" :value="$money($summary['cost_per_km_cents'])" />
                <x-pitmetric.metric-card :label="$it ? 'Costo / ora' : 'Cost / hour'" :value="$money($summary['cost_per_hour_cents'])" />
            </section>

            <div class="grid gap-6 xl:grid-cols-2">
                <section class="pm-panel overflow-hidden">
                    <div class="border-b border-zinc-200/80 px-5 py-4 dark:border-white/10">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">{{ $it ? 'CONFRONTO PILOTI' : 'DRIVER COMPARISON' }}</p>
                        <h2 class="mt-1 text-lg font-bold text-zinc-950 dark:text-white">{{ $it ? 'Attività per pilota' : 'Activity by driver' }}</h2>
                    </div>

                    @if ($intelligence['drivers']->isEmpty())
                        <div class="p-5 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $it ? 'Servono sessioni collegate a un ingresso evento e a un pilota.' : 'Sessions linked to an event entry and driver are required.' }}
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="bg-zinc-50 text-xs uppercase tracking-[0.12em] text-zinc-500 dark:bg-white/[0.03] dark:text-zinc-400">
                                    <tr>
                                        <th class="px-5 py-3">{{ $it ? 'Pilota' : 'Driver' }}</th>
                                        <th class="px-3 py-3 text-right">{{ $it ? 'Weekend' : 'Weekends' }}</th>
                                        <th class="px-3 py-3 text-right">{{ $it ? 'Sessioni' : 'Sessions' }}</th>
                                        <th class="px-3 py-3 text-right">{{ $it ? 'Giri' : 'Laps' }}</th>
                                        <th class="px-3 py-3 text-right">km</th>
                                        <th class="px-5 py-3 text-right">h</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200/80 dark:divide-white/10">
                                    @foreach ($intelligence['drivers'] as $driver)
                                        <tr>
                                            <td class="px-5 py-3 font-semibold text-zinc-950 dark:text-white">
                                                {{ $driver['name'] }}
                                                @if ($driver['racing_number'])
                                                    <span class="ml-1 text-xs font-medium text-zinc-400">#{{ $driver['racing_number'] }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ $driver['weekends'] }}</td>
                                            <td class="px-3 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ $driver['sessions'] }}</td>
                                            <td class="px-3 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ $driver['laps'] }}</td>
                                            <td class="px-3 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ number_format($driver['distance_meters'] / 1000, 1, ',', '.') }}</td>
                                            <td class="px-5 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ number_format($driver['duration_seconds'] / 3600, 1, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                <section class="pm-panel overflow-hidden">
                    <div class="border-b border-zinc-200/80 px-5 py-4 dark:border-white/10">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">{{ $it ? 'CONFRONTO MEZZI' : 'VEHICLE COMPARISON' }}</p>
                        <h2 class="mt-1 text-lg font-bold text-zinc-950 dark:text-white">{{ $it ? 'Utilizzo per mezzo' : 'Usage by vehicle' }}</h2>
                    </div>

                    @if ($intelligence['vehicles']->isEmpty())
                        <div class="p-5 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $it ? 'Nessuna sessione finalizzata nel periodo.' : 'No finalized sessions in this period.' }}
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="bg-zinc-50 text-xs uppercase tracking-[0.12em] text-zinc-500 dark:bg-white/[0.03] dark:text-zinc-400">
                                    <tr>
                                        <th class="px-5 py-3">{{ $it ? 'Mezzo' : 'Vehicle' }}</th>
                                        <th class="px-3 py-3 text-right">{{ $it ? 'Weekend' : 'Weekends' }}</th>
                                        <th class="px-3 py-3 text-right">{{ $it ? 'Sessioni' : 'Sessions' }}</th>
                                        <th class="px-3 py-3 text-right">{{ $it ? 'Giri' : 'Laps' }}</th>
                                        <th class="px-3 py-3 text-right">km</th>
                                        <th class="px-5 py-3 text-right">h</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200/80 dark:divide-white/10">
                                    @foreach ($intelligence['vehicles'] as $vehicle)
                                        <tr>
                                            <td class="px-5 py-3 font-semibold text-zinc-950 dark:text-white">{{ $vehicle['name'] }}</td>
                                            <td class="px-3 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ $vehicle['weekends'] }}</td>
                                            <td class="px-3 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ $vehicle['sessions'] }}</td>
                                            <td class="px-3 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ $vehicle['laps'] }}</td>
                                            <td class="px-3 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ number_format($vehicle['distance_meters'] / 1000, 1, ',', '.') }}</td>
                                            <td class="px-5 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ number_format($vehicle['duration_seconds'] / 3600, 1, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>

            <section class="pm-panel overflow-hidden">
                <div class="flex flex-col gap-2 border-b border-zinc-200/80 px-5 py-4 sm:flex-row sm:items-end sm:justify-between dark:border-white/10">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">{{ $it ? 'WEEKEND INTELLIGENCE' : 'WEEKEND INTELLIGENCE' }}</p>
                        <h2 class="mt-1 text-lg font-bold text-zinc-950 dark:text-white">{{ $it ? 'Weekend recenti' : 'Recent weekends' }}</h2>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $it ? 'Apri il report completo per sessioni, costi e operazioni.' : 'Open the full report for sessions, costs and operations.' }}</p>
                </div>

                @if ($intelligence['weekends']->isEmpty())
                    <div class="p-5 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $it ? 'Nessun weekend disponibile per il periodo selezionato.' : 'No weekends available for the selected period.' }}
                    </div>
                @else
                    <div class="divide-y divide-zinc-200/80 dark:divide-white/10">
                        @foreach ($intelligence['weekends'] as $weekend)
                            @php($event = $weekend['event'])
                            <article class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1.6fr)_repeat(4,minmax(0,.65fr))_auto] lg:items-center">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="truncate font-bold text-zinc-950 dark:text-white">{{ $event->name }}</h3>
                                        <x-pitmetric.status-badge :status="$event->status" />
                                    </div>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $event->start_date->format('d/m/Y') }}–{{ $event->end_date->format('d/m/Y') }}
                                        @if ($event->circuitLayout?->circuit)
                                            · {{ $event->circuitLayout->circuit->name }}
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ $it ? 'Sessioni' : 'Sessions' }}</p>
                                    <p class="mt-1 font-bold text-zinc-950 dark:text-white">{{ $weekend['sessions'] }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ $it ? 'Spesa' : 'Spend' }}</p>
                                    <p class="mt-1 font-bold text-zinc-950 dark:text-white">{{ $money($weekend['cost_cents']) }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ $it ? 'Costo/km' : 'Cost/km' }}</p>
                                    <p class="mt-1 font-bold text-zinc-950 dark:text-white">{{ $money($weekend['cost_per_km_cents']) }}</p>
                                </div>
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-zinc-400">{{ $it ? 'Costo/ora' : 'Cost/hour' }}</p>
                                    <p class="mt-1 font-bold text-zinc-950 dark:text-white">{{ $money($weekend['cost_per_hour_cents']) }}</p>
                                </div>
                                <a class="pm-race-button justify-center" href="{{ route('events.report', $event) }}" wire:navigate>
                                    {{ $it ? 'Weekend Report' : 'Weekend Report' }}
                                </a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="grid gap-6 lg:grid-cols-[1.2fr_.8fr]">
                <div class="pm-panel p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">{{ $it ? 'LETTURA DATI' : 'DATA INTERPRETATION' }}</p>
                    <h2 class="mt-1 text-lg font-bold text-zinc-950 dark:text-white">{{ $it ? 'Cosa significa davvero il costo operativo' : 'What operational cost actually means' }}</h2>
                    <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                        {{ $it
                            ? 'PitMetric non prova ad attribuire automaticamente ogni spesa a un singolo pilota o mezzo quando il dato non lo consente. I KPI generali usano la spesa totale del periodo; i Weekend Report separano invece i costi direttamente collegati a ingresso, sessione e task dai costi condivisi.'
                            : 'PitMetric does not automatically attribute every expense to a driver or vehicle when the source data cannot support it. Overall KPIs use total period spend; Weekend Reports separately expose costs directly linked to entries, sessions and tasks from shared costs.' }}
                    </p>
                </div>

                <div class="pm-panel p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">{{ $it ? 'CATEGORIE COSTO' : 'COST CATEGORIES' }}</p>
                    <div class="mt-3 space-y-3">
                        @forelse ($intelligence['expense_categories']->take(6) as $category)
                            <div class="flex items-center justify-between gap-3 border-b border-zinc-200/70 pb-2 last:border-0 dark:border-white/10">
                                <span class="truncate text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ str_replace('_', ' ', ucfirst($category['category'])) }}</span>
                                <span class="whitespace-nowrap text-sm font-bold text-zinc-950 dark:text-white">{{ $money($category['amount_cents']) }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $it ? 'Nessuna spesa registrata.' : 'No expenses recorded.' }}</p>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-layouts::app>
