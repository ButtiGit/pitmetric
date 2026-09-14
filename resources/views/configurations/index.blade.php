<x-layouts::app :title="__('Configurations')">
    @php($it = app()->getLocale() === 'it')
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1360px] space-y-5">
            <div><p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">CONFIGURATIONS</p><x-pitmetric.page-header :title="$it ? 'Configurazioni versionate' : 'Versioned configurations'" :description="$it ? 'Ogni versione fotografa i componenti montati. Quando una sessione la usa, resta immutabile.' : 'Each version snapshots installed components. Once used by a session it remains immutable.'" /></div>
            @if (session('status'))<div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>@endif
            @if ($errors->any())<div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <section class="pm-panel p-5 sm:p-6">
                <h2 class="text-lg font-black text-pm-text">{{ $it ? 'Nuova configurazione' : 'New configuration' }}</h2>
                <form method="POST" action="{{ route('configurations.store') }}" class="mt-5 space-y-4">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2"><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Mezzo' : 'Vehicle' }}</span><select class="pm-input" name="vehicle_id" required><option value="">{{ $it ? 'Seleziona' : 'Select' }}</option>@foreach ($vehicles as $vehicle)<option value="{{ $vehicle->id }}">{{ $vehicle->name }}</option>@endforeach</select></label><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Nome build' : 'Build name' }}</span><input class="pm-input" name="name" required maxlength="120" placeholder="Race Build"></label></div>
                    <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Descrizione' : 'Description' }}</span><textarea class="pm-input min-h-20" name="description" maxlength="2000"></textarea></label>
                    <div><span class="pm-label">{{ $it ? 'Componenti installati' : 'Installed components' }}</span><div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">@forelse ($components as $component)<label class="flex items-center gap-2 rounded-lg border border-pm-border bg-pm-subtle px-3 py-2 text-sm text-pm-text-secondary"><input type="checkbox" name="component_ids[]" value="{{ $component->id }}">{{ $component->name }} <span class="text-pm-muted">· {{ $component->type->name }}</span></label>@empty<p class="text-sm text-pm-muted">{{ $it ? 'Crea prima dei componenti.' : 'Create components first.' }}</p>@endforelse</div></div>
                    <button class="pm-race-button" type="submit" @disabled($vehicles->isEmpty())>{{ $it ? 'Crea configurazione' : 'Create configuration' }}</button>
                </form>
            </section>

            <div class="grid gap-4 lg:grid-cols-2">
                @forelse ($configurations as $configuration)
                    @php($latest = $configuration->versions->first())
                    <article class="pm-panel p-5 sm:p-6">
                        <div class="flex items-start justify-between gap-3"><div><p class="text-xs font-bold text-pm-accent">{{ $configuration->vehicle->name }}</p><h2 class="mt-1 text-lg font-black text-pm-text">{{ $configuration->name }}</h2><p class="mt-1 text-sm text-pm-muted">v{{ $latest?->version_number ?? 0 }} · {{ $latest?->locked_at ? ($it ? 'bloccata' : 'locked') : ($it ? 'attuale' : 'current') }}</p></div><form method="POST" action="{{ route('configurations.destroy', $configuration) }}" onsubmit="return confirm(@js($it ? 'Archiviare la configurazione?' : 'Archive configuration?'))">@csrf @method('DELETE')<button class="text-xs font-bold text-pm-danger hover:underline">{{ $it ? 'Archivia' : 'Archive' }}</button></form></div>
                        <div class="mt-4 flex flex-wrap gap-2">@forelse ($latest?->components ?? collect() as $component)<span class="rounded-full border border-pm-border bg-pm-subtle px-2.5 py-1 text-xs text-pm-text-secondary">{{ $component->name }}</span>@empty<span class="text-sm text-pm-muted">{{ $it ? 'Nessun componente' : 'No components' }}</span>@endforelse</div>
                        <details class="mt-5 border-t border-pm-border pt-4"><summary class="cursor-pointer text-sm font-bold text-pm-text hover:text-pm-accent">{{ $it ? 'Crea nuova versione' : 'Create new version' }}</summary><form method="POST" action="{{ route('configurations.versions.store', $configuration) }}" class="mt-4 space-y-4">@csrf<div class="grid gap-2 sm:grid-cols-2">@foreach ($components as $component)<label class="flex items-center gap-2 rounded-lg border border-pm-border bg-pm-subtle px-3 py-2 text-sm text-pm-text-secondary"><input type="checkbox" name="component_ids[]" value="{{ $component->id }}" @checked($latest?->components->contains($component))>{{ $component->name }}</label>@endforeach</div><label class="grid gap-2"><span class="pm-label">{{ $it ? 'Nota versione' : 'Version note' }}</span><input class="pm-input" name="notes" maxlength="2000"></label><button class="pm-ghost-button" type="submit">{{ $it ? 'Salva nuova versione' : 'Save new version' }}</button></form></details>
                    </article>
                @empty
                    <x-pitmetric.empty-state :title="$it ? 'Nessuna configurazione' : 'No configurations'" :description="$it ? 'Crea una build per collegare componenti e sessioni.' : 'Create a build to connect components and sessions.'" />
                @endforelse
            </div>
        </div>
    </div>
</x-layouts::app>
