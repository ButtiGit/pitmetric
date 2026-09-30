@props(['title' => null, 'demo' => false])

@php
    $it = app()->getLocale() === 'it';
@endphp

<div class="pm-workspace-bar" data-pm-workspace-bar>
    <div class="pm-workspace-location">
        <span class="pm-workspace-symbol" aria-hidden="true"><flux:icon.squares-2x2 class="size-4" /></span>
        <span>{{ $demo ? 'Race Team Demo' : 'Workspace' }}</span>
        <span class="pm-workspace-divider" aria-hidden="true">/</span>
        <strong>{{ $title ?? __('Dashboard') }}</strong>
    </div>
    <div class="pm-workspace-tools">
        @if ($demo)
            <span class="pm-workspace-demo">{{ $it ? 'Demo · sola lettura' : 'Demo · read only' }}</span>
            <a href="{{ route('register') }}" class="pm-ghost-button">{{ $it ? 'Crea il tuo account' : 'Create your account' }}<flux:icon.arrow-up-right class="size-4" /></a>
        @else
            <a href="{{ route('pit-mode.index') }}" class="pm-ghost-button" wire:navigate><flux:icon.bolt class="size-4" />Pit Mode</a>
            {{ $slot }}
        @endif
    </div>
</div>
