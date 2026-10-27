<x-layouts::app :title="$championship->name">
    @php($positionCount = min(100, max(20, (int) max(array_merge(array_keys($championship->points ?: [1 => 0]), array_filter(array_keys((array) old('points', [])), fn ($key) => ctype_digit((string) $key)))))))
    @php($display = fn (int $amount): string => \App\Services\ChampionshipRecords::display($amount))
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 max-[42rem]:pb-28 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-[1360px] space-y-5" data-pm-championship x-data="{ tab: @js(old('_championship_tab', session('championship_tab', 'standings'))) }">
            <a class="inline-flex text-sm font-bold text-pm-muted hover:text-pm-text" href="{{ route('championships.index') }}">{{ __('championship.title') }}</a>
            <header class="pm-panel flex flex-wrap items-start justify-between gap-5 p-5 sm:p-6">
                <div class="min-w-0"><p class="text-xs font-bold uppercase tracking-widest text-pm-accent">{{ $championship->season }}@if ($championship->category) / {{ $championship->category }}@endif</p><h1 class="mt-2 break-words text-3xl font-black text-pm-text">{{ $championship->name }}</h1><p class="mt-2 text-sm text-pm-muted">{{ __('championship.'.$championship->status) }}</p>@if ($championship->notes)<p class="mt-3 max-w-2xl whitespace-pre-wrap break-words text-sm text-pm-text-secondary">{{ $championship->notes }}</p>@endif</div>
                <div class="flex flex-wrap gap-2">
                    <x-crud-modal id="edit-championship" :title="__('championship.edit')" :trigger="__('championship.edit')" trigger-class="pm-ghost-button">
                        <form method="POST" action="{{ route('championships.update', $championship) }}" class="grid gap-4 sm:grid-cols-2">@csrf @method('PUT')<x-championships.metadata :championship="$championship" /></form>
                    </x-crud-modal>
                    <x-crud-modal id="championship-scoring" :title="__('championship.scoring')" :trigger="__('championship.scoring')" trigger-class="pm-ghost-button" :description="__('championship.scoring_help')">
                        <form method="POST" action="{{ route('championships.scoring', $championship) }}" class="space-y-5" x-data="{ extra: 0 }">@csrf @method('PUT')
                            <div class="grid gap-3 sm:grid-cols-3">
                                @for ($position = 1; $position <= $positionCount; $position++)
                                    <label class="grid gap-2"><span class="pm-label">{{ __('championship.position') }} {{ $position }}</span><input class="pm-input" name="points[{{ $position }}]" inputmode="decimal" value="{{ isset($championship->points[$position]) ? $display((int) $championship->points[$position]) : '' }}"></label>
                                @endfor
                                <template x-for="offset in extra" :key="offset"><label class="grid gap-2"><span class="pm-label">{{ __('championship.position') }} <span x-text="offset + {{ $positionCount }}"></span></span><input class="pm-input" :name="'points[' + (offset + {{ $positionCount }}) + ']'" inputmode="decimal"></label></template>
                            </div>
                            <button class="pm-ghost-button" type="button" @click="extra++" :disabled="extra + {{ $positionCount }} >= 100">{{ __('championship.add_position') }}</button>
                            <div class="grid gap-3 sm:grid-cols-2"><label class="grid gap-2"><span class="pm-label">{{ __('championship.pole_bonus') }}</span><input class="pm-input" name="pole_bonus" inputmode="decimal" value="{{ $display($championship->pole_bonus) }}" required></label><label class="grid gap-2"><span class="pm-label">{{ __('championship.fastest_lap_bonus') }}</span><input class="pm-input" name="fastest_lap_bonus" inputmode="decimal" value="{{ $display($championship->fastest_lap_bonus) }}" required></label></div>
                            <div class="flex justify-end"><button class="pm-race-button" type="submit">{{ __('championship.save') }}</button></div>
                        </form>
                    </x-crud-modal>
                    @include('championships.import')
                    @can('team-write')<form method="POST" action="{{ route('championships.archive', $championship) }}" onsubmit="return confirm(@js(__('championship.archive_confirm')))">@csrf @method('DELETE')<button class="pm-ghost-button" type="submit">{{ __('championship.archive') }}</button></form>@endcan
                </div>
            </header>
            @if (session('status'))<p role="status" class="rounded-xl border border-pm-success/30 bg-pm-success-subtle p-4 text-sm text-pm-success">{{ session('status') }}</p>@endif
            @if ($errors->any() && !old('_pm_dialog'))<p role="alert" class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger">{{ $errors->first() }}</p>@endif
            <nav class="flex flex-wrap gap-2" aria-label="{{ __('championship.title') }}">
                @foreach (['standings','rounds','participants'] as $tab)
                    <button type="button" class="pm-ghost-button" @click="tab = @js($tab)" :aria-pressed="tab === @js($tab)" :class="tab === @js($tab) ? '!border-pm-accent !text-pm-accent' : ''">{{ __('championship.'.$tab) }}@if ($tab !== 'standings') <span class="font-mono text-xs">{{ $championship->{$tab}->count() }}</span>@endif</button>
                @endforeach
            </nav>
            <section x-show="tab === 'standings'" class="space-y-3" data-championship-standings>
                <h2 class="text-xl font-black text-pm-text">{{ __('championship.standings') }}</h2>
                <p class="max-w-4xl text-xs leading-6 text-pm-muted">{{ __('championship.score_rule') }}</p>
                <div class="pm-panel flex flex-wrap gap-x-5 gap-y-2 p-4 text-xs text-pm-text-secondary">
                    @foreach (array_slice($championship->points, 0, 100, true) as $position => $points)<span class="whitespace-nowrap">P{{ $position }} <strong class="ml-1 text-pm-text">{{ $display((int) $points) }}</strong></span>@endforeach
                    <span>{{ __('championship.pole_bonus') }}: {{ $display($championship->pole_bonus) }}</span><span>{{ __('championship.fastest_lap_bonus') }}: {{ $display($championship->fastest_lap_bonus) }}</span>
                </div>
                <ol class="space-y-2">
                    @forelse ($standings as $row)
                        <li class="pm-panel flex items-start gap-3 p-4 sm:gap-5" data-standings-row>
                            <span class="grid size-10 shrink-0 place-items-center rounded-lg border border-pm-border bg-pm-subtle font-mono font-black text-pm-accent">{{ $row['rank'] }}</span>
                            <div class="min-w-0 flex-1"><h3 class="break-words font-bold text-pm-text">{{ $row['participant']->driver->display_name }}</h3><p class="mt-1 break-words text-xs text-pm-muted">{{ filled($row['participant']->entry_number) ? '#'.$row['participant']->entry_number.' ' : '' }}{{ $row['participant']->team_name }}@if ($row['participant']->status === 'withdrawn') / {{ __('championship.withdrawn') }}@endif</p><p class="mt-2 text-xs text-pm-text-secondary">{{ __('championship.starts') }} {{ $row['starts'] }} / {{ __('championship.wins') }} {{ $row['finishes'][1] ?? 0 }}</p></div>
                            <div class="shrink-0 text-right"><strong class="text-xl font-black text-pm-text">{{ $display($row['points']) }}</strong><p class="text-[10px] uppercase tracking-wide text-pm-muted">{{ __('championship.points') }}</p></div>
                        </li>
                    @empty
                        <li class="pm-panel p-6 text-sm text-pm-muted">{{ __('championship.no_participants') }}</li>
                    @endforelse
                </ol>
            </section>
            <section x-show="tab === 'rounds'" class="space-y-3" data-championship-rounds>
                <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-xl font-black text-pm-text">{{ __('championship.rounds') }}</h2>
                    <x-crud-modal id="new-championship-round" :title="__('championship.new_round')" :trigger="__('championship.new_round')">
                        <form method="POST" action="{{ route('championships.rounds.store', $championship) }}" class="grid gap-4 sm:grid-cols-2">@csrf<x-championships.round-form :events="$events" :next-number="$nextRound" /></form>
                    </x-crud-modal>
                </div>
                @forelse ($championship->rounds as $round)
                    <article class="pm-panel flex flex-wrap items-start justify-between gap-4 p-5" data-championship-round>
                        <div class="min-w-0"><p class="text-xs font-bold uppercase text-pm-accent">{{ __('championship.rounds') }} {{ $round->number }} / {{ __('championship.'.$round->status) }}</p><h3 class="mt-2 break-words text-lg font-black text-pm-text">{{ $round->name }}</h3><p class="mt-2 break-words text-sm text-pm-text-secondary">{{ $round->starts_on?->format('d/m/Y') }}@if ($round->ends_on) – {{ $round->ends_on->format('d/m/Y') }}@endif @if ($round->venue) / {{ $round->venue }}@endif</p>@if ($round->event && !$round->event->trashed())<a class="mt-2 inline-flex text-xs font-bold text-pm-accent hover:underline" href="{{ route('events.show', $round->event) }}">{{ $round->event->name }}</a>@endif</div>
                        <div class="flex flex-wrap gap-2">
                            <x-crud-modal id="edit-championship-round-{{ $round->id }}" :title="__('championship.edit_round')" :trigger="__('championship.edit_round')" trigger-class="pm-ghost-button"><form method="POST" action="{{ route('championships.rounds.update', [$championship, $round->id]) }}" class="grid gap-4 sm:grid-cols-2">@csrf @method('PUT')<x-championships.round-form :round="$round" :events="$events" /></form></x-crud-modal>
                            <x-crud-modal id="championship-round-results-{{ $round->id }}" :title="$round->name.' / '.__('championship.results')" :trigger="__('championship.results').' ('.$round->results->count().')'" trigger-class="pm-ghost-button" :permission="null">
                                @can('team-write')
                                    @if ($championship->participants->isNotEmpty())<button class="pm-race-button mb-4" type="button" onclick="document.getElementById('new-championship-result-{{ $round->id }}').showModal()">{{ __('championship.new_result') }}</button>@else<p class="mb-4 text-sm text-pm-muted">{{ __('championship.need_participants') }}</p>@endif
                                @endcan
                                <div class="space-y-2">@forelse ($round->results->sortBy(fn ($result) => $result->position ?? 999) as $result)
                                    <div class="rounded-xl border border-pm-border bg-pm-subtle p-4"><div class="flex flex-wrap justify-between gap-3"><div class="min-w-0"><p class="break-words font-bold text-pm-text">{{ $result->position ? 'P'.$result->position.' ' : '' }}{{ $result->participant->driver->display_name }}</p><p class="mt-1 text-xs text-pm-muted">{{ __('championship.'.$result->status) }} / {{ $display(app(\App\Services\ChampionshipStandings::class)->points($championship, $result)) }} {{ __('championship.points') }}</p>@if ($result->notes)<p class="mt-2 whitespace-pre-wrap break-words text-xs text-pm-text-secondary">{{ $result->notes }}</p>@endif</div><div class="flex flex-wrap gap-2">@can('team-write')<button class="pm-ghost-button" type="button" onclick="document.getElementById('edit-championship-result-{{ $result->id }}').showModal()">{{ __('championship.edit_result') }}</button><form method="POST" action="{{ route('championships.results.remove', [$championship, $round->id, $result->id]) }}" onsubmit="return confirm(@js(__('championship.remove_confirm')))">@csrf @method('DELETE')<button class="pm-ghost-button" type="submit">{{ __('championship.remove_result') }}</button></form>@endcan</div></div></div>
                                @empty<p class="text-sm text-pm-muted">{{ __('championship.no_results') }}</p>@endforelse</div>
                            </x-crud-modal>
                        </div>
                    </article>
                    <x-crud-modal id="new-championship-result-{{ $round->id }}" :title="$round->name.' / '.__('championship.new_result')"><form method="POST" action="{{ route('championships.results.store', [$championship, $round->id]) }}">@csrf<x-championships.result-form :participants="$championship->participants->whereNotIn('id', $round->results->pluck('participant_id'))" /></form></x-crud-modal>
                    @foreach ($round->results as $result)
                        <x-crud-modal id="edit-championship-result-{{ $result->id }}" :title="$round->name.' / '.__('championship.edit_result')"><form method="POST" action="{{ route('championships.results.store', [$championship, $round->id]) }}">@csrf<x-championships.result-form :result="$result" :participants="$championship->participants" /></form></x-crud-modal>
                    @endforeach
                @empty<div class="pm-panel p-6 text-sm text-pm-muted">{{ __('championship.no_rounds') }}</div>@endforelse
            </section>
            <section x-show="tab === 'participants'" class="space-y-3" data-championship-participants>
                <div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-xl font-black text-pm-text">{{ __('championship.participants') }}</h2>
                    <x-crud-modal id="new-championship-participant" :title="__('championship.new_participant')" :trigger="__('championship.new_participant')">
                        <form method="POST" action="{{ route('championships.participants.store', $championship) }}" class="grid gap-4 sm:grid-cols-2" x-data="{ driverId: '', number: '' }">@csrf<input type="hidden" name="_championship_tab" value="participants"><input type="hidden" name="status" value="active">
                            <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ __('championship.existing_driver') }}</span><select class="pm-input" name="driver_id" x-model="driverId" @change="number = $event.target.selectedOptions[0].dataset.number || ''"><option value="">{{ __('championship.new_driver') }}</option>@foreach ($drivers as $driver)<option value="{{ $driver->id }}" data-number="{{ $driver->racing_number }}">{{ $driver->display_name }}</option>@endforeach</select></label>
                            <label class="grid gap-2" x-show="driverId === ''"><span class="pm-label">{{ __('championship.driver_name') }}</span><input class="pm-input" name="name" maxlength="120" :required="driverId === ''"></label>
                            <label class="grid gap-2" x-show="driverId === ''"><span class="pm-label">{{ __('championship.licence') }}</span><input class="pm-input" name="licence" maxlength="80"></label>
                            <label class="grid gap-2"><span class="pm-label">{{ __('championship.entry_number') }}</span><input class="pm-input" name="number" maxlength="20" x-model="number"></label>
                            <label class="grid gap-2"><span class="pm-label">{{ __('championship.team_name') }}</span><input class="pm-input" name="team_name" maxlength="120"></label>
                            <div class="flex justify-end sm:col-span-2"><button class="pm-race-button" type="submit">{{ __('championship.save') }}</button></div>
                        </form>
                    </x-crud-modal>
                </div>
                @forelse ($championship->participants->sortBy(fn ($participant) => $participant->driver->display_name) as $participant)
                    <article class="pm-panel flex flex-wrap items-center justify-between gap-4 p-4" data-championship-participant>
                        <div class="min-w-0"><h3 class="break-words font-bold text-pm-text">{{ $participant->driver->display_name }}</h3><p class="mt-1 break-words text-xs text-pm-muted">{{ filled($participant->entry_number) ? '#'.$participant->entry_number.' / ' : '' }}{{ $participant->team_name }} / {{ __('championship.'.($participant->status === 'active' ? 'enrolled' : 'withdrawn')) }}</p></div>
                        <x-crud-modal id="edit-championship-participant-{{ $participant->id }}" :title="$participant->driver->display_name.' / '.__('championship.edit_participant')" :trigger="__('championship.edit_participant')" trigger-class="pm-ghost-button">
                            <form method="POST" action="{{ route('championships.participants.update', [$championship, $participant->id]) }}" class="grid gap-4 sm:grid-cols-2">@csrf @method('PUT')<input type="hidden" name="_championship_tab" value="participants">
                                <label class="grid gap-2"><span class="pm-label">{{ __('championship.entry_number') }}</span><input class="pm-input" name="number" maxlength="20" value="{{ $participant->entry_number }}"></label><label class="grid gap-2"><span class="pm-label">{{ __('championship.team_name') }}</span><input class="pm-input" name="team_name" maxlength="120" value="{{ $participant->team_name }}"></label><label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ __('championship.status') }}</span><select class="pm-input" name="status"><option value="active" @selected($participant->status === 'active')>{{ __('championship.enrolled') }}</option><option value="withdrawn" @selected($participant->status === 'withdrawn')>{{ __('championship.withdrawn') }}</option></select></label><div class="flex justify-end sm:col-span-2"><button class="pm-race-button" type="submit">{{ __('championship.save') }}</button></div>
                            </form>
                        </x-crud-modal>
                    </article>
                @empty<div class="pm-panel p-6 text-sm text-pm-muted">{{ __('championship.no_participants') }}</div>@endforelse
            </section>
        </div>
    </div>
</x-layouts::app>
