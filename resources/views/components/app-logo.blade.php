@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand :name="config('app.name', 'PitMetric')" {{ $attributes }}>
        <x-slot name="logo" class="flex h-9 w-14 items-center justify-center overflow-visible">
            <img src="{{ asset('brand/pitmetric-mark-light.svg') }}" alt="" class="h-8 w-auto dark:hidden">
            <img src="{{ asset('brand/pitmetric-mark-dark.svg') }}" alt="" class="hidden h-8 w-auto dark:block">
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="config('app.name', 'PitMetric')" {{ $attributes }}>
        <x-slot name="logo" class="flex h-9 w-14 items-center justify-center overflow-visible">
            <img src="{{ asset('brand/pitmetric-mark-light.svg') }}" alt="" class="h-8 w-auto dark:hidden">
            <img src="{{ asset('brand/pitmetric-mark-dark.svg') }}" alt="" class="hidden h-8 w-auto dark:block">
        </x-slot>
    </flux:brand>
@endif
