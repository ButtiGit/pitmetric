@php
    $it = app()->getLocale() === 'it';
    $event = $report['event'];
    $summary = $report['summary'];
    $money = fn (?int $cents) => $cents === null ? 'N/D' : number_format($cents / 100, 2, ',', '.').' €';
    $km = fn (int $meters) => number_format($meters / 1000, 1, ',', '.').' km';
    $hours = fn (int $seconds) => number_format($seconds / 3600, 1, ',', '.').' h';
@endphp

<x-layouts::app :title="'Weekend Report · '.$event->name">
    <style>
        @media print {
            .pm-report-actions, [data-pm-sidebar], header { display: none !important; }
            .pm-report-shell { max-width: none !important; padding: 0 !important; }
            .pm-report-card { break-inside: avoid; box-shadow: none !important; }
        }
    </style>

    <div class="pitmetric-app bg-pm-page min-h-full">
        <div class="pm-report-shell mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
            <div class="pm-report-actions flex flex-wrap items-center justify-between gap-3">
                <a class="pm-ghost-button" href="{{ route('insights.index') }}" wire:navigate>
                    {{ $it ? 'Torna a Intelligence' : 'Back to Intelligence' }}
                </a>
                <div class="flex flex-wrap gap-2">
                    <a class="pm-ghost-button" href="{{ route('events.report.csv', $event) }}">
                        {{ $it ? 'Esporta CSV' : 'Export CSV' }}
                    </a>
                    <button class="pm-race-button" type="button" onclick="window.print()">
                        {{ $it ? 'Stampa / Salva PDF' : 'Print / Save PDF' }}
                    </button>
                </div>
            </div>

            <section class="pm-report-card pm-panel overflow-hidden">
                <div class="border-b border-zinc-200/80 p-5 sm:p-7 dark:border-white/10">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-pm-accent">PITMETRIC WEEKEND REPORT</p>
                            <h1 class="mt-2 text-2xl font-black tracking-tight text-zinc-950 sm:text-3xl dark:text-white">{{ $event->name }}</h1>
                            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-zinc-500 dark:text-zinc-400">
                                <span>{{ $event->start_date->format('d/m/Y') }}–{{ $event->end_date->format('d/m/Y') }}</span>
                                @if ($event->championship)
                                    <span>{{ $event->championship }}</span>
                                @endif
                                @if ($event->round_label)
                                    <span>{{ $event->round_label }}</span>
                                @endif
                                @if ($event->circuitLayout?->circuit)
                                    <span>{{ $event->circuitLayout->circuit->name }} · {{ $event->circuitLayout->name }}</span>
                                @endif
                            </div>
                        </div>
                        <x-pitmetric.status-badge :status="$event->status" />
                    </div>
                </div>

                <div class="grid gap-px bg-zinc-200/80 sm:grid-cols-3 xl:grid-cols-6 dark:bg-white/10">
                    @foreach ([
                        [$it ? 'Sessioni' : 'Sessions', $summary['sessions']],
                        [$it ? 'Giri' : 'Laps', $summary['laps']],
                        [$it ? 'Distanza' : 'Distance', $km($summary['distance_meters'])],
                        [$it ? 'Tempo pista' : 'Track time', $hours($summary['duration_seconds'])],
                        [$it ? 'Costo / km' : 'Cost / km', $money($summary['cost_per_km_cents'])],
                        [$it ? 'Costo / ora' : 'Cost / hour', $money($summary['cost_per_hour_cents'])],
                    ] as [$label, $value])
                        <div class="bg-white p-4 dark:bg-zinc-950">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-zinc-400">{{ $label }}</p>
                            <p class="mt-1 text-lg font-black text-zinc-950 dark:text-white">{{ $value }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-col gap-2 bg-zinc-950 px-5 py-4 text-white sm:flex-row sm:items-center sm:justify-between dark:bg-white dark:text-zinc-950">
                    <span class="text-xs font-semibold uppercase tracking-[0.18em]">{{ $it ? 'Costo totale weekend' : 'Total weekend cost' }}</span>
                    <span class="text-2xl font-black">{{ $money($summary['cost_cents']) }}</span>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-3">
                <div class="pm-report-card pm-stat-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ $it ? 'Task completati' : 'Tasks completed' }}</p>
                    <p class="mt-2 text-2xl font-black text-zinc-950 dark:text-white">{{ $report['operations']['tasks_done'] }}/{{ $report['operations']['tasks_total'] }}</p>
                </div>
                <div class="pm-report-card pm-stat-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ $it ? 'Note operative' : 'Operational notes' }}</p>
                    <p class="mt-2 text-2xl font-black text-zinc-950 dark:text-white">{{ $report['operations']['notes'] }}</p>
                </div>
                <div class="pm-report-card pm-stat-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ $it ? 'Interventi manutenzione' : 'Maintenance interventions' }}</p>
                    <p class="mt-2 text-2xl font-black text-zinc-950 dark:text-white">{{ $report['operations']['maintenance_records'] }}</p>
                </div>
            </section>

            <section class="pm-report-card pm-panel overflow-hidden">
                <div class="border-b border-zinc-200/80 px-5 py-4 dark:border-white/10">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">{{ $it ? 'PILOTI E MEZZI' : 'DRIVERS & VEHICLES' }}</p>
                    <h2 class="mt-1 text-lg font-bold text-zinc-950 dark:text-white">{{ $it ? 'Riepilogo per ingresso' : 'Entry summary' }}</h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $it ? 'Il costo diretto include solo spese collegate esplicitamente a ingresso, sessioni e task di quell’ingresso.' : 'Direct cost only includes expenses explicitly linked to that entry, its sessions and its tasks.' }}
                    </p>
                </div>

                @if ($report['entries']->isEmpty())
                    <div class="p-5 text-sm text-zinc-500 dark:text-zinc-400">{{ $it ? 'Nessun ingresso registrato.' : 'No entries recorded.' }}</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-zinc-50 text-xs uppercase tracking-[0.12em] text-zinc-500 dark:bg-white/[0.03] dark:text-zinc-400">
                                <tr>
                                    <th class="px-5 py-3">{{ $it ? 'Pilota' : 'Driver' }}</th>
                                    <th class="px-3 py-3">{{ $it ? 'Mezzo' : 'Vehicle' }}</th>
                                    <th class="px-3 py-3 text-right">{{ $it ? 'Sessioni' : 'Sessions' }}</th>
                                    <th class="px-3 py-3 text-right">{{ $it ? 'Giri' : 'Laps' }}</th>
                                    <th class="px-3 py-3 text-right">km</th>
                                    <th class="px-3 py-3 text-right">h</th>
                                    <th class="px-5 py-3 text-right">{{ $it ? 'Costo diretto' : 'Direct cost' }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200/80 dark:divide-white/10">
                                @foreach ($report['entries'] as $row)
                                    <tr>
                                        <td class="px-5 py-3 font-semibold text-zinc-950 dark:text-white">
                                            {{ $row['entry']->driver->display_name }}
                                            @if ($row['entry']->entry_number)
                                                <span class="ml-1 text-xs text-zinc-400">#{{ $row['entry']->entry_number }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300">{{ $row['entry']->vehicle->name }}</td>
                                        <td class="px-3 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ $row['sessions'] }}</td>
                                        <td class="px-3 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ $row['laps'] }}</td>
                                        <td class="px-3 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ number_format($row['distance_meters'] / 1000, 1, ',', '.') }}</td>
                                        <td class="px-3 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ number_format($row['duration_seconds'] / 3600, 1, ',', '.') }}</td>
                                        <td class="px-5 py-3 text-right font-bold text-zinc-950 dark:text-white">{{ $money($row['direct_cost_cents']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="pm-report-card pm-panel overflow-hidden">
                <div class="border-b border-zinc-200/80 px-5 py-4 dark:border-white/10">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">{{ $it ? 'STINT E SESSIONI' : 'STINTS & SESSIONS' }}</p>
                    <h2 class="mt-1 text-lg font-bold text-zinc-950 dark:text-white">{{ $it ? 'Cronologia pista' : 'Track history' }}</h2>
                </div>

                @if ($report['sessions']->isEmpty())
                    <div class="p-5 text-sm text-zinc-500 dark:text-zinc-400">{{ $it ? 'Nessuna sessione finalizzata.' : 'No finalized sessions.' }}</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-left text-sm">
                            <thead class="bg-zinc-50 text-xs uppercase tracking-[0.12em] text-zinc-500 dark:bg-white/[0.03] dark:text-zinc-400">
                                <tr>
                                    <th class="px-5 py-3">{{ $it ? 'Data' : 'Date' }}</th>
                                    <th class="px-3 py-3">{{ $it ? 'Tipo' : 'Type' }}</th>
                                    <th class="px-3 py-3">{{ $it ? 'Pilota' : 'Driver' }}</th>
                                    <th class="px-3 py-3">{{ $it ? 'Mezzo' : 'Vehicle' }}</th>
                                    <th class="px-3 py-3 text-right">{{ $it ? 'Giri' : 'Laps' }}</th>
                                    <th class="px-3 py-3 text-right">km</th>
                                    <th class="px-3 py-3 text-right">min</th>
                                    <th class="px-3 py-3">Setup</th>
                                    <th class="px-5 py-3 text-right">{{ $it ? 'Costo diretto' : 'Direct cost' }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200/80 dark:divide-white/10">
                                @foreach ($report['sessions'] as $row)
                                    @php($session = $row['session'])
                                    <tr>
                                        <td class="px-5 py-3 whitespace-nowrap text-zinc-600 dark:text-zinc-300">{{ $session->started_at->format('d/m H:i') }}</td>
                                        <td class="px-3 py-3 font-medium text-zinc-950 dark:text-white">{{ ucfirst($session->session_type) }}</td>
                                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300">{{ $session->eventEntry?->driver?->display_name ?? '—' }}</td>
                                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300">{{ $session->vehicle?->name ?? '—' }}</td>
                                        <td class="px-3 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ $session->completed_laps ?? '—' }}</td>
                                        <td class="px-3 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ number_format($row['distance_meters'] / 1000, 1, ',', '.') }}</td>
                                        <td class="px-3 py-3 text-right text-zinc-600 dark:text-zinc-300">{{ $session->duration_seconds !== null ? number_format($session->duration_seconds / 60, 1, ',', '.') : '—' }}</td>
                                        <td class="px-3 py-3 text-zinc-600 dark:text-zinc-300">{{ $session->setupSnapshot?->name ?? '—' }}</td>
                                        <td class="px-5 py-3 text-right font-semibold text-zinc-950 dark:text-white">{{ $money($row['cost_cents']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="pm-report-card pm-panel overflow-hidden">
                    <div class="border-b border-zinc-200/80 px-5 py-4 dark:border-white/10">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">{{ $it ? 'COSTI' : 'COSTS' }}</p>
                        <h2 class="mt-1 text-lg font-bold text-zinc-950 dark:text-white">{{ $it ? 'Spesa per categoria' : 'Spend by category' }}</h2>
                    </div>
                    <div class="divide-y divide-zinc-200/80 dark:divide-white/10">
                        @forelse ($report['expense_categories'] as $category)
                            <div class="flex items-center justify-between gap-3 px-5 py-3">
                                <div>
                                    <p class="font-semibold text-zinc-950 dark:text-white">{{ str_replace('_', ' ', ucfirst($category['category'])) }}</p>
                                    <p class="text-xs text-zinc-400">{{ $category['count'] }} {{ $it ? 'voci' : 'items' }}</p>
                                </div>
                                <span class="font-black text-zinc-950 dark:text-white">{{ $money($category['amount_cents']) }}</span>
                            </div>
                        @empty
                            <div class="p-5 text-sm text-zinc-500 dark:text-zinc-400">{{ $it ? 'Nessuna spesa registrata.' : 'No expenses recorded.' }}</div>
                        @endforelse
                    </div>
                </section>

                <section class="pm-report-card pm-panel overflow-hidden">
                    <div class="border-b border-zinc-200/80 px-5 py-4 dark:border-white/10">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">{{ $it ? 'REGISTRO COSTI' : 'COST LEDGER' }}</p>
                        <h2 class="mt-1 text-lg font-bold text-zinc-950 dark:text-white">{{ $it ? 'Ultime voci' : 'Expense items' }}</h2>
                    </div>
                    <div class="max-h-[28rem] divide-y divide-zinc-200/80 overflow-auto dark:divide-white/10">
                        @forelse ($report['expenses'] as $expense)
                            <div class="flex items-start justify-between gap-4 px-5 py-3">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-zinc-950 dark:text-white">{{ $expense->description }}</p>
                                    <p class="mt-0.5 text-xs text-zinc-400">{{ $expense->occurred_at->format('d/m/Y') }} · {{ str_replace('_', ' ', $expense->category) }}</p>
                                </div>
                                <span class="whitespace-nowrap font-bold text-zinc-950 dark:text-white">{{ $money($expense->amount_cents) }}</span>
                            </div>
                        @empty
                            <div class="p-5 text-sm text-zinc-500 dark:text-zinc-400">{{ $it ? 'Nessuna spesa registrata.' : 'No expenses recorded.' }}</div>
                        @endforelse
                    </div>
                </section>
            </div>

            @if ($event->notes)
                <section class="pm-report-card pm-panel p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-pm-accent">{{ $it ? 'NOTE WEEKEND' : 'WEEKEND NOTES' }}</p>
                    <p class="mt-3 whitespace-pre-line text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $event->notes }}</p>
                </section>
            @endif

            <p class="pb-4 text-center text-xs text-zinc-400">
                {{ $it ? 'Generato da PitMetric dai dati operativi registrati nel workspace.' : 'Generated by PitMetric from operational data recorded in the workspace.' }}
            </p>
        </div>
    </div>
</x-layouts::app>
