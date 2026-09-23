@if (auth()->check() && \Illuminate\Support\Facades\Schema::hasTable('track_captures') && auth()->user()->can('team-write'))
    <details class="group fixed bottom-5 right-4 z-50 sm:bottom-6 sm:right-6">
        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-full bg-[#E10600] px-4 py-3 text-sm font-black text-white shadow-2xl shadow-black/40 transition hover:bg-[#f20b05] [&::-webkit-details-marker]:hidden">
            <flux:icon.bolt class="size-5" />
            <span>{{ app()->getLocale() === 'it' ? 'Segna tempo' : 'Quick lap' }}</span>
        </summary>

        <div class="absolute bottom-14 right-0 w-[min(92vw,24rem)] rounded-2xl border border-white/10 bg-[#15181d]/98 p-4 shadow-2xl backdrop-blur-xl">
            <div class="mb-4">
                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-[#E10600]">{{ app()->getLocale() === 'it' ? 'Cattura rapida' : 'Quick capture' }}</p>
                <h2 class="mt-1 text-base font-black text-white">{{ app()->getLocale() === 'it' ? 'Salva ora, completa dopo' : 'Save now, complete later' }}</h2>
                <p class="mt-1 text-xs leading-5 text-zinc-400">{{ app()->getLocale() === 'it' ? 'Il circuito può anche non esistere ancora. PitMetric non blocca il tempo.' : 'The circuit does not need to exist yet. PitMetric will not block the timing record.' }}</p>
            </div>

            <form method="POST" action="{{ route('quick-captures.lap.store') }}" class="grid gap-3">
                @csrf
                <label class="grid gap-1.5">
                    <span class="text-xs font-bold text-zinc-300">{{ app()->getLocale() === 'it' ? 'Circuito' : 'Circuit' }}</span>
                    <input class="pm-input" name="circuit_name" required maxlength="120" autocomplete="off" placeholder="Lonato / Cremona / ...">
                </label>
                <label class="grid gap-1.5">
                    <span class="text-xs font-bold text-zinc-300">{{ app()->getLocale() === 'it' ? 'Tempo giro' : 'Lap time' }}</span>
                    <input class="pm-input font-mono text-lg font-black" name="lap_time" required maxlength="20" inputmode="decimal" autocomplete="off" placeholder="1:02.345">
                </label>
                <label class="grid gap-1.5">
                    <span class="text-xs font-bold text-zinc-300">{{ app()->getLocale() === 'it' ? 'Nota veloce' : 'Quick note' }}</span>
                    <input class="pm-input" name="notes" maxlength="1000" placeholder="{{ app()->getLocale() === 'it' ? 'Gomme, meteo, traffico…' : 'Tyres, weather, traffic…' }}">
                </label>
                <button class="pm-race-button mt-1 w-full justify-center" type="submit">{{ app()->getLocale() === 'it' ? 'Salva tempo' : 'Save lap' }}</button>
            </form>
        </div>
    </details>
@endif
