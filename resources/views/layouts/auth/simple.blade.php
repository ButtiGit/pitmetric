<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-4">
                <a href="{{ route('home') }}" class="flex items-center justify-center" wire:navigate aria-label="PitMetric home">
                    <img src="{{ asset('brand/pitmetric-primary-light.svg') }}" alt="PitMetric" class="h-12 w-auto dark:hidden">
                    <img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="hidden h-12 w-auto dark:block">
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
