<x-layouts::app :title="__('Race weekends')">
    @php($it = app()->getLocale() === 'it')

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1360px] space-y-5">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">RACE WEEKENDS</p>
                <x-pitmetric.page-header
                    :title="$it ? 'Eventi e weekend di gara' : 'Events and race weekends'"
                    :description="$it ? 'Organizza circuito, piloti, mezzi, setup, sessioni, lavori, spese e note nello stesso spazio operativo.' : 'Organize circuit, drivers, vehicles, setups, sessions, work, expenses and notes in one operational workspace.'"
                />
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <section class="grid gap-4 xl:grid-cols-[1.4fr_0.6fr]">
                <article class="pm-panel p-5 sm:p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div><p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-accent">{{ $it ? 'NUOVO EVENTO' : 'NEW EVENT' }}</p><h2 class="mt-2 text-xl font-black text-pm-text">{{ $it ? 'Prepara il prossimo weekend' : 'Prepare the next weekend' }}</h2></div>
                        <p class="max-w-xl text-sm text-pm-text-secondary">{{ $it ? 'Il layout scelto diventa il circuito predefinito di tutte le sessioni dell’evento.' : 'The selected layout becomes the default circuit for every session in the event.' }}</p>
                    </div>
                    <form method="POST" action="{{ route('events.store') }}" class="mt-5 grid gap-4 md:grid-cols-2">
                        @csrf
                        <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Nome evento' : 'Event name' }}</span><input class="pm-input" name="name" maxlength="140" required value="{{ old('name') }}" placeholder="{{ $it ? 'Busca - Campionato Regionale' : 'Busca - Regional Championship' }}"></label>
                        <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Circuito / layout' : 'Circuit / layout' }}</span><select class="pm-input" name="circuit_layout_id" required><option value="">{{ $it ? 'Seleziona layout' : 'Select layout' }}</option>@foreach ($layouts as $layout)<option value="{{ $layout->id }}" @selected((string) old('circuit_layout_id') === (string) $layout->id)>{{ $layout->circuit->name }} · {{ $layout->name }} · {{ number_format($layout->length_meters, 0, ',', '.') }} m</option>@endforeach</select></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Dal' : 'From' }}</span><input class="pm-input" type="date" name="start_date" required value="{{ old('start_date') }}"></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Al' : 'To' }}</span><input class="pm-input" type="date" name="end_date" required value="{{ old('end_date') }}"></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Campionato' : 'Championship' }}</span><input class="pm-input" name="championship" maxlength="120" value="{{ old('championship') }}"></label>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Round / tappa' : 'Round' }}</span><input class="pm-input" name="round_label" maxlength="80" value="{{ old('round_label') }}" placeholder="Round 4"></label>
                        <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ $it ? 'Note iniziali' : 'Initial notes' }}</span><textarea class="pm-input min-h-24" name="notes" maxlength="4000">{{ old('notes') }}</textarea></label>
                        <div class="md:col-span-2"><button class="pm-race-button" type="submit" @disabled($layouts->isEmpty())>{{ $it ? 'Crea workspace evento' : 'Create event workspace' }}</button></div>
                    </form>
                    @if ($layouts->isEmpty())<p class="mt-3 text-sm font-semibold text-pm-warning">{{ $it ? 'Prima crea almeno un circuito e un layout.' : 'Create at least one circuit and layout first.' }}</p>@endif
                </article>

                <article class="pm-panel p-5 sm:p-6">
                    <p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'PILOTI' : 'DRIVERS' }}</p>
                    <h2 class="mt-2 text-xl font-black text-pm-text">{{ $it ? 'Catalogo piloti' : 'Driver roster' }}</h2>
                    <p class="mt-2 text-sm leading-6 text-pm-text-secondary">{{ $it ? 'Il pilota viene creato una volta e poi assegnato ai diversi weekend.' : 'Create a driver once, then assign them across race weekends.' }}</p>
                    <form method="POST" action="{{ route('drivers.store') }}" class="mt-5 grid gap-3">
                        @csrf
                        <input class="pm-input" name="display_name" maxlength="120" required placeholder="{{ $it ? 'Nome pilota' : 'Driver name' }}">
                        <div class="grid grid-cols-2 gap-3"><input class="pm-input" name="racing_number" maxlength="20" placeholder="{{ $it ? 'Numero' : 'Number' }}"><input class="pm-input" name="licence_reference" maxlength="80" placeholder="{{ $it ? 'Licenza' : 'Licence' }}"></div>
                        <button class="pm-ghost-button justify-center" type="submit">{{ $it ? 'Aggiungi pilota' : 'Add driver' }}</button>
                    </form>
                    <div class="mt-5 space-y-2">
                        @forelse ($drivers as $driver)
                            <div class="flex items-center justify-between gap-3 rounded-xl border border-pm-border bg-pm-subtle px-3 py-3"><div class="min-w-0"><p class="truncate font-bold text-pm-text">{{ $driver->display_name }}</p><p class="mt-1 text-xs text-pm-muted">{{ $driver->licence_reference ?: ($it ? 'Licenza non indicata' : 'No licence reference') }}</p></div><span class="rounded-lg border border-pm-border px-2 py-1 font-mono text-xs font-bold text-pm-text">{{ $driver->racing_number ?: '--' }}</span></div>
                        @empty
                            <p class="rounded-xl border border-dashed border-pm-border p-4 text-sm text-pm-muted">{{ $it ? 'Nessun pilota ancora.' : 'No drivers yet.' }}</p>
                        @endforelse
                    </div>
                </article>
            </section>

            <section class="space-y-3">
                <div class="flex items-end justify-between gap-4"><div><p class="text-[11px] font-bold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'CALENDARIO OPERATIVO' : 'OPERATIONS CALENDAR' }}</p><h2 class="mt-2 text-xl font-black text-pm-text">{{ $it ? 'Weekend' : 'Weekends' }}</h2></div><p class="text-sm text-pm-muted">{{ $events->count() }} {{ $it ? 'eventi' : 'events' }}</p></div>
                @forelse ($events as $event)
                    @php($variant = match($event->status) {'active' => 'success', 'completed' => 'info', 'cancelled' => 'danger', default => 'warning'})
                    <a href="{{ route('events.show', $event) }}" class="pm-panel block p-5 transition hover:border-pm-border-strong sm:p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h3 class="text-lg font-black text-pm-text">{{ $event->name }}</h3><x-pitmetric.status-badge :label="$event->status" :variant="$variant" /></div><p class="mt-2 text-sm text-pm-text-secondary">{{ $event->start_date->format('d/m/Y') }} - {{ $event->end_date->format('d/m/Y') }} · {{ $event->circuitLayout?->circuit?->name ?? ($it ? 'Circuito non disponibile' : 'Circuit unavailable') }} · {{ $event->circuitLayout?->name }}</p>@if ($event->championship || $event->round_label)<p class="mt-1 text-xs text-pm-muted">{{ collect([$event->championship, $event->round_label])->filter()->join(' · ') }}</p>@endif</div>
                            <div class="grid grid-cols-3 gap-2 text-center sm:min-w-[320px]"><div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-xl font-black text-pm-text">{{ $event->entries_count }}</p><p class="mt-1 text-[10px] uppercase text-pm-muted">Entries</p></div><div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-xl font-black text-pm-text">{{ $event->sessions_count }}</p><p class="mt-1 text-[10px] uppercase text-pm-muted">Sessions</p></div><div class="rounded-xl border border-pm-border bg-pm-subtle p-3"><p class="text-xl font-black text-pm-text">{{ $event->tasks_count }}</p><p class="mt-1 text-[10px] uppercase text-pm-muted">Tasks</p></div></div>
                        </div>
                    </a>
                @empty
                    <x-pitmetric.empty-state :title="$it ? 'Nessun evento' : 'No events'" :description="$it ? 'Crea il primo weekend per trasformare sessioni, setup, lavori e costi in un unico flusso operativo.' : 'Create the first weekend to turn sessions, setups, work and costs into one operational flow.'" />
                @endforelse
            </section>
        </div>
    </div>
</x-layouts::app>
