<x-layouts::app :title="__('contracts.title')">
    <div class="pitmetric-app pm-mobile-page-with-dock min-h-full bg-pm-page p-4 sm:p-6"><div class="mx-auto max-w-6xl space-y-5">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <x-pitmetric.page-header :title="__('contracts.title')" :description="__('contracts.subtitle')" />
            @can('create', \App\Models\SeasonContract::class)
                <x-crud-modal id="create-contract" :title="__('contracts.new')" :trigger="__('contracts.new')">
                    <form action="{{ route('contracts.store') }}" method="POST" class="grid gap-4 sm:grid-cols-2">@csrf
                        <label class="grid gap-2"><span class="pm-label">{{ __('prints.driver') }}</span><select name="driver_id" class="pm-input" required><option value="">{{ __('contracts.choose') }}</option>@foreach ($drivers->whereNull('deleted_at')->where('status', 'active') as $driver)<option value="{{ $driver->id }}" @selected((string) old('driver_id') === (string) $driver->id)>{{ $driver->display_name }}</option>@endforeach</select></label>
                        <label class="grid gap-2"><span class="pm-label">{{ __('contracts.primary') }}</span><select name="vehicle_id" class="pm-input" required><option value="">{{ __('contracts.choose') }}</option>@foreach ($vehicles->whereNull('deleted_at')->where('status', 'active') as $vehicle)<option value="{{ $vehicle->id }}" @selected((string) old('vehicle_id') === (string) $vehicle->id)>{{ $vehicle->name }}</option>@endforeach</select></label>
                        <label class="grid gap-2"><span class="pm-label">{{ __('contracts.season') }}</span><input name="season" class="pm-input" value="{{ old('season', now()->format('Y')) }}" maxlength="40" required></label>
                        <label class="grid gap-2"><span class="pm-label">{{ __('contracts.initial') }}</span><input name="initial_races" class="pm-input" type="number" min="1" max="10000" value="{{ old('initial_races', 5) }}" required></label>
                        <label class="grid gap-2"><span class="pm-label">{{ __('contracts.start') }}</span><input name="starts_on" type="date" class="pm-input" value="{{ old('starts_on', now()->startOfYear()->toDateString()) }}" required></label>
                        <label class="grid gap-2"><span class="pm-label">{{ __('contracts.end') }}</span><input name="ends_on" type="date" class="pm-input" value="{{ old('ends_on', now()->endOfYear()->toDateString()) }}" required></label>
                        <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ __('championship.title') }}</span><select class="pm-input" name="championship_id"><option value="">{{ __('contracts.no_championship') }}</option>@foreach ($championships as $championship)<option value="{{ $championship->id }}" @selected((string) old('championship_id') === (string) $championship->id)>{{ $championship->name }} · {{ $championship->season }}</option>@endforeach</select></label>
                        <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ __('prints.notes') }}</span><textarea name="notes" class="pm-input" maxlength="2000">{{ old('notes') }}</textarea></label>
                        <button class="pm-race-button sm:col-span-2" type="submit">{{ __('Save') }}</button>
                    </form>
                </x-crud-modal>
            @endcan
        </div>
        <x-contracts.feedback />
        <p class="rounded-xl border border-pm-border p-4 text-sm text-pm-text-secondary">{{ __('contracts.rule') }}</p>
        <form method="GET" action="{{ route('contracts.index') }}" class="grid gap-3 sm:grid-cols-4">
            <label class="grid gap-2"><span class="pm-label">{{ __('prints.status') }}</span><select name="status" class="pm-input"><option value="">{{ __('contracts.all') }}</option>@foreach (['active','suspended','completed'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ __('contracts.states.'.$status) }}</option>@endforeach</select></label>
            <label class="grid gap-2"><span class="pm-label">{{ __('prints.driver') }}</span><select name="driver_id" class="pm-input"><option value="">{{ __('contracts.all') }}</option>@foreach ($drivers as $driver)<option value="{{ $driver->id }}" @selected(($filters['driver_id'] ?? '') == $driver->id)>{{ $driver->display_name }}</option>@endforeach</select></label>
            <label class="grid gap-2"><span class="pm-label">{{ __('prints.vehicle') }}</span><select name="vehicle_id" class="pm-input"><option value="">{{ __('contracts.all') }}</option>@foreach ($vehicles as $vehicle)<option value="{{ $vehicle->id }}" @selected(($filters['vehicle_id'] ?? '') == $vehicle->id)>{{ $vehicle->name }}</option>@endforeach</select></label>
            <button type="submit" class="pm-ghost-button self-end">{{ __('tasks.filter') }}</button>
        </form>
        <div class="grid gap-4 lg:grid-cols-2">
            @forelse ($contracts as $contract)
                @php($total = $contract->initial_races + (int) $contract->extra_races)
                @php($remaining = max(0, $total - (int) $contract->consumed))
                <article class="pm-panel min-w-0 space-y-4 p-4 [overflow-wrap:anywhere]" data-contract-id="{{ $contract->id }}">
                    <div><h2 class="text-lg font-black text-pm-text">{{ $contract->driver?->display_name }} · {{ $contract->vehicle?->name }}</h2><p class="mt-1 text-sm text-pm-muted">#{{ $contract->id }} · {{ $contract->season }} · {{ $contract->championship?->name ?? __('contracts.no_championship') }}</p></div>
                    <p class="text-sm text-pm-text-secondary">{{ $contract->starts_on->format('d/m/Y') }} · {{ $contract->ends_on->format('d/m/Y') }} · {{ __('contracts.states.'.$contract->status) }}</p>
                    @if ($contract->ends_on->toDateString() < now()->timezone('Europe/Rome')->toDateString())<p class="text-sm font-bold text-pm-warning">{{ __('contracts.expired') }}</p>@endif
                    <x-contracts.balance :$total :consumed="(int) $contract->consumed" :$remaining />
                    <a class="pm-ghost-button" href="{{ route('contracts.show', $contract) }}" wire:navigate>{{ __('contracts.open') }}</a>
                </article>
            @empty
                <p class="pm-panel p-5 text-pm-muted">{{ __('contracts.empty') }}</p>
            @endforelse
        </div>
        {{ $contracts->links() }}
    </div></div>
</x-layouts::app>
