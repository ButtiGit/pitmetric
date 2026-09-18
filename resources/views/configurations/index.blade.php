<x-layouts::app :title="__('Configurations')">
    @php($it = app()->getLocale() === 'it')
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1360px] space-y-5">
            <div class="pm-next-actions"><span class="font-semibold text-pm-muted">{{ __('workflow.next') }}</span><a href="{{ route('garage.index') }}#create-vehicle">{{ __('workflow.garage') }}</a><a href="{{ route('components.index') }}#create-component">{{ __('workflow.components') }}</a><a href="{{ route('setups.index') }}">{{ __('workflow.setups') }}</a><a href="{{ route('sessions.index') }}#record-session">{{ __('workflow.sessions') }}</a></div>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div><p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">CONFIGURATIONS</p><x-pitmetric.page-header :title="$it ? 'Configurazioni versionate' : 'Versioned configurations'" :description="$it ? 'Ogni versione fotografa i componenti montati. Quando una sessione la usa, resta immutabile.' : 'Each version snapshots installed components. Once used by a session it remains immutable.'" /></div>
                <x-crud-modal id="create-configuration" :title="$it ? 'Nuova configurazione' : 'New configuration'" :description="$it ? 'Scegli mezzo e componenti. Il salvataggio aggiorna anche i montaggi fisici del mezzo.' : 'Choose a vehicle and components. Saving also updates the physical installations on the vehicle.'" :trigger="$it ? '+ Nuova configurazione' : '+ New configuration'">
                    @can('team-write')<form method="POST" action="{{ route('configurations.store') }}" class="space-y-4">
                        @csrf
                        <div class="grid gap-4 md:grid-cols-2"><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Mezzo' : 'Vehicle' }}</span><select class="pm-input" name="vehicle_id" required><option value="">{{ $it ? 'Seleziona' : 'Select' }}</option>@foreach ($vehicles as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->name }}</option>@endforeach</select></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Nome build' : 'Build name' }}</span><input class="pm-input" name="name" required maxlength="120" placeholder="Race Build"></label></div>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Descrizione' : 'Description' }}</span><textarea class="pm-input min-h-20" name="description" maxlength="2000"></textarea></label>
                        <div><span class="pm-label">{{ $it ? 'Componenti installati' : 'Installed components' }}</span><div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">@forelse ($components as $component)<label class="flex items-center gap-2 rounded-lg border border-pm-border bg-pm-subtle px-3 py-2 text-sm text-pm-text-secondary"><input type="checkbox" name="component_ids[]" value="{{ $component->id }}">{{ $component->name }} <span class="text-pm-muted">· {{ $component->type->name }}</span></label>@empty<p class="text-sm text-pm-muted">{{ $it ? 'Crea prima dei componenti.' : 'Create components first.' }}</p>@endforelse</div></div>
                        <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Costo preparazione (€)' : 'Setup work cost (€)' }}</span><input class="pm-input" name="operation_cost" type="number" min="0" max="1000000" step="0.01"><span class="text-xs text-pm-muted">{{ $it ? 'Opzionale · viene aggiunto automaticamente ai Costi' : 'Optional · automatically added to Expenses' }}</span></label>
                        <div class="flex justify-end"><button class="pm-race-button" type="submit" @disabled($vehicles->isEmpty())>{{ $it ? 'Crea configurazione' : 'Create configuration' }}</button></div>
                    </form>@endcan
                </x-crud-modal>
            </div>

            @if (session('status'))<div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <div class="grid gap-4 lg:grid-cols-2">
                @forelse ($configurations as $configuration)
                    @php($latest = $configuration->versions->first())
                    <article class="pm-panel p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-bold text-pm-accent">{{ $configuration->vehicle->name }}</p><h2 class="mt-1 text-lg font-black text-pm-text">{{ $configuration->name }}</h2><p class="mt-1 text-sm text-pm-muted">v{{ $latest?->version_number ?? 0 }} · {{ $latest?->locked_at ? ($it ? 'bloccata' : 'locked') : ($it ? 'attuale' : 'current') }}</p></div>@can('team-write')<form method="POST" action="{{ route('configurations.destroy', $configuration) }}" onsubmit="return confirm(@js($it ? 'Archiviare la configurazione?' : 'Archive configuration?'))">@csrf @method('DELETE')<button class="text-xs font-bold text-pm-danger hover:underline">{{ $it ? 'Archivia' : 'Archive' }}</button></form>@endcan</div>
                        <div class="mt-4 flex flex-wrap gap-2">@forelse ($latest?->components ?? collect() as $component)<span class="rounded-full border border-pm-border bg-pm-subtle px-2.5 py-1 text-xs text-pm-text-secondary">{{ $component->name }}</span>@empty<span class="text-sm text-pm-muted">{{ $it ? 'Nessun componente' : 'No components' }}</span>@endforelse</div>
                        <div class="mt-5 flex flex-wrap gap-2 border-t border-pm-border pt-4"><x-crud-modal id="edit-configuration-{{ $configuration->id }}" :title="$it ? 'Modifica dettagli' : 'Edit details'" :trigger="$it ? 'Modifica dettagli' : 'Edit details'" trigger-class="pm-ghost-button">
 @can('team-write')<form method="POST" action="{{ route('configurations.update', $configuration) }}" class="grid gap-4 sm:grid-cols-2">@csrf @method('PUT')
 <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Nome configurazione' : 'Configuration name' }}</span><input class="pm-input" name="name" type="text" value="{{ $configuration->name }}" required maxlength="120"></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Descrizione' : 'Description' }}</span><textarea class="pm-input min-h-20" name="description" maxlength="2000">{{ $configuration->description }}</textarea></label>
 <div class="sm:col-span-2 flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Salva modifiche' : 'Save changes' }}</button></div>
 </form>@endcan</x-crud-modal><a class="pm-ghost-button" href="{{ route('sessions.index', ['configuration_version_id' => $latest?->id]) }}#record-session">{{ $it ? 'Registra sessione' : 'Record session' }}</a>
                            <x-crud-modal id="version-configuration-{{ $configuration->id }}" :title="$it ? 'Nuova versione' : 'New version'" :description="$configuration->name.' · v'.(($latest?->version_number ?? 0) + 1)" :trigger="$it ? 'Crea nuova versione' : 'Create new version'" trigger-class="pm-ghost-button">
                                @can('team-write')<form method="POST" action="{{ route('configurations.versions.store', $configuration) }}" class="space-y-4">
                                    @csrf
                                    <div class="grid gap-2 sm:grid-cols-2">@foreach ($components as $component)<label class="flex items-center gap-2 rounded-lg border border-pm-border bg-pm-subtle px-3 py-2 text-sm text-pm-text-secondary"><input type="checkbox" name="component_ids[]" value="{{ $component->id }}" @checked($latest?->components->contains($component))>{{ $component->name }}</label>@endforeach</div>
                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Nota versione' : 'Version note' }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="2000"></textarea></label>
                                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Costo modifica (€)' : 'Change cost (€)' }}</span><input class="pm-input" name="operation_cost" type="number" min="0" max="1000000" step="0.01"><span class="text-xs text-pm-muted">{{ $it ? 'Opzionale · registrato nei Costi insieme alla versione' : 'Optional · recorded in Expenses with this version' }}</span></label>
                                    <div class="flex justify-end"><button class="pm-race-button" type="submit">{{ $it ? 'Salva nuova versione' : 'Save new version' }}</button></div>
                                </form>@endcan
                            </x-crud-modal>
                        </div>
                    </article>
                @empty
                    <x-pitmetric.empty-state :title="$it ? 'Nessuna configurazione' : 'No configurations'" :description="$it ? 'Crea una build per collegare componenti e sessioni.' : 'Create a build to connect components and sessions.'" />
                @endforelse
            </div>
        </div>
    </div>
</x-layouts::app>
