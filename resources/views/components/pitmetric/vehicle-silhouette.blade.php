@props(['type' => 'other'])

<svg {{ $attributes->merge(['class' => 'h-auto w-full']) }} viewBox="0 0 240 96" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    @switch($type)
        @case('kart')
            <path d="M30 64h21l8-17h48l10 9h35l10 8h43l6 8H27l3-8Z" fill="currentColor"/>
            <path d="M92 34h23l11 13H92V34Z" fill="currentColor" opacity=".82"/>
            <circle cx="62" cy="72" r="12" fill="currentColor"/>
            <circle cx="178" cy="72" r="12" fill="currentColor"/>
            @break
        @case('formula')
            <path d="M18 67h35l15-10 35-4 18-22h18l15 22 50 8 21 6v8H18v-8Z" fill="currentColor"/>
            <path d="M102 52 116 35h25l11 18-50-1Z" fill="currentColor" opacity=".84"/>
            <rect x="21" y="58" width="31" height="5" rx="2.5" fill="currentColor"/>
            <rect x="192" y="54" width="29" height="6" rx="3" fill="currentColor"/>
            <circle cx="64" cy="73" r="13" fill="currentColor"/>
            <circle cx="185" cy="73" r="13" fill="currentColor"/>
            @break
        @case('prototype')
            <path d="M23 69 45 55l39-8 25-18h43l28 22 37 8 12 10v7H20l3-7Z" fill="currentColor"/>
            <path d="m95 48 20-14h31l24 18-75-4Z" fill="currentColor" opacity=".78"/>
            <circle cx="69" cy="74" r="13" fill="currentColor"/>
            <circle cx="183" cy="74" r="13" fill="currentColor"/>
            @break
        @case('hypercar')
            <path d="M18 70 42 57l41-8 24-17h41l27 17 45 10 9 11v7H18v-7Z" fill="currentColor"/>
            <path d="m90 49 23-14h31l23 16-77-2Z" fill="currentColor" opacity=".72"/>
            <circle cx="68" cy="75" r="13" fill="currentColor"/>
            <circle cx="183" cy="75" r="13" fill="currentColor"/>
            @break
        @case('gt')
            <path d="M19 68 37 54l45-8 25-17h42l32 19 42 9 9 11v8H17l2-8Z" fill="currentColor"/>
            <path d="m95 47 18-14h31l25 17-74-3Z" fill="currentColor" opacity=".72"/>
            <path d="M184 46h33v6h-34l1-6Z" fill="currentColor" opacity=".85"/>
            <circle cx="67" cy="74" r="13" fill="currentColor"/>
            <circle cx="185" cy="74" r="13" fill="currentColor"/>
            @break
        @case('touring')
            <path d="M17 68 34 48l48-7 18-14h49l30 18 43 8 11 15v8H17v-8Z" fill="currentColor"/>
            <path d="M91 43 106 31h36l24 15-75-3Z" fill="currentColor" opacity=".72"/>
            <circle cx="65" cy="74" r="13" fill="currentColor"/>
            <circle cx="186" cy="74" r="13" fill="currentColor"/>
            @break
        @case('rally')
            <path d="M17 69 34 50l42-8 19-15h53l31 18 42 10 12 14v8H17v-8Z" fill="currentColor"/>
            <path d="M88 43 104 31h37l25 15-78-3Z" fill="currentColor" opacity=".72"/>
            <path d="M31 44h22l9 5H31v-5Z" fill="currentColor" opacity=".8"/>
            <circle cx="64" cy="75" r="14" fill="currentColor"/>
            <circle cx="186" cy="75" r="14" fill="currentColor"/>
            @break
        @case('drift')
            <path d="M17 69 36 52l43-8 26-17h43l34 21 39 8 12 13v8H17v-8Z" fill="currentColor"/>
            <path d="M91 45 110 31h33l24 17-76-3Z" fill="currentColor" opacity=".72"/>
            <path d="M185 42h31v6h-32l1-6Z" fill="currentColor" opacity=".85"/>
            <circle cx="65" cy="75" r="13" fill="currentColor"/>
            <circle cx="187" cy="75" r="13" fill="currentColor"/>
            @break
        @case('road_car')
        @case('car')
            <path d="M18 69 35 53l43-8 24-16h48l31 19 42 9 10 12v8H18v-8Z" fill="currentColor"/>
            <path d="M91 46 108 33h35l23 16-75-3Z" fill="currentColor" opacity=".72"/>
            <circle cx="66" cy="75" r="13" fill="currentColor"/>
            <circle cx="185" cy="75" r="13" fill="currentColor"/>
            @break
        @case('motorcycle')
            <circle cx="66" cy="72" r="19" stroke="currentColor" stroke-width="9"/>
            <circle cx="183" cy="72" r="19" stroke="currentColor" stroke-width="9"/>
            <path d="m71 68 34-29 28 5 18 24h-31l-18-16-16 18-15-2Z" fill="currentColor"/>
            <path d="m132 43 20-18 15 5-12 18-23-5Z" fill="currentColor" opacity=".78"/>
            @break
        @case('quad')
            <circle cx="58" cy="73" r="17" fill="currentColor"/>
            <circle cx="184" cy="73" r="17" fill="currentColor"/>
            <path d="M38 66h40l18-22h49l16 17h42l11 12H35l3-7Z" fill="currentColor"/>
            <path d="m112 43 12-16h13l7 16h-32Z" fill="currentColor" opacity=".76"/>
            @break
        @case('buggy')
            <circle cx="58" cy="73" r="18" fill="currentColor"/>
            <circle cx="186" cy="73" r="18" fill="currentColor"/>
            <path d="M32 65h39l23-28h51l27 28h39l8 10H28l4-10Z" fill="currentColor"/>
            <path d="m101 39 13-13h22l15 13h-50Z" fill="currentColor" opacity=".72"/>
            @break
        @case('offroad')
            <circle cx="58" cy="72" r="19" fill="currentColor"/>
            <circle cx="186" cy="72" r="19" fill="currentColor"/>
            <path d="M30 64h44l15-30h58l22 23h42l10 15H27l3-8Z" fill="currentColor"/>
            <path d="M101 39V23h39l16 19-55-3Z" fill="currentColor" opacity=".74"/>
            @break
        @case('truck')
            <circle cx="65" cy="74" r="14" fill="currentColor"/>
            <circle cx="187" cy="74" r="14" fill="currentColor"/>
            <path d="M24 35h103v39H24V35Zm106 14h47l26 16h18v9h-91V49Z" fill="currentColor"/>
            <path d="M140 53h31l15 10h-46V53Z" fill="currentColor" opacity=".68"/>
            @break
        @case('boat')
            <path d="M21 58h197l-20 19H52L21 58Z" fill="currentColor"/>
            <path d="M92 31h54l30 27H70l22-27Z" fill="currentColor" opacity=".82"/>
            <path d="M115 13h7v45h-7V13Z" fill="currentColor" opacity=".65"/>
            @break
        @default
            <path d="M18 69 35 53l43-8 24-16h48l31 19 42 9 10 12v8H18v-8Z" fill="currentColor"/>
            <path d="M91 46 108 33h35l23 16-75-3Z" fill="currentColor" opacity=".72"/>
            <circle cx="66" cy="75" r="13" fill="currentColor"/>
            <circle cx="185" cy="75" r="13" fill="currentColor"/>
    @endswitch
</svg>
