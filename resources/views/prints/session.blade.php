<x-prints.layout :$kind :$workspace :$reference :$generatedAt :$backUrl>
    @if ($vehicle?->category === 'kart')<x-slot:actions><a href="{{ route('prints.tyres', $session) }}">{{ __('prints.titles.tyres') }}</a></x-slot:actions>
    @endif
    <x-prints.session-context :$session />
    <section><h2>{{ __('prints.summary') }}</h2><dl class="print-facts"><div><dt>{{ __('prints.best_lap') }}</dt><dd>{{ $bestTime }}@if ($bestLap) · {{ __('prints.lap') }} {{ $bestLap->lap_number }} @endif</dd></div><div><dt>{{ __('prints.recorded_laps') }}</dt><dd>{{ $session->completed_laps ?? '—' }} · {{ __('prints.timed_laps') }}: {{ $session->timingLaps->count() }}</dd></div></dl></section>
    <section><h2>{{ __('prints.used_setup') }}: {{ $session->setupSnapshot?->name ?? '—' }}</h2><p class="print-caption">{{ __('prints.snapshot_help') }}</p>
        @if ($session->setupSnapshot)<x-prints.setup-values :rows="$setupRows" />
    @else<p>{{ __('prints.no_snapshot') }}</p>
    @endif
    </section>
    @if ($vehicle?->category === 'kart')<section><h2>{{ __('prints.titles.tyres') }}</h2><x-prints.tyre-readings :$session /></section>
    @endif
    <section><h2>{{ __('prints.weather') }}</h2>
        <x-prints.weather-cards :cards="$weatherCards" />
    </section>
    <section><h2>{{ __('prints.lap_times') }}</h2>
        <table><thead><tr><th scope="col">{{ __('prints.lap') }}</th><th scope="col">{{ __('prints.time') }}</th><th scope="col">{{ __('prints.validity') }}</th><th scope="col">{{ __('prints.source') }}</th></tr></thead>
            <tbody>
    @forelse ($session->timingLaps as $lap)<tr data-print-lap="{{ $lap->id }}"><td>{{ $lap->lap_number }}</td><td>{{ $documents->lapTime($lap->lap_time_ms) }}@if ($bestLap?->id === $lap->id) · {{ __('prints.best') }}@endif</td><td>{{ __('prints.'.($lap->is_valid ? 'valid' : 'invalid')) }}</td><td>{{ $lap->source }}</td></tr>
    @empty<tr><td colspan="4">{{ __('prints.no_laps') }}</td></tr>
    @endforelse</tbody>
        </table>
    </section>
    @if ($session->notes)<section><h2>{{ __('prints.notes') }}</h2><p class="print-note">{{ $session->notes }}</p></section>
    @endif
</x-prints.layout>
