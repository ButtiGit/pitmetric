<x-layouts::app :title="__('contracts.title').' #'.$contract->id">
    <div class="pitmetric-app pm-mobile-page-with-dock min-h-full bg-pm-page p-4 sm:p-6"><div class="mx-auto max-w-5xl space-y-5 text-pm-text [overflow-wrap:anywhere]">
        <div class="flex flex-wrap gap-3"><a class="pm-ghost-button" href="{{ route('contracts.index') }}" wire:navigate>{{ __('prints.back') }}</a><a class="pm-ghost-button" href="{{ route('prints.contract', $contract) }}" target="_blank" rel="noopener">{{ __('contracts.print') }}</a></div>
        <x-contracts.feedback />
        <section class="pm-panel space-y-4 p-4 sm:p-6">
            <h1 class="text-2xl font-black">{{ $contract->driver?->display_name }} · {{ $contract->vehicle?->name }}</h1>
            <p>#{{ $contract->id }} · {{ $contract->season }} · {{ $contract->championship?->name ?? __('contracts.no_championship') }}</p>
            <p>{{ $contract->starts_on->format('d/m/Y') }} · {{ $contract->ends_on->format('d/m/Y') }} · {{ __('contracts.states.'.$contract->status) }}</p>
            <p>{{ __('contracts.initial') }}: {{ $contract->initial_races }}</p>
            <x-contracts.balance :$total :$consumed :$remaining />
            <p class="text-sm text-pm-muted">{{ __('contracts.rule') }}</p>
            @if ($contract->notes)<p class="whitespace-pre-wrap">{{ $contract->notes }}</p>@endif
            @can('update', $contract)
                <div class="flex flex-wrap gap-3">
                    <x-crud-modal id="edit-contract" :title="__('contracts.edit')" :trigger="__('contracts.edit')" trigger-class="pm-ghost-button">
                        <form method="POST" action="{{ route('contracts.update', $contract) }}" class="grid gap-4 sm:grid-cols-2">@csrf @method('PUT')<input type="hidden" name="revision" value="{{ $contract->revision }}">
                            <p class="text-sm text-pm-muted sm:col-span-2">{{ __('contracts.terms') }}</p>
                            <label class="grid gap-2"><span class="pm-label">{{ __('contracts.start') }}</span><input class="pm-input" name="starts_on" type="date" value="{{ $contract->starts_on->toDateString() }}" required></label>
                            <label class="grid gap-2"><span class="pm-label">{{ __('contracts.end') }}</span><input class="pm-input" name="ends_on" type="date" value="{{ $contract->ends_on->toDateString() }}" required></label>
                            <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ __('prints.status') }}</span><select name="status" class="pm-input" required>@foreach (['active','suspended','completed'] as $state)<option value="{{ $state }}" @selected($contract->status === $state)>{{ __('contracts.states.'.$state) }}</option>@endforeach</select></label>
                            <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ __('prints.notes') }}</span><textarea class="pm-input" name="notes" maxlength="2000">{{ $contract->notes }}</textarea></label>
                            <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ __('contracts.reason') }}</span><textarea class="pm-input" name="reason" maxlength="1000" required></textarea></label>
                            <button type="submit" class="pm-race-button sm:col-span-2">{{ __('Save') }}</button>
                        </form>
                    </x-crud-modal>
                    @if ($contract->status !== 'completed')
                        <x-crud-modal id="extend-contract" :title="__('contracts.extend')" :trigger="__('contracts.extend')" trigger-class="pm-ghost-button">
                            <form method="POST" action="{{ route('contracts.extend', $contract) }}" class="grid gap-4">@csrf<input type="hidden" name="revision" value="{{ $contract->revision }}">
                                <label class="grid gap-2"><span class="pm-label">{{ __('contracts.added') }}</span><input class="pm-input" type="number" name="added_races" min="1" max="10000" required></label>
                                <label class="grid gap-2"><span class="pm-label">{{ __('contracts.end') }}</span><input class="pm-input" type="date" name="ends_on" value="{{ $contract->ends_on->toDateString() }}" min="{{ $contract->ends_on->toDateString() }}"></label>
                                <label class="grid gap-2"><span class="pm-label">{{ __('contracts.reason') }}</span><textarea class="pm-input" name="reason" maxlength="1000" required></textarea></label>
                                <button type="submit" class="pm-race-button">{{ __('Save') }}</button>
                            </form>
                        </x-crud-modal>
                    @endif
                    @if ($contract->status === 'active')
                        <x-crud-modal id="attach-contract-entry" :title="__('contracts.attach')" :trigger="__('contracts.attach')" trigger-class="pm-ghost-button">
                            <form method="POST" action="{{ route('contracts.participations.store', $contract) }}" class="grid gap-4">@csrf
                                <p class="text-sm text-pm-muted">{{ __('contracts.association_help') }}</p>
                                <label class="grid gap-2"><span class="pm-label">{{ __('prints.event') }}</span><select class="pm-input" name="event_entry_id" required><option value="">{{ __('contracts.choose') }}</option>@foreach ($eligibleEntries as $entry)<option value="{{ $entry->id }}">{{ $entry->raceEvent?->name }} · {{ $entry->vehicle?->name }} #{{ $entry->id }}</option>@endforeach</select></label>
                                <label class="grid gap-2"><span class="pm-label">{{ __('contracts.round') }}</span><select class="pm-input" name="round_id"><option value="">{{ __('contracts.optional') }}</option>@foreach ($rounds as $round)<option value="{{ $round->id }}">{{ $round->name }} · {{ __('prints.event') }} #{{ $round->event_id }}</option>@endforeach</select></label>
                                <label class="grid gap-2"><span class="pm-label">{{ __('contracts.notes') }}</span><textarea class="pm-input" name="notes" maxlength="2000"></textarea></label>
                                <label class="grid gap-2"><span class="pm-label">{{ __('contracts.reason') }}</span><input class="pm-input" name="reason" value="{{ __('contracts.associated') }}" maxlength="1000" required></label>
                                <button type="submit" class="pm-race-button" @disabled($eligibleEntries->isEmpty())>{{ __('Save') }}</button>
                            </form>
                        </x-crud-modal>
                    @endif
                </div>
            @endcan
        </section>
        <x-contracts.records :$contract :editable="true" />
        <section class="pm-panel space-y-3 p-4"><h2 class="text-lg font-black">{{ __('contracts.document') }}</h2>
            @can('update', $contract)
                <x-crud-modal id="contract-document" :title="__('contracts.upload')" :trigger="__('contracts.upload')" trigger-class="pm-ghost-button">
                    <form method="POST" action="{{ route('control-center.documents.store') }}" enctype="multipart/form-data" class="grid gap-4">@csrf
                        <input type="hidden" name="attachable" value="season_contract:{{ $contract->id }}">
                        <p class="text-sm text-pm-muted">{{ __('contracts.document_help') }}</p>
                        <label class="grid gap-2"><span class="pm-label">{{ __('contracts.document') }}</span><input class="pm-input" name="document" type="file" accept="application/pdf,image/jpeg,image/png,image/webp" required></label>
                        <label class="grid gap-2"><span class="pm-label">{{ __('prints.notes') }}</span><textarea name="notes" class="pm-input" maxlength="2000"></textarea></label>
                        <button class="pm-race-button" type="submit">{{ __('contracts.upload') }}</button>
                    </form>
                </x-crud-modal>
            @endcan
            @forelse ($documents as $document)
                <p><a class="text-pm-accent underline" href="{{ route('control-center.documents.download', $document) }}">{{ $document->name }}</a> · {{ $document->uploader?->name }} · {{ $document->created_at->copy()->timezone('Europe/Rome')->format('d/m/Y H:i') }}</p>
            @empty
                <p class="text-sm text-pm-muted">{{ __('contracts.empty_history') }}</p>
            @endforelse
        </section>
    </div></div>
</x-layouts::app>
