@props([
    'eyebrow' => null,
    'title' => null,
    'meta' => null,
])

<div {{ $attributes->class(['pm-mobile-action-dock sm:hidden']) }} data-pm-mobile-action-dock>
    @if ($eyebrow || $title || $meta)
        <div class="pm-mobile-action-context">
            <div class="min-w-0">
                @if ($eyebrow)
                    <p class="pm-mobile-action-eyebrow">{{ $eyebrow }}</p>
                @endif
                @if ($title)
                    <p class="pm-mobile-action-title">{{ $title }}</p>
                @endif
            </div>
            @if ($meta)
                <span class="pm-mobile-action-meta">{{ $meta }}</span>
            @endif
        </div>
    @endif

    <div class="pm-mobile-action-grid">
        {{ $slot }}
    </div>
</div>
