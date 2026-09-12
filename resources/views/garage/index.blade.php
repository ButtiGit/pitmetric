<x-layouts::app :title="__('demo.nav.garage')">
    @php
        $categoryLabels = collect(\App\Models\Vehicle::CATEGORIES)->mapWithKeys(fn (string $category) => [$category => __('garage.categories.'.$category)]);
        $statusLabels = collect(\App\Models\Vehicle::STATUSES)->mapWithKeys(fn (string $status) => [$status => __('garage.statuses.'.$status)]);
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1360px] space-y-5">
            <section class="pm-panel p-5 sm:p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-start gap-3">
                        <span class="mt-1 size-2.5 shrink-0 rounded-full bg-pm-success"></span>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full border border-pm-success/30 bg-pm-success-subtle px-2.5 py-1 text-[11px] font-bold tracking-[0.08em] text-pm-success">{{ __('garage.workspace.badge') }}</span>
                                <span class="text-xs text-pm-muted">{{ $workspace->name }} · {{ auth()->user()->email }}</span>
                            </div>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-pm-text-secondary">{{ __('garage.workspace.copy') }}</p>
                        </div>
                    </div>
                    <div class="rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-right">
                        <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-pm-muted">{{ __('garage.workspace.vehicle_count') }}</p>
                        <p class="mt-1 text-2xl font-black text-pm-text">{{ $vehicles->count() }}</p>
                    </div>
                </div>
            </section>

            @if (session('status'))
                <div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger" role="alert">
                    <p class="font-bold">{{ __('garage.validation_title') }}</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">GARAGE</p>
                <h1 class="mt-2 text-2xl font-black tracking-[-0.03em] text-pm-text sm:text-3xl">{{ __('garage.title') }}</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-pm-text-secondary">{{ __('garage.description') }}</p>
            </div>

            <section class="pm-panel p-5 sm:p-6">
                <div class="mb-5">
                    <h2 class="text-lg font-black text-pm-text">{{ __('garage.create.title') }}</h2>
                    <p class="mt-1 text-sm text-pm-text-secondary">{{ __('garage.create.description') }}</p>
                </div>

                <form method="POST" action="{{ route('garage.store') }}" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @csrf

                    <label class="grid gap-2 xl:col-span-2">
                        <span class="pm-label">{{ __('garage.fields.name') }}</span>
                        <input class="pm-input" name="name" type="text" value="{{ old('name') }}" required maxlength="100" placeholder="Kart #27">
                    </label>

                    <label class="grid gap-2">
                        <span class="pm-label">{{ __('garage.fields.category') }}</span>
                        <select class="pm-input" name="category" required>
                            @foreach ($categoryLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('category', 'kart') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="grid gap-2">
                        <span class="pm-label">{{ __('garage.fields.status') }}</span>
                        <select class="pm-input" name="status" required>
                            @foreach ($statusLabels as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', 'active') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="grid gap-2">
                        <span class="pm-label">{{ __('garage.fields.manufacturer') }}</span>
                        <input class="pm-input" name="manufacturer" type="text" value="{{ old('manufacturer') }}" maxlength="100" placeholder="Tony Kart">
                    </label>

                    <label class="grid gap-2">
                        <span class="pm-label">{{ __('garage.fields.model') }}</span>
                        <input class="pm-input" name="model" type="text" value="{{ old('model') }}" maxlength="100" placeholder="Racer 401">
                    </label>

                    <label class="grid gap-2">
                        <span class="pm-label">{{ __('garage.fields.year') }}</span>
                        <input class="pm-input" name="year" type="number" value="{{ old('year') }}" min="1900" max="{{ now()->year + 1 }}" step="1" inputmode="numeric">
                    </label>

                    <label class="grid gap-2">
                        <span class="pm-label">{{ __('garage.fields.identifier') }}</span>
                        <input class="pm-input" name="identifier" type="text" value="{{ old('identifier') }}" maxlength="100" placeholder="CHASSIS-001">
                    </label>

                    <label class="grid gap-2 md:col-span-2 xl:col-span-4">
                        <span class="pm-label">{{ __('garage.fields.notes') }}</span>
                        <textarea class="pm-input min-h-24 resize-y" name="notes" maxlength="2000" placeholder="{{ __('garage.fields.notes_placeholder') }}">{{ old('notes') }}</textarea>
                    </label>

                    <div class="md:col-span-2 xl:col-span-4">
                        <button class="pm-race-button" type="submit">{{ __('garage.create.submit') }}</button>
                    </div>
                </form>
            </section>

            <section>
                <div class="mb-3 flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-black text-pm-text">{{ __('garage.list.title') }}</h2>
                        <p class="mt-1 text-sm text-pm-text-secondary">{{ __('garage.list.description') }}</p>
                    </div>
                </div>

                @if ($vehicles->isEmpty())
                    <div class="pm-panel border-dashed p-8 text-center">
                        <div class="mx-auto grid size-11 place-items-center rounded-xl border border-pm-border bg-pm-subtle text-xl">🏁</div>
                        <h3 class="mt-4 font-bold text-pm-text">{{ __('garage.empty.title') }}</h3>
                        <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-pm-text-secondary">{{ __('garage.empty.description') }}</p>
                    </div>
                @else
                    <div class="grid gap-4 lg:grid-cols-2">
                        @foreach ($vehicles as $vehicle)
                            <article class="pm-panel p-5 sm:p-6">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="truncate text-lg font-black text-pm-text">{{ $vehicle->name }}</h3>
                                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $vehicle->status === 'active' ? 'bg-pm-success-subtle text-pm-success' : 'bg-pm-subtle text-pm-muted' }}">{{ $statusLabels[$vehicle->status] ?? $vehicle->status }}</span>
                                        </div>
                                        <p class="mt-1 text-sm text-pm-text-secondary">
                                            {{ $categoryLabels[$vehicle->category] ?? $vehicle->category }}
                                            @if ($vehicle->manufacturer || $vehicle->model)
                                                · {{ collect([$vehicle->manufacturer, $vehicle->model])->filter()->join(' ') }}
                                            @endif
                                            @if ($vehicle->year)
                                                · {{ $vehicle->year }}
                                            @endif
                                        </p>
                                        @if ($vehicle->identifier)
                                            <p class="mt-2 font-mono text-xs text-pm-muted">{{ $vehicle->identifier }}</p>
                                        @endif
                                    </div>

                                    <span class="rounded-lg border border-pm-border bg-pm-subtle px-2.5 py-1.5 text-xs font-bold text-pm-muted">#{{ $vehicle->id }}</span>
                                </div>

                                @if ($vehicle->notes)
                                    <p class="mt-4 rounded-xl border border-pm-border bg-pm-subtle p-3 text-sm leading-6 text-pm-text-secondary">{{ $vehicle->notes }}</p>
                                @endif

                                <details class="mt-5 border-t border-pm-border pt-4">
                                    <summary class="cursor-pointer select-none text-sm font-bold text-pm-text hover:text-pm-accent">{{ __('garage.edit.toggle') }}</summary>

                                    <form method="POST" action="{{ route('garage.update', $vehicle) }}" class="mt-4 grid gap-4 sm:grid-cols-2">
                                        @csrf
                                        @method('PUT')

                                        <label class="grid gap-2 sm:col-span-2">
                                            <span class="pm-label">{{ __('garage.fields.name') }}</span>
                                            <input class="pm-input" name="name" type="text" value="{{ $vehicle->name }}" required maxlength="100">
                                        </label>

                                        <label class="grid gap-2">
                                            <span class="pm-label">{{ __('garage.fields.category') }}</span>
                                            <select class="pm-input" name="category" required>
                                                @foreach ($categoryLabels as $value => $label)
                                                    <option value="{{ $value }}" @selected($vehicle->category === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </label>

                                        <label class="grid gap-2">
                                            <span class="pm-label">{{ __('garage.fields.status') }}</span>
                                            <select class="pm-input" name="status" required>
                                                @foreach ($statusLabels as $value => $label)
                                                    <option value="{{ $value }}" @selected($vehicle->status === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </label>

                                        <label class="grid gap-2">
                                            <span class="pm-label">{{ __('garage.fields.manufacturer') }}</span>
                                            <input class="pm-input" name="manufacturer" type="text" value="{{ $vehicle->manufacturer }}" maxlength="100">
                                        </label>

                                        <label class="grid gap-2">
                                            <span class="pm-label">{{ __('garage.fields.model') }}</span>
                                            <input class="pm-input" name="model" type="text" value="{{ $vehicle->model }}" maxlength="100">
                                        </label>

                                        <label class="grid gap-2">
                                            <span class="pm-label">{{ __('garage.fields.year') }}</span>
                                            <input class="pm-input" name="year" type="number" value="{{ $vehicle->year }}" min="1900" max="{{ now()->year + 1 }}" step="1" inputmode="numeric">
                                        </label>

                                        <label class="grid gap-2">
                                            <span class="pm-label">{{ __('garage.fields.identifier') }}</span>
                                            <input class="pm-input" name="identifier" type="text" value="{{ $vehicle->identifier }}" maxlength="100">
                                        </label>

                                        <label class="grid gap-2 sm:col-span-2">
                                            <span class="pm-label">{{ __('garage.fields.notes') }}</span>
                                            <textarea class="pm-input min-h-20 resize-y" name="notes" maxlength="2000">{{ $vehicle->notes }}</textarea>
                                        </label>

                                        <div class="sm:col-span-2">
                                            <button class="pm-ghost-button" type="submit">{{ __('garage.edit.submit') }}</button>
                                        </div>
                                    </form>
                                </details>

                                <form method="POST" action="{{ route('garage.destroy', $vehicle) }}" class="mt-4" onsubmit="return confirm(@js(__('garage.delete.confirm')))" >
                                    @csrf
                                    @method('DELETE')
                                    <button class="pm-danger-button" type="submit">{{ __('garage.delete.submit') }}</button>
                                </form>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="pm-panel p-5 sm:p-6">
                <div class="flex items-start gap-3">
                    <div class="grid size-9 shrink-0 place-items-center rounded-lg border border-pm-border bg-pm-subtle text-sm">↗</div>
                    <div>
                        <h2 class="font-bold text-pm-text">{{ __('garage.next.title') }}</h2>
                        <p class="mt-1 text-sm leading-6 text-pm-text-secondary">{{ __('garage.next.description') }}</p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-layouts::app>
