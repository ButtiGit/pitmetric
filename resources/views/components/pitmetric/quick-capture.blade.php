@php
    $canQuickCapture = false;
    $quickCaptureUser = auth()->user();

    if ($quickCaptureUser instanceof \App\Models\User
        && \Illuminate\Support\Facades\Schema::hasTable('track_captures')
        && \Illuminate\Support\Facades\Schema::hasTable('workspace_user')) {
        $membership = \Illuminate\Support\Facades\DB::table('workspace_user')
            ->where('user_id', $quickCaptureUser->getKey());

        if (\Illuminate\Support\Facades\Schema::hasColumn('workspace_user', 'status')) {
            $membership->where('status', 'active');
        }

        $workspaceId = request()->hasSession() ? request()->session()->get('pitmetric.current_workspace_id') : null;

        if (is_numeric($workspaceId)) {
            $workspaceId = (int) $workspaceId;
            $validSelection = (clone $membership)->where('workspace_id', $workspaceId)->exists();
            $workspaceId = $validSelection ? $workspaceId : null;
        }

        if (! is_int($workspaceId)) {
            $selected = (clone $membership)->orderBy('workspace_id')->value('workspace_id');
            $workspaceId = is_numeric($selected) ? (int) $selected : null;
        }

        if ($workspaceId !== null) {
            $writableMembership = (clone $membership)->where('workspace_id', $workspaceId);

            if (\Illuminate\Support\Facades\Schema::hasColumn('workspace_user', 'role')) {
                $writableMembership->whereIn('role', \App\Models\WorkspaceMembership::WRITABLE_ROLES);
            }

            $canQuickCapture = $writableMembership->exists();
        }
    }
@endphp

@if ($canQuickCapture)
    <details class="group fixed bottom-5 right-4 z-50 sm:bottom-6 sm:right-6">
        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-full bg-[#E10600] px-4 py-3 text-sm font-black text-white shadow-2xl shadow-black/40 transition hover:bg-[#f20b05] [&::-webkit-details-marker]:hidden">
            <flux:icon.bolt class="size-5" />
            <span>{{ app()->getLocale() === 'it' ? 'Segna tempo' : 'Quick lap' }}</span>
        </summary>

        <div class="absolute bottom-14 right-0 max-h-[min(78vh,42rem)] w-[min(94vw,26rem)] overflow-y-auto rounded-2xl border border-white/10 bg-[#15181d]/98 p-4 shadow-2xl backdrop-blur-xl">
            <div class="mb-4">
                <p class="text-[10px] font-black uppercase tracking-[0.14em] text-[#E10600]">{{ app()->getLocale() === 'it' ? 'Cattura rapida' : 'Quick capture' }}</p>
                <h2 class="mt-1 text-base font-black text-white">{{ app()->getLocale() === 'it' ? 'Salva ora, completa dopo' : 'Save now, complete later' }}</h2>
                <p class="mt-1 text-xs leading-5 text-zinc-400">{{ app()->getLocale() === 'it' ? 'Scrivi quello che sai adesso. Circuito, pilota, mezzo e setup possono essere creati anche dopo.' : 'Enter what you know now. Circuit, driver, vehicle and setup can all be created later.' }}</p>
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

                <details class="rounded-xl border border-white/8 bg-white/[0.025] p-3">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 text-xs font-black text-zinc-200 [&::-webkit-details-marker]:hidden">
                        <span>{{ app()->getLocale() === 'it' ? 'Aggiungi contesto (opzionale)' : 'Add context (optional)' }}</span>
                        <flux:icon.chevron-down class="size-4" />
                    </summary>
                    <div class="mt-3 grid gap-3">
                        <div class="grid grid-cols-2 gap-2">
                            <label class="grid gap-1.5">
                                <span class="text-[11px] font-bold text-zinc-400">{{ app()->getLocale() === 'it' ? 'Pilota' : 'Driver' }}</span>
                                <input class="pm-input" name="driver_name" maxlength="120" autocomplete="off" placeholder="{{ app()->getLocale() === 'it' ? 'Nome pilota' : 'Driver name' }}">
                            </label>
                            <label class="grid gap-1.5">
                                <span class="text-[11px] font-bold text-zinc-400">{{ app()->getLocale() === 'it' ? 'Mezzo / kart' : 'Vehicle / kart' }}</span>
                                <input class="pm-input" name="vehicle_name" maxlength="120" autocomplete="off" placeholder="Kart 12">
                            </label>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="grid gap-1.5">
                                <span class="text-[11px] font-bold text-zinc-400">{{ app()->getLocale() === 'it' ? 'Configurazione' : 'Configuration' }}</span>
                                <input class="pm-input" name="configuration_name" maxlength="120" autocomplete="off" placeholder="Race setup">
                            </label>
                            <label class="grid gap-1.5">
                                <span class="text-[11px] font-bold text-zinc-400">{{ app()->getLocale() === 'it' ? 'Setup tecnico' : 'Technical setup' }}</span>
                                <input class="pm-input" name="technical_setup_name" maxlength="120" autocomplete="off" placeholder="Dry 1">
                            </label>
                        </div>
                        <label class="grid gap-1.5">
                            <span class="text-[11px] font-bold text-zinc-400">{{ app()->getLocale() === 'it' ? 'Componenti' : 'Components' }}</span>
                            <input class="pm-input" name="component_names" maxlength="600" autocomplete="off" placeholder="Motore A, gomme set 2, catena B">
                            <span class="text-[10px] leading-4 text-zinc-500">{{ app()->getLocale() === 'it' ? 'Puoi separarli con virgole. Quelli che non esistono finiranno negli avvisi.' : 'Separate multiple items with commas. Missing ones will appear in follow-ups.' }}</span>
                        </label>
                    </div>
                </details>

                <label class="grid gap-1.5">
                    <span class="text-xs font-bold text-zinc-300">{{ app()->getLocale() === 'it' ? 'Nota veloce' : 'Quick note' }}</span>
                    <input class="pm-input" name="notes" maxlength="1000" placeholder="{{ app()->getLocale() === 'it' ? 'Gomme, meteo, traffico...' : 'Tyres, weather, traffic...' }}">
                </label>
                <button class="pm-race-button mt-1 w-full justify-center" type="submit">{{ app()->getLocale() === 'it' ? 'Salva tempo' : 'Save lap' }}</button>
            </form>
        </div>
    </details>
@endif
