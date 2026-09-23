<x-layouts::app :title="'Pit Mode'">
    @php
        $it = app()->getLocale() === 'it';
        $contextIsEmpty = collect($context)->filter()->isEmpty();
        $pressureLabels = [
            'fl' => $it ? 'Ant. SX' : 'Front L',
            'fr' => $it ? 'Ant. DX' : 'Front R',
            'rl' => $it ? 'Post. SX' : 'Rear L',
            'rr' => $it ? 'Post. DX' : 'Rear R',
        ];
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-3 py-4 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-3xl space-y-4">
            <header class="rounded-2xl border border-pm-border bg-pm-panel p-4 sm:p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.16em] text-pm-accent">Trackside</p>
                        <h1 class="mt-1 text-2xl font-black text-pm-text">Pit Mode</h1>
                        <p class="mt-1 text-sm leading-5 text-pm-muted">
                            {{ $it
                                ? 'Registra quello che succede in pista senza fermarti a compilare il database. Il contesto resta attivo per le catture successive.'
                                : 'Capture what happens trackside without stopping to complete the database. Context stays active for the next captures.' }}
                        </p>
                    </div>
                    <a href="{{ route('follow-ups.index') }}" class="pm-ghost-button shrink-0">
                        {{ $it ? 'Avvisi' : 'Alerts' }}
                    </a>
                </div>
            </header>

            @if (session('status'))
                <div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger">
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <details class="rounded-2xl border border-pm-border bg-pm-panel p-4" {{ $contextIsEmpty ? 'open' : '' }}>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 [&::-webkit-details-marker]:hidden">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.14em] text-pm-accent">
                            {{ $it ? 'Contesto attivo' : 'Active context' }}
                        </p>
                        <p class="mt-1 text-sm font-black text-pm-text">
                            {{ collect([$context['circuit_name'], $context['driver_name'], $context['vehicle_name']])->filter()->join(' · ') ?: ($it ? 'Nessun contesto impostato' : 'No context set') }}
                        </p>
                    </div>
                    <span class="text-xs font-bold text-pm-muted">{{ $it ? 'Modifica' : 'Edit' }}</span>
                </summary>

                <form method="POST" action="{{ route('pit-mode.context.update') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                    @csrf
                    <label class="grid gap-1.5">
                        <span class="pm-label">{{ $it ? 'Circuito' : 'Circuit' }}</span>
                        <input class="pm-input" name="circuit_name" maxlength="120" value="{{ old('circuit_name', $context['circuit_name']) }}" placeholder="Lonato">
                    </label>
                    <label class="grid gap-1.5">
                        <span class="pm-label">{{ $it ? 'Pilota' : 'Driver' }}</span>
                        <input class="pm-input" name="driver_name" maxlength="120" value="{{ old('driver_name', $context['driver_name']) }}" placeholder="Simone">
                    </label>
                    <label class="grid gap-1.5">
                        <span class="pm-label">{{ $it ? 'Kart / mezzo' : 'Kart / vehicle' }}</span>
                        <input class="pm-input" name="vehicle_name" maxlength="120" value="{{ old('vehicle_name', $context['vehicle_name']) }}" placeholder="Kart 12">
                    </label>
                    <label class="grid gap-1.5">
                        <span class="pm-label">{{ $it ? 'Configurazione' : 'Configuration' }}</span>
                        <input class="pm-input" name="configuration_name" maxlength="120" value="{{ old('configuration_name', $context['configuration_name']) }}" placeholder="Race build">
                    </label>
                    <label class="grid gap-1.5 sm:col-span-2">
                        <span class="pm-label">{{ $it ? 'Setup tecnico' : 'Technical setup' }}</span>
                        <input class="pm-input" name="technical_setup_name" maxlength="120" value="{{ old('technical_setup_name', $context['technical_setup_name']) }}" placeholder="Dry 1">
                    </label>
                    <button class="pm-race-button justify-center sm:col-span-2" type="submit">
                        {{ $it ? 'Salva contesto' : 'Save context' }}
                    </button>
                </form>

                @if (! $contextIsEmpty)
                    <form method="POST" action="{{ route('pit-mode.context.clear') }}" class="mt-2">
                        @csrf
                        @method('DELETE')
                        <button class="w-full rounded-lg px-3 py-2 text-xs font-bold text-pm-muted hover:text-pm-text" type="submit">
                            {{ $it ? 'Azzera contesto' : 'Clear context' }}
                        </button>
                    </form>
                @endif
            </details>

            <section class="space-y-2" aria-label="{{ $it ? 'Azioni rapide' : 'Quick actions' }}">
                <details id="pit-lap" class="group rounded-2xl border border-pm-border bg-pm-panel open:border-pm-accent/40">
                    <summary class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 [&::-webkit-details-marker]:hidden">
                        <div>
                            <p class="text-base font-black text-pm-text">{{ $it ? '+ Tempo' : '+ Lap time' }}</p>
                            <p class="text-xs text-pm-muted">{{ $it ? 'Cronometro al volo' : 'Quick stopwatch entry' }}</p>
                        </div>
                        <span class="text-xl font-black text-pm-accent">01</span>
                    </summary>
                    <form method="POST" action="{{ route('quick-captures.lap.store') }}" class="grid gap-3 border-t border-pm-border p-4 sm:grid-cols-2">
                        @csrf
                        <input type="hidden" name="pit_mode" value="1">
                        <input type="hidden" name="driver_name" value="{{ $context['driver_name'] }}">
                        <input type="hidden" name="vehicle_name" value="{{ $context['vehicle_name'] }}">
                        <input type="hidden" name="configuration_name" value="{{ $context['configuration_name'] }}">
                        <input type="hidden" name="technical_setup_name" value="{{ $context['technical_setup_name'] }}">
                        <label class="grid gap-1.5">
                            <span class="pm-label">{{ $it ? 'Circuito' : 'Circuit' }}</span>
                            <input class="pm-input" name="circuit_name" required maxlength="120" value="{{ $context['circuit_name'] }}" placeholder="Lonato">
                        </label>
                        <label class="grid gap-1.5">
                            <span class="pm-label">{{ $it ? 'Tempo giro' : 'Lap time' }}</span>
                            <input class="pm-input font-mono text-lg font-black" name="lap_time" required maxlength="20" inputmode="decimal" placeholder="1:02.345" autofocus>
                        </label>
                        <label class="grid gap-1.5 sm:col-span-2">
                            <span class="pm-label">{{ $it ? 'Nota veloce' : 'Quick note' }}</span>
                            <input class="pm-input" name="notes" maxlength="1000" placeholder="{{ $it ? 'Traffico, gomme, meteo...' : 'Traffic, tyres, weather...' }}">
                        </label>
                        <button class="pm-race-button justify-center sm:col-span-2" type="submit">
                            {{ $it ? 'Salva tempo' : 'Save lap' }}
                        </button>
                    </form>
                </details>

                <details id="pit-pressure" class="group rounded-2xl border border-pm-border bg-pm-panel open:border-pm-accent/40">
                    <summary class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 [&::-webkit-details-marker]:hidden">
                        <div>
                            <p class="text-base font-black text-pm-text">{{ $it ? '+ Pressioni' : '+ Pressures' }}</p>
                            <p class="text-xs text-pm-muted">{{ $it ? 'Quattro gomme in una schermata' : 'Four tyres in one screen' }}</p>
                        </div>
                        <span class="text-xl font-black text-pm-accent">02</span>
                    </summary>
                    <form method="POST" action="{{ route('pit-mode.pressures.store') }}" class="grid grid-cols-2 gap-3 border-t border-pm-border p-4">
                        @csrf
                        @foreach ($pressureLabels as $key => $label)
                            <label class="grid gap-1.5">
                                <span class="pm-label">{{ $label }}</span>
                                <input class="pm-input font-mono text-lg font-black" name="pressure_{{ $key }}" required type="number" inputmode="decimal" min="0.1" max="1000" step="0.01">
                            </label>
                        @endforeach
                        <label class="grid gap-1.5">
                            <span class="pm-label">{{ $it ? 'Unità' : 'Unit' }}</span>
                            <select class="pm-input" name="unit">
                                <option value="bar">bar</option>
                                <option value="psi">psi</option>
                                <option value="kpa">kPa</option>
                            </select>
                        </label>
                        <label class="grid gap-1.5">
                            <span class="pm-label">{{ $it ? 'Nota' : 'Note' }}</span>
                            <input class="pm-input" name="notes" maxlength="1000">
                        </label>
                        <button class="pm-race-button col-span-2 justify-center" type="submit">
                            {{ $it ? 'Salva pressioni' : 'Save pressures' }}
                        </button>
                    </form>
                </details>

                <details id="pit-issue" class="group rounded-2xl border border-pm-border bg-pm-panel open:border-pm-accent/40">
                    <summary class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 [&::-webkit-details-marker]:hidden">
                        <div>
                            <p class="text-base font-black text-pm-text">{{ $it ? '+ Problema' : '+ Issue' }}</p>
                            <p class="text-xs text-pm-muted">{{ $it ? 'Segna subito cosa non va' : 'Log a problem immediately' }}</p>
                        </div>
                        <span class="text-xl font-black text-pm-accent">03</span>
                    </summary>
                    <form method="POST" action="{{ route('pit-mode.issues.store') }}" class="grid gap-3 border-t border-pm-border p-4">
                        @csrf
                        <label class="grid gap-1.5">
                            <span class="pm-label">{{ $it ? 'Gravità' : 'Severity' }}</span>
                            <select class="pm-input" name="severity">
                                <option value="warning">{{ $it ? 'Da controllare' : 'Needs checking' }}</option>
                                <option value="critical">{{ $it ? 'Critico' : 'Critical' }}</option>
                                <option value="info">{{ $it ? 'Osservazione' : 'Observation' }}</option>
                            </select>
                        </label>
                        <label class="grid gap-1.5">
                            <span class="pm-label">{{ $it ? 'Cosa è successo?' : 'What happened?' }}</span>
                            <textarea class="pm-input min-h-24" name="issue" required maxlength="1000" placeholder="{{ $it ? 'Vibrazione in frenata, temperatura alta...' : 'Brake vibration, high temperature...' }}"></textarea>
                        </label>
                        <button class="pm-race-button justify-center" type="submit">
                            {{ $it ? 'Salva problema' : 'Save issue' }}
                        </button>
                    </form>
                </details>

                <details id="pit-component" class="group rounded-2xl border border-pm-border bg-pm-panel open:border-pm-accent/40">
                    <summary class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 [&::-webkit-details-marker]:hidden">
                        <div>
                            <p class="text-base font-black text-pm-text">{{ $it ? '+ Cambio componente' : '+ Component change' }}</p>
                            <p class="text-xs text-pm-muted">{{ $it ? 'Vecchio fuori, nuovo dentro' : 'Old out, new in' }}</p>
                        </div>
                        <span class="text-xl font-black text-pm-accent">04</span>
                    </summary>
                    <form method="POST" action="{{ route('pit-mode.component-changes.store') }}" class="grid gap-3 border-t border-pm-border p-4 sm:grid-cols-2">
                        @csrf
                        <label class="grid gap-1.5">
                            <span class="pm-label">{{ $it ? 'Rimosso' : 'Removed' }}</span>
                            <input class="pm-input" name="removed_component" maxlength="120" placeholder="Catena A">
                        </label>
                        <label class="grid gap-1.5">
                            <span class="pm-label">{{ $it ? 'Installato' : 'Installed' }}</span>
                            <input class="pm-input" name="installed_component" maxlength="120" placeholder="Catena B">
                        </label>
                        <label class="grid gap-1.5 sm:col-span-2">
                            <span class="pm-label">{{ $it ? 'Nota' : 'Note' }}</span>
                            <input class="pm-input" name="notes" maxlength="1000">
                        </label>
                        <button class="pm-race-button justify-center sm:col-span-2" type="submit">
                            {{ $it ? 'Salva cambio' : 'Save change' }}
                        </button>
                    </form>
                </details>

                <details id="pit-note" class="group rounded-2xl border border-pm-border bg-pm-panel open:border-pm-accent/40">
                    <summary class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 [&::-webkit-details-marker]:hidden">
                        <div>
                            <p class="text-base font-black text-pm-text">{{ $it ? '+ Nota' : '+ Note' }}</p>
                            <p class="text-xs text-pm-muted">{{ $it ? 'Qualsiasi cosa da ricordare' : 'Anything worth remembering' }}</p>
                        </div>
                        <span class="text-xl font-black text-pm-accent">05</span>
                    </summary>
                    <form method="POST" action="{{ route('pit-mode.notes.store') }}" class="grid gap-3 border-t border-pm-border p-4">
                        @csrf
                        <label class="grid gap-1.5">
                            <span class="pm-label">{{ $it ? 'Nota' : 'Note' }}</span>
                            <textarea class="pm-input min-h-24" name="note" required maxlength="2000"></textarea>
                        </label>
                        <button class="pm-race-button justify-center" type="submit">
                            {{ $it ? 'Salva nota' : 'Save note' }}
                        </button>
                    </form>
                </details>
            </section>

            @if ($captures->isNotEmpty())
                <section class="rounded-2xl border border-pm-border bg-pm-panel p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.14em] text-pm-accent">
                                {{ $it ? 'Cronologia rapida' : 'Quick history' }}
                            </p>
                            <h2 class="mt-1 text-base font-black text-pm-text">
                                {{ $it ? 'Ultime catture' : 'Latest captures' }}
                            </h2>
                        </div>
                        <span class="text-xs font-bold text-pm-muted">{{ $captures->count() }}</span>
                    </div>

                    <div class="mt-3 space-y-2">
                        @foreach ($captures as $capture)
                            @php
                                $kindLabel = match ($capture->kind) {
                                    'lap_time' => $it ? 'Tempo' : 'Lap',
                                    'tyre_pressure' => $it ? 'Pressioni' : 'Pressures',
                                    'issue' => $it ? 'Problema' : 'Issue',
                                    'component_change' => $it ? 'Cambio componente' : 'Component change',
                                    default => $it ? 'Nota' : 'Note',
                                };

                                $captureSummary = match ($capture->kind) {
                                    'lap_time' => (string) $capture->formattedLapTime(),
                                    'tyre_pressure' => implode(' / ', [
                                        data_get($capture->payload, 'fl'),
                                        data_get($capture->payload, 'fr'),
                                        data_get($capture->payload, 'rl'),
                                        data_get($capture->payload, 'rr'),
                                    ]).' '.(string) data_get($capture->payload, 'unit'),
                                    'component_change' => (data_get($capture->payload, 'removed_component') ?: '-').' -> '.(data_get($capture->payload, 'installed_component') ?: '-'),
                                    default => \Illuminate\Support\Str::limit((string) $capture->notes, 100),
                                };
                            @endphp

                            <article class="rounded-xl border border-pm-border bg-pm-subtle p-3 {{ (string) request('captured') === (string) $capture->id ? 'ring-1 ring-pm-accent/60' : '' }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-black uppercase tracking-[0.12em] text-pm-accent">{{ $kindLabel }}</p>
                                        <p class="mt-1 text-sm font-bold text-pm-text">{{ $captureSummary }}</p>
                                        <p class="mt-1 text-[11px] text-pm-muted">
                                            {{ $capture->circuit?->name ?? $capture->circuit_name ?? ($it ? 'Senza circuito' : 'No circuit') }}
                                            · {{ $capture->occurred_at?->format('H:i') }}
                                        </p>
                                    </div>

                                    @if ($capture->status === 'needs_attention')
                                        <span class="rounded-full bg-pm-warning-subtle px-2 py-1 text-[10px] font-black text-pm-warning">
                                            {{ $it ? 'Da completare' : 'Complete later' }}
                                        </span>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-layouts::app>
