<x-layouts::app :title="__('Configurations')">
    @php
        $it = app()->getLocale() === 'it';
        $configurableVehicles = $vehicles->filter(fn ($vehicle) => (int) $vehicle->active_components_count > 0);
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1360px] space-y-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <x-pitmetric.page-header
                        :title="$it ? 'Configurazioni versionate' : 'Versioned configurations'"
                        :description="$it ? 'Una configurazione fotografa i componenti realmente montati sul mezzo. Lo stato fisico si modifica solo dalla pagina Componenti.' : 'A configuration snapshots the components physically installed on the vehicle. Physical state is changed only from Components.'"
                    />
                </div>

                <x-crud-modal
                    id="create-configuration"
                    :title="$it ? 'Nuova configurazione' : 'New configuration'"
                    :description="$it ? 'PitMetric salverà uno snapshot esatto dei componenti attualmente montati sul mezzo.' : 'PitMetric will save an exact snapshot of the components currently installed on the vehicle.'"
                    :trigger="$it ? '+ Nuova configurazione' : '+ New configuration'"
                >
                    @can('team-write')
                        <form method="POST" action="{{ route('configurations.store') }}" class="space-y-4">
                            @csrf

                            <div class="rounded-xl border border-pm-accent/20 bg-pm-accent/5 p-4">
                                <p class="text-sm font-bold text-pm-text">{{ $it ? 'Lo stato fisico viene prima' : 'Physical state comes first' }}</p>
                                <p class="mt-1 text-sm leading-6 text-pm-text-secondary">
                                    {{ $it ? 'Per cambiare motore, catena, gomme o qualsiasi altro componente, montalo o rimuovilo prima in Componenti. Qui crei solo la versione storica della build reale.' : 'To change an engine, chain, tyres or any other component, install or remove it in Components first. Here you only create the historical version of the real build.' }}
                                </p>
                                <a href="{{ route('components.index') }}" class="mt-2 inline-flex text-sm font-bold text-pm-accent hover:underline">
                                    {{ $it ? 'Gestisci componenti fisici' : 'Manage physical components' }}
                                </a>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="grid gap-2">
                                    <span class="pm-label">{{ $it ? 'Mezzo' : 'Vehicle' }}</span>
                                    <select class="pm-input" name="vehicle_id" required>
                                        <option value="">{{ $it ? 'Seleziona' : 'Select' }}</option>
                                        @foreach ($vehicles as $vehicle)
                                            <option value="{{ $vehicle->id }}" @disabled((int) $vehicle->active_components_count === 0)>
                                                {{ $vehicle->name }} · {{ $vehicle->active_components_count }} {{ $it ? 'componenti montati' : 'installed components' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($vehicles->isNotEmpty() && $configurableVehicles->isEmpty())
                                        <span class="text-xs font-semibold text-pm-warning">{{ $it ? 'Nessun mezzo ha ancora componenti montati.' : 'No vehicle has installed components yet.' }}</span>
                                    @endif
                                </label>

                                <label class="grid gap-2">
                                    <span class="pm-label">{{ $it ? 'Nome build' : 'Build name' }}</span>
                                    <input class="pm-input" name="name" required maxlength="120" placeholder="Race Build">
                                </label>
                            </div>

                            <label class="grid gap-2">
                                <span class="pm-label">{{ $it ? 'Descrizione' : 'Description' }}</span>
                                <textarea class="pm-input min-h-20" name="description" maxlength="2000"></textarea>
                            </label>

                            <label class="grid gap-2">
                                <span class="pm-label">{{ $it ? 'Costo preparazione (€)' : 'Setup work cost (€)' }}</span>
                                <input class="pm-input" name="operation_cost" type="number" min="0" max="1000000" step="0.01">
                                <span class="text-xs text-pm-muted">{{ $it ? 'Opzionale · viene aggiunto automaticamente ai Costi' : 'Optional · automatically added to Expenses' }}</span>
                            </label>

                            <div class="flex justify-end">
                                <button class="pm-race-button" type="submit" @disabled($configurableVehicles->isEmpty())>
                                    {{ $it ? 'Cattura configurazione' : 'Capture configuration' }}
                                </button>
                            </div>
                        </form>
                    @endcan
                </x-crud-modal>
            </div>

            @if (session('status'))
                <div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>
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

            @if ($vehicles->isEmpty())
                <section class="pm-panel border-pm-warning/30 bg-pm-warning-subtle p-5 sm:p-6">
                    <p class="text-xs font-black uppercase tracking-[0.12em] text-pm-warning">01 · {{ $it ? 'Mezzo richiesto' : 'Vehicle required' }}</p>
                    <h2 class="mt-2 text-lg font-black text-pm-text">{{ $it ? 'Prima crea il mezzo' : 'Create the vehicle first' }}</h2>
                    <p class="mt-2 text-sm leading-6 text-pm-text-secondary">{{ $it ? 'Una configurazione appartiene sempre a un mezzo reale.' : 'Every configuration belongs to a real vehicle.' }}</p>
                    <a href="{{ route('garage.index') }}" class="mt-4 inline-flex pm-race-button">{{ $it ? 'Vai ai Mezzi' : 'Go to Vehicles' }}</a>
                </section>
            @elseif ($configurableVehicles->isEmpty())
                <section class="pm-panel border-pm-warning/30 bg-pm-warning-subtle p-5 sm:p-6">
                    <p class="text-xs font-black uppercase tracking-[0.12em] text-pm-warning">02 · {{ $it ? 'Componenti richiesti' : 'Components required' }}</p>
                    <h2 class="mt-2 text-lg font-black text-pm-text">{{ $it ? 'Monta almeno un componente' : 'Install at least one component' }}</h2>
                    <p class="mt-2 text-sm leading-6 text-pm-text-secondary">{{ $it ? 'La configurazione deve rappresentare una build fisica reale. Installa i componenti sul mezzo e torna qui per catturare la prima versione.' : 'The configuration must represent a real physical build. Install components on the vehicle, then return here to capture the first version.' }}</p>
                    <a href="{{ route('components.index') }}" class="mt-4 inline-flex pm-race-button">{{ $it ? 'Vai ai Componenti' : 'Go to Components' }}</a>
                </section>
            @endif

            <div class="grid gap-4 lg:grid-cols-2">
                @forelse ($configurations as $configuration)
                    @php
                        $latest = $configuration->versions->first();
                        $physicalComponents = $configuration->vehicle->componentInstallations
                            ->map(fn ($installation) => $installation->component)
                            ->filter();
                        $latestIds = collect($latest?->components ?? collect())->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
                        $physicalIds = $physicalComponents->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
                        $aligned = $latest !== null && $physicalIds !== [] && $latestIds === $physicalIds;
                    @endphp

                    <article class="pm-panel p-5 sm:p-6">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="text-xs font-bold text-pm-accent">{{ $configuration->vehicle->name }}</p>
                                    <x-pitmetric.status-badge
                                        :label="$aligned ? ($it ? 'Allineata' : 'Aligned') : ($it ? 'Da aggiornare' : 'Needs update')"
                                        :variant="$aligned ? 'success' : 'warning'"
                                    />
                                </div>
                                <h2 class="mt-1 text-lg font-black text-pm-text">{{ $configuration->name }}</h2>
                                <p class="mt-1 text-sm text-pm-muted">
                                    v{{ $latest?->version_number ?? 0 }} · {{ $latest?->locked_at ? ($it ? 'storica/bloccata' : 'historical/locked') : ($it ? 'ultima versione' : 'latest version') }}
                                </p>
                            </div>

                            @can('team-write')
                                <form method="POST" action="{{ route('configurations.destroy', $configuration) }}" onsubmit="return confirm(@js($it ? 'Archiviare la configurazione?' : 'Archive configuration?'))">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs font-bold text-pm-danger hover:underline">{{ $it ? 'Archivia' : 'Archive' }}</button>
                                </form>
                            @endcan
                        </div>

                        <div class="mt-5 grid gap-3 md:grid-cols-2">
                            <div class="rounded-xl border border-pm-border bg-pm-subtle p-4">
                                <p class="text-[10px] font-black uppercase tracking-[0.12em] text-pm-muted">{{ $it ? 'Snapshot configurazione' : 'Configuration snapshot' }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @forelse ($latest?->components ?? collect() as $component)
                                        <span class="rounded-full border border-pm-border bg-pm-panel px-2.5 py-1 text-xs text-pm-text-secondary">{{ $component->name }}</span>
                                    @empty
                                        <span class="text-sm text-pm-muted">{{ $it ? 'Nessun componente' : 'No components' }}</span>
                                    @endforelse
                                </div>
                            </div>

                            <div class="rounded-xl border {{ $aligned ? 'border-pm-success/25 bg-pm-success-subtle' : 'border-pm-warning/30 bg-pm-warning-subtle' }} p-4">
                                <p class="text-[10px] font-black uppercase tracking-[0.12em] {{ $aligned ? 'text-pm-success' : 'text-pm-warning' }}">{{ $it ? 'Stato fisico adesso' : 'Physical state now' }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @forelse ($physicalComponents as $component)
                                        <span class="rounded-full border border-pm-border bg-pm-panel px-2.5 py-1 text-xs text-pm-text-secondary">{{ $component->name }}</span>
                                    @empty
                                        <span class="text-sm text-pm-muted">{{ $it ? 'Nessun componente montato' : 'No installed components' }}</span>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        @if (! $aligned)
                            <div class="mt-4 rounded-xl border border-pm-warning/30 bg-pm-warning-subtle p-4">
                                <p class="text-sm font-bold text-pm-warning">{{ $it ? 'La build fisica è cambiata.' : 'The physical build changed.' }}</p>
                                <p class="mt-1 text-sm leading-6 text-pm-text-secondary">
                                    {{ $it ? 'Prima di registrare una nuova sessione, crea una nuova versione che fotografi lo stato attuale.' : 'Before recording another session, create a new version that captures the current physical state.' }}
                                </p>
                            </div>
                        @endif

                        <div class="mt-5 flex flex-wrap gap-2 border-t border-pm-border pt-4">
                            <a class="pm-ghost-button" href="{{ route('components.index') }}">{{ $it ? 'Gestisci componenti' : 'Manage components' }}</a>

                            <x-crud-modal
                                id="edit-configuration-{{ $configuration->id }}"
                                :title="$it ? 'Modifica dettagli' : 'Edit details'"
                                :trigger="$it ? 'Modifica dettagli' : 'Edit details'"
                                trigger-class="pm-ghost-button"
                            >
                                @can('team-write')
                                    <form method="POST" action="{{ route('configurations.update', $configuration) }}" class="grid gap-4 sm:grid-cols-2">
                                        @csrf
                                        @method('PUT')
                                        <label class="grid gap-2">
                                            <span class="pm-label">{{ $it ? 'Nome configurazione' : 'Configuration name' }}</span>
                                            <input class="pm-input" name="name" type="text" value="{{ $configuration->name }}" required maxlength="120">
                                        </label>
                                        <label class="grid gap-2">
                                            <span class="pm-label">{{ $it ? 'Descrizione' : 'Description' }}</span>
                                            <textarea class="pm-input min-h-20" name="description" maxlength="2000">{{ $configuration->description }}</textarea>
                                        </label>
                                        <div class="sm:col-span-2 flex justify-end">
                                            <button class="pm-race-button" type="submit">{{ $it ? 'Salva modifiche' : 'Save changes' }}</button>
                                        </div>
                                    </form>
                                @endcan
                            </x-crud-modal>

                            <x-crud-modal
                                id="version-configuration-{{ $configuration->id }}"
                                :title="$it ? 'Cattura nuova versione' : 'Capture new version'"
                                :description="$configuration->name.' · v'.(($latest?->version_number ?? 0) + 1)"
                                :trigger="$it ? 'Crea nuova versione' : 'Create new version'"
                                trigger-class="pm-ghost-button"
                            >
                                @can('team-write')
                                    <form method="POST" action="{{ route('configurations.versions.store', $configuration) }}" class="space-y-4">
                                        @csrf

                                        <div class="rounded-xl border border-pm-border bg-pm-subtle p-4">
                                            <p class="text-sm font-bold text-pm-text">{{ $it ? 'Componenti che verranno salvati' : 'Components that will be saved' }}</p>
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @forelse ($physicalComponents as $component)
                                                    <span class="rounded-full border border-pm-border bg-pm-panel px-2.5 py-1 text-xs text-pm-text-secondary">{{ $component->name }}</span>
                                                @empty
                                                    <span class="text-sm font-semibold text-pm-warning">{{ $it ? 'Monta almeno un componente prima di creare una versione.' : 'Install at least one component before creating a version.' }}</span>
                                                @endforelse
                                            </div>
                                        </div>

                                        <label class="grid gap-2">
                                            <span class="pm-label">{{ $it ? 'Nota versione' : 'Version note' }}</span>
                                            <textarea class="pm-input min-h-20" name="notes" maxlength="2000" placeholder="{{ $it ? 'Es. sostituito motore dopo warm-up' : 'E.g. engine changed after warm-up' }}"></textarea>
                                        </label>

                                        <label class="grid gap-2">
                                            <span class="pm-label">{{ $it ? 'Costo modifica (€)' : 'Change cost (€)' }}</span>
                                            <input class="pm-input" name="operation_cost" type="number" min="0" max="1000000" step="0.01">
                                            <span class="text-xs text-pm-muted">{{ $it ? 'Opzionale · registrato nei Costi insieme alla versione' : 'Optional · recorded in Expenses with this version' }}</span>
                                        </label>

                                        <div class="flex items-center justify-between gap-3">
                                            <a href="{{ route('components.index') }}" class="text-sm font-bold text-pm-accent hover:underline">{{ $it ? 'Modifica stato fisico' : 'Change physical state' }}</a>
                                            <button class="pm-race-button" type="submit" @disabled($physicalComponents->isEmpty())>{{ $it ? 'Cattura versione' : 'Capture version' }}</button>
                                        </div>
                                    </form>
                                @endcan
                            </x-crud-modal>

                            @if ($aligned)
                                <a class="pm-race-button" href="{{ route('sessions.index', ['configuration_version_id' => $latest?->id]) }}#record-session">{{ $it ? 'Registra sessione' : 'Record session' }}</a>
                            @else
                                <span class="inline-flex items-center rounded-lg border border-pm-warning/30 bg-pm-warning-subtle px-3 py-2 text-xs font-bold text-pm-warning">
                                    {{ $it ? 'Sessione bloccata finché non allinei la versione' : 'Session blocked until version is aligned' }}
                                </span>
                            @endif
                        </div>
                    </article>
                @empty
                    <x-pitmetric.empty-state
                        :title="$it ? 'Nessuna configurazione' : 'No configurations'"
                        :description="$it ? 'Dopo aver montato i componenti sul mezzo, cattura qui la prima build versionata.' : 'After installing components on the vehicle, capture the first versioned build here.'"
                    />
                @endforelse
            </div>
        </div>
    </div>
</x-layouts::app>
