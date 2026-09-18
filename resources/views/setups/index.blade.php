<x-layouts::app :title="__('Technical setups')">
    @php
        $it = app()->getLocale() === 'it';
        $canWrite = Gate::allows('team-write');
        $groups = [
            'tyres' => $it ? 'Gomme' : 'Tyres',
            'chassis' => $it ? 'Telaio' : 'Chassis',
            'controls' => $it ? 'Controlli' : 'Controls',
            'aero' => 'Aero',
            'drivetrain' => $it ? 'Trasmissione' : 'Drivetrain',
        ];
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1420px] space-y-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">TECHNICAL SETUP</p>
                    <x-pitmetric.page-header
                        :title="$it ? 'Setup tecnici del mezzo' : 'Vehicle technical setups'"
                        :description="$it ? 'Componenti e setup ora sono separati: qui salvi regolazioni, pressioni e geometrie. Ogni sessione crea automaticamente una fotografia immutabile dei valori realmente usati.' : 'Components and setup are now separate: store adjustments, pressures and geometry here. Every session automatically captures an immutable snapshot of the values actually used.'"
                    />
                </div>

                @if ($canWrite)
                    <x-crud-modal id="create-technical-setup" :title="$it ? 'Nuovo setup tecnico' : 'New technical setup'" :description="$it ? 'Inserisci solo i parametri che hanno senso per il tuo mezzo. I campi lasciati vuoti non vengono salvati.' : 'Enter only the parameters that make sense for your vehicle. Empty fields are not stored.'" :trigger="$it ? '+ Nuovo setup' : '+ New setup'" size="max-w-5xl">
                        @can('team-write')<form method="POST" action="{{ route('setups.store') }}" class="space-y-5">
                            @csrf
                            @include('setups._fields', ['setup' => null])
                            <div class="flex justify-end"><button class="pm-race-button" type="submit" @disabled($vehicles->isEmpty())>{{ $it ? 'Salva setup' : 'Save setup' }}</button></div>
                        </form>@endcan
                    </x-crud-modal>
                @endif
            </div>

            @if (session('status'))<div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <section class="grid gap-4 lg:grid-cols-3">
                <article class="pm-stat-card lg:col-span-1">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Configurazione' : 'Configuration' }}</p>
                    <p class="mt-3 text-lg font-black text-pm-text">{{ $it ? 'Quali componenti sono montati' : 'Which components are installed' }}</p>
                    <p class="mt-2 text-sm leading-6 text-pm-muted">{{ $it ? 'Resta versionata nella pagina Configurazioni.' : 'Remains versioned on the Configurations page.' }}</p>
                </article>
                <article class="pm-stat-card lg:col-span-1">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Setup tecnico' : 'Technical setup' }}</p>
                    <p class="mt-3 text-lg font-black text-pm-text">{{ $it ? 'Come è regolato il mezzo' : 'How the vehicle is adjusted' }}</p>
                    <p class="mt-2 text-sm leading-6 text-pm-muted">{{ $it ? 'Pressioni, geometrie, aero, differenziale e altri valori regolabili.' : 'Pressures, geometry, aero, differential and other adjustable values.' }}</p>
                </article>
                <article class="pm-stat-card lg:col-span-1">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-pm-muted">SETUP SNAPSHOT</p>
                    <p class="mt-3 text-lg font-black text-pm-text">{{ $it ? 'La prova non cambia più' : 'The run never changes' }}</p>
                    <p class="mt-2 text-sm leading-6 text-pm-muted">{{ $it ? 'Quando registri una sessione, PitMetric copia il setup. Modificare il profilo dopo non altera lo storico.' : 'When a session is recorded, PitMetric copies the setup. Editing the profile later never changes history.' }}</p>
                </article>
            </section>

            <section class="grid gap-4 xl:grid-cols-2">
                @forelse ($setups as $setup)
                    <article class="pm-panel p-5 sm:p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-bold text-pm-accent">{{ $setup->vehicle->name }}</p>
                                <h2 class="mt-1 text-xl font-black text-pm-text">{{ $setup->name }}</h2>
                                @if ($setup->description)<p class="mt-2 text-sm leading-6 text-pm-muted">{{ $setup->description }}</p>@endif
                            </div>
                            <div class="flex items-center gap-2">
                                <x-pitmetric.status-badge :label="$setup->status" variant="success" />
                                <span class="rounded-full border border-pm-border bg-pm-subtle px-2.5 py-1 text-xs font-bold text-pm-muted">{{ $setup->snapshots_count }} {{ $it ? 'snapshot' : 'snapshots' }}</span>
                            </div>
                        </div>

                        <div class="mt-5 space-y-4">
                            @foreach ($groups as $group => $groupLabel)
                                @php
                                    $groupValues = collect(\App\Models\TechnicalSetup::FIELD_DEFINITIONS)
                                        ->filter(fn ($definition, $key) => $definition['group'] === $group && array_key_exists($key, $setup->values ?? []));
                                @endphp
                                @if ($groupValues->isNotEmpty())
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-[0.12em] text-pm-muted">{{ $groupLabel }}</p>
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach ($groupValues as $key => $definition)
                                                <span class="rounded-lg border border-pm-border bg-pm-subtle px-3 py-2 text-xs text-pm-text-secondary"><strong class="text-pm-text">{{ $definition['label'] }}</strong> · {{ $setup->values[$key] }}{{ $definition['unit'] !== '' ? ' '.$definition['unit'] : '' }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach

                            @if (($setup->values ?? []) === [])
                                <p class="rounded-xl border border-dashed border-pm-border px-4 py-3 text-sm text-pm-muted">{{ $it ? 'Profilo senza parametri: puoi usarlo come segnaposto e completarlo quando hai i valori reali.' : 'Profile without parameters: use it as a placeholder and complete it when real values are available.' }}</p>
                            @endif
                        </div>

                        @if ($canWrite)
                            <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-pm-border pt-4">
                                <x-crud-modal id="edit-technical-setup-{{ $setup->id }}" :title="$it ? 'Modifica setup tecnico' : 'Edit technical setup'" :description="$it ? 'Le sessioni già registrate manterranno i loro snapshot originali.' : 'Existing sessions keep their original snapshots.'" :trigger="$it ? 'Modifica' : 'Edit'" trigger-class="pm-ghost-button" size="max-w-5xl">
                                    @can('team-write')<form method="POST" action="{{ route('setups.update', $setup) }}" class="space-y-5">
                                        @csrf @method('PUT')
                                        @include('setups._fields', ['setup' => $setup])
                                        <div class="flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Salva nuova regolazione' : 'Save adjustments' }}</button></div>
                                    </form>@endcan
                                </x-crud-modal>

                                @can('team-write')<form method="POST" action="{{ route('setups.destroy', $setup) }}" onsubmit="return confirm(@js($it ? 'Archiviare questo setup? Gli snapshot delle sessioni resteranno intatti.' : 'Archive this setup? Session snapshots remain intact.'))">
                                    @csrf @method('DELETE')
                                    <button class="pm-ghost-button text-pm-danger" type="submit">{{ $it ? 'Archivia' : 'Archive' }}</button>
                                </form>@endcan
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="xl:col-span-2"><x-pitmetric.empty-state :title="$it ? 'Nessun setup tecnico' : 'No technical setups'" :description="$it ? 'Crea il primo profilo di regolazione. Le prossime sessioni lo fotograferanno automaticamente nello storico.' : 'Create the first adjustment profile. Future sessions will automatically capture it in history.'" /></div>
                @endforelse
            </section>
        </div>
    </div>
</x-layouts::app>
