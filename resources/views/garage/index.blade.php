<x-layouts::app :title="__('demo.nav.garage')">
    @php
        $categoryLabels = collect(\App\Models\Vehicle::CATEGORIES)->mapWithKeys(fn (string $category) => [$category => __('garage.categories.'.$category)]);
        $statusLabels = collect(\App\Models\Vehicle::STATUSES)->mapWithKeys(fn (string $status) => [$status => __('garage.statuses.'.$status)]);
        $it = app()->getLocale() === 'it';
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1360px] space-y-5">
            <section class="pm-panel p-5 sm:p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full border border-pm-success/30 bg-pm-success-subtle px-2.5 py-1 text-[11px] font-bold tracking-[0.08em] text-pm-success">{{ __('garage.workspace.badge') }}</span>
                            <span class="text-xs text-pm-muted">{{ $workspace->name }}</span>
                        </div>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-pm-text-secondary">{{ __('garage.workspace.copy') }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-right">
                            <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-pm-muted">{{ __('garage.workspace.vehicle_count') }}</p>
                            <p class="mt-1 text-2xl font-black text-pm-text">{{ $vehicles->count() }}</p>
                        </div>
                        <x-crud-modal
                            id="create-vehicle"
                            :title="$it ? 'Aggiungi mezzo' : 'Add vehicle'"
                            :description="$it ? 'Scegli il tipo: PitMetric userà automaticamente la silhouette corrispondente nel Garage.' : 'Choose the type: PitMetric will automatically use the matching silhouette in the Garage.'"
                            :trigger="$it ? '+ Aggiungi mezzo' : '+ Add vehicle'"
                        >
                            <form method="POST" action="{{ route('garage.store') }}" class="grid gap-4 md:grid-cols-2">
                                @csrf
                                <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ __('garage.fields.name') }}</span><input class="pm-input" name="name" value="{{ old('name') }}" required maxlength="100"></label>
                                <label class="grid gap-2">
                                    <span class="pm-label">{{ __('garage.fields.category') }}</span>
                                    <select class="pm-input" name="category" required>@foreach ($categoryLabels as $value => $label)<option value="{{ $value }}" @selected(old('category', 'kart') === $value)>{{ $label }}</option>@endforeach</select>
                                    <span class="text-xs text-pm-muted">{{ $it ? 'Determina anche il simbolo mostrato nel Garage.' : 'Also determines the symbol shown in the Garage.' }}</span>
                                </label>
                                <label class="grid gap-2"><span class="pm-label">{{ __('garage.fields.status') }}</span><select class="pm-input" name="status" required>@foreach ($statusLabels as $value => $label)<option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>@endforeach</select></label>
                                <label class="grid gap-2"><span class="pm-label">{{ __('garage.fields.manufacturer') }}</span><input class="pm-input" name="manufacturer" value="{{ old('manufacturer') }}" maxlength="100"></label>
                                <label class="grid gap-2"><span class="pm-label">{{ __('garage.fields.model') }}</span><input class="pm-input" name="model" value="{{ old('model') }}" maxlength="100"></label>
                                <label class="grid gap-2"><span class="pm-label">{{ __('garage.fields.year') }}</span><input class="pm-input" name="year" type="number" min="1900" max="{{ now()->year + 1 }}" value="{{ old('year') }}"></label>
                                <label class="grid gap-2"><span class="pm-label">{{ __('garage.fields.identifier') }}</span><input class="pm-input" name="identifier" value="{{ old('identifier') }}" maxlength="100"></label>
                                <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Costo acquisto (€)' : 'Acquisition cost (€)' }}</span><input class="pm-input" name="purchase_cost" type="number" min="0" max="10000000" step="0.01" value="{{ old('purchase_cost') }}"><span class="text-xs text-pm-muted">{{ $it ? 'Opzionale · viene registrato automaticamente nei Costi' : 'Optional · automatically recorded in Expenses' }}</span></label>
                                <label class="grid gap-2 md:col-span-2"><span class="pm-label">{{ __('garage.fields.notes') }}</span><textarea class="pm-input min-h-24" name="notes" maxlength="2000">{{ old('notes') }}</textarea></label>
                                <div class="md:col-span-2 flex justify-end"><button class="pm-race-button" type="submit">{{ __('garage.create.submit') }}</button></div>
                            </form>
                        </x-crud-modal>
                    </div>
                </div>
            </section>

            @if (session('status'))<div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>@endif
            @if (session('error'))<div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle px-4 py-3 text-sm font-semibold text-pm-danger">{{ session('error') }}</div>@endif
            @if ($errors->any())<div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger"><ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">GARAGE</p>
                <h1 class="mt-2 text-2xl font-black tracking-[-0.03em] text-pm-text sm:text-3xl">{{ __('garage.title') }}</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-pm-text-secondary">{{ __('garage.description') }}</p>
            </div>

            @if ($vehicles->isEmpty())
                <div class="pm-panel border-dashed p-8 text-center"><h3 class="font-bold text-pm-text">{{ __('garage.empty.title') }}</h3><p class="mx-auto mt-2 max-w-xl text-sm text-pm-text-secondary">{{ __('garage.empty.description') }}</p></div>
            @else
                <div class="grid gap-5 xl:grid-cols-2">
                    @foreach ($vehicles as $vehicle)
                        @php($activeInstallations = $vehicle->componentInstallations)
                        <article class="pm-panel group relative isolate min-h-[350px] overflow-hidden p-0 transition duration-300 hover:-translate-y-1 hover:border-pm-accent/40 hover:shadow-xl focus-within:border-pm-accent/40 focus-within:shadow-xl">
                            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                                <div class="absolute -left-14 top-1/2 w-[78%] -translate-y-1/2 text-pm-accent opacity-[0.14] transition duration-500 group-hover:translate-x-2 group-hover:scale-[1.03] group-hover:opacity-[0.22]">
                                    <x-pitmetric.vehicle-silhouette :type="$vehicle->category" />
                                </div>
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-pm-page/25 to-pm-page/90"></div>
                                <div class="absolute inset-x-0 bottom-0 h-44 bg-gradient-to-t from-pm-page via-pm-page/95 to-transparent"></div>
                            </div>

                            <div class="relative z-10 flex min-h-[350px] flex-col p-5 sm:p-6">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full border border-pm-border bg-pm-subtle/90 px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.12em] text-pm-muted">{{ $categoryLabels[$vehicle->category] ?? $vehicle->category }}</span>
                                        <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.1em] {{ $vehicle->status === 'active' ? 'bg-pm-success-subtle text-pm-success' : 'bg-pm-subtle text-pm-muted' }}">{{ $statusLabels[$vehicle->status] ?? $vehicle->status }}</span>
                                        <span class="rounded-full border border-pm-border bg-pm-page/70 px-2.5 py-1 text-[10px] font-bold text-pm-muted">{{ $activeInstallations->count() }} {{ $it ? 'pezzi' : 'parts' }}</span>
                                    </div>
                                    <span class="rounded-lg border border-pm-border bg-pm-page/75 px-2.5 py-1.5 font-mono text-[11px] font-bold text-pm-muted">#{{ $vehicle->id }}</span>
                                </div>

                                <div class="mt-10 md:ml-[34%]">
                                    <h2 class="text-2xl font-black tracking-[-0.035em] text-pm-text sm:text-3xl">{{ $vehicle->name }}</h2>
                                    <p class="mt-2 text-sm font-semibold text-pm-text-secondary">
                                        @if ($vehicle->manufacturer || $vehicle->model)
                                            {{ collect([$vehicle->manufacturer, $vehicle->model])->filter()->join(' ') }}
                                        @else
                                            {{ $categoryLabels[$vehicle->category] ?? $vehicle->category }}
                                        @endif
                                        @if ($vehicle->year) · {{ $vehicle->year }} @endif
                                    </p>
                                    @if ($vehicle->identifier)<p class="mt-2 font-mono text-xs text-pm-muted">{{ $vehicle->identifier }}</p>@endif
                                    @if ($vehicle->notes)<p class="mt-3 max-w-xl text-sm leading-6 text-pm-text-secondary">{{ \Illuminate\Support\Str::limit($vehicle->notes, 120) }}</p>@endif
                                </div>

                                <div class="mt-auto pt-8">
                                    <div class="flex items-end justify-between gap-3 border-t border-pm-border/80 pt-4">
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-[0.14em] text-pm-accent">{{ __('garage.components.title') }}</p>
                                            <p class="mt-1 hidden text-xs text-pm-muted md:block">{{ __('garage.components.hover_hint') }}</p>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-2">
                                            <x-crud-modal id="edit-vehicle-{{ $vehicle->id }}" :title="$it ? 'Modifica mezzo' : 'Edit vehicle'" :trigger="__('garage.edit.toggle')" trigger-class="pm-ghost-button">
                                                <form method="POST" action="{{ route('garage.update', $vehicle) }}" class="grid gap-4 sm:grid-cols-2">
                                                    @csrf @method('PUT')
                                                    <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ __('garage.fields.name') }}</span><input class="pm-input" name="name" value="{{ $vehicle->name }}" required maxlength="100"></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ __('garage.fields.category') }}</span><select class="pm-input" name="category" required>@foreach ($categoryLabels as $value => $label)<option value="{{ $value }}" @selected($vehicle->category === $value)>{{ $label }}</option>@endforeach</select><span class="text-xs text-pm-muted">{{ $it ? 'Aggiorna anche la silhouette della card.' : 'Also updates the card silhouette.' }}</span></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ __('garage.fields.status') }}</span><select class="pm-input" name="status" required>@foreach ($statusLabels as $value => $label)<option value="{{ $value }}" @selected($vehicle->status === $value)>{{ $label }}</option>@endforeach</select></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ __('garage.fields.manufacturer') }}</span><input class="pm-input" name="manufacturer" value="{{ $vehicle->manufacturer }}" maxlength="100"></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ __('garage.fields.model') }}</span><input class="pm-input" name="model" value="{{ $vehicle->model }}" maxlength="100"></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ __('garage.fields.year') }}</span><input class="pm-input" name="year" type="number" min="1900" max="{{ now()->year + 1 }}" value="{{ $vehicle->year }}"></label>
                                                    <label class="grid gap-2"><span class="pm-label">{{ __('garage.fields.identifier') }}</span><input class="pm-input" name="identifier" value="{{ $vehicle->identifier }}" maxlength="100"></label>
                                                    <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ __('garage.fields.notes') }}</span><textarea class="pm-input min-h-20" name="notes" maxlength="2000">{{ $vehicle->notes }}</textarea></label>
                                                    <div class="sm:col-span-2 flex justify-end"><button class="pm-race-button" type="submit">{{ __('garage.edit.submit') }}</button></div>
                                                </form>
                                            </x-crud-modal>

                                            <form method="POST" action="{{ route('garage.destroy', $vehicle) }}" onsubmit="return confirm(@js(__('garage.delete.confirm')))" >@csrf @method('DELETE')<button class="pm-danger-button" type="submit">{{ __('garage.delete.submit') }}</button></form>
                                        </div>
                                    </div>

                                    <div class="mt-3 transition duration-300 md:pointer-events-none md:translate-y-3 md:opacity-0 md:group-hover:pointer-events-auto md:group-hover:translate-y-0 md:group-hover:opacity-100 md:group-focus-within:pointer-events-auto md:group-focus-within:translate-y-0 md:group-focus-within:opacity-100">
                                        @if ($activeInstallations->isEmpty())
                                            <a href="{{ route('components.index') }}" class="flex items-center justify-between rounded-xl border border-dashed border-pm-border bg-pm-page/70 px-3 py-3 text-sm transition hover:border-pm-accent/40 hover:bg-pm-accent/5">
                                                <span class="font-semibold text-pm-muted">{{ __('garage.components.empty') }}</span>
                                                <span class="text-xs font-black text-pm-accent">{{ __('garage.components.empty_action') }} →</span>
                                            </a>
                                        @else
                                            <div class="grid max-h-28 gap-2 overflow-y-auto pr-1 sm:grid-cols-2">
                                                @foreach ($activeInstallations as $installation)
                                                    <a href="{{ route('components.index') }}#component-{{ $installation->component->id }}" class="group/component rounded-xl border border-pm-border bg-pm-page/80 px-3 py-2.5 transition hover:border-pm-accent/50 hover:bg-pm-accent/5" title="{{ __('garage.components.open') }}: {{ $installation->component->name }}">
                                                        <div class="flex items-center justify-between gap-2">
                                                            <span class="truncate text-sm font-black text-pm-text">{{ $installation->component->name }}</span>
                                                            <span class="text-xs font-black text-pm-accent opacity-0 transition group-hover/component:opacity-100">↗</span>
                                                        </div>
                                                        <p class="mt-1 truncate text-[11px] text-pm-muted">{{ $installation->component->type->name }}@if ($installation->position_or_role) · {{ $installation->position_or_role }}@endif</p>
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
