@props(['cards'])
        @forelse ($cards as $card)
            <h3>{{ __('prints.weather_sources.'.$card['kind']) }}</h3>
            <p class="print-caption">{{ __('prints.weather_help.'.$card['kind']) }}</p>
            @if ($card['mismatch'])<p class="print-warning">{{ __('prints.history_mismatch') }}</p>
    @endif
            <p class="print-caption">{{ __('prints.reference_time') }}: {{ $card['valid_at'] ? \Illuminate\Support\Carbon::parse($card['valid_at'])->timezone('Europe/Rome')->format('d/m/Y H:i') : '—' }}@if ($card['captured_at']) · {{ __('prints.saved') }}: {{ \Illuminate\Support\Carbon::parse($card['captured_at'])->timezone('Europe/Rome')->format('d/m/Y H:i') }}@endif</p>
            <x-prints.setup-values :rows="$card['rows']" />
            @if ($card['notes'])<p class="print-note">{{ $card['notes'] }}</p>
    @endif
        @empty<p>{{ __('prints.no_weather') }}</p>
    @endforelse
