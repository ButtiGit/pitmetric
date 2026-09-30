@props(['section' => 'dashboard'])

@php
    $it = app()->getLocale() === 'it';
    $items = [
        ['dashboard', 'home', 'Home'],
        ['events', 'calendar-days', 'Weekend'],
        ['sessions', 'flag', $it ? 'Sessioni' : 'Sessions'],
        ['garage', 'truck', 'Garage'],
        ['maintenance', 'wrench-screwdriver', 'Service'],
    ];
@endphp

<nav class="pm-mobile-bottom-nav pm-demo-bottom-nav lg:hidden" aria-label="{{ $it ? 'Navigazione demo' : 'Demo navigation' }}" data-pm-demo-navigation>
    @foreach ($items as [$key, $icon, $label])
        <a href="{{ $key === 'dashboard' ? route('demo.public') : route('demo.manager', ['section' => $key]) }}"
            class="pm-mobile-nav-item {{ $section === $key ? 'is-current' : '' }}"
            @if ($section === $key) aria-current="page" @endif>
            <flux:icon :name="$icon" class="size-5" />
            <span>{{ $label }}</span>
        </a>
    @endforeach
</nav>
