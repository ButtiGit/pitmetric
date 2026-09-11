<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080a0d">
    <title>{{ $title ?? 'PitMetric' }}{{ isset($title) ? ' · PitMetric' : '' }}</title>
    <meta name="description" content="{{ $description ?? 'PitMetric aiuta piloti e piccoli team a tracciare configurazioni, componenti, sessioni, manutenzione e costi.' }}">
    <meta property="og:title" content="{{ $title ?? 'PitMetric' }}">
    <meta property="og:description" content="{{ $description ?? 'Know every lap. Track every component.' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="PitMetric">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="alternate icon" href="/favicon.ico" sizes="any">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#080a0d] text-zinc-100 antialiased">
    <div class="min-h-screen bg-[radial-gradient(circle_at_top_right,rgba(225,6,0,0.13),transparent_32rem)]">
        <header class="sticky top-0 z-50 border-b border-white/10 bg-[#080a0d]/90 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-3.5 lg:px-8">
                <a href="{{ route('home') }}" class="group inline-flex items-center" aria-label="PitMetric home">
                    <img src="{{ asset('brand/pitmetric-compact-dark.svg') }}" alt="PitMetric" class="h-8 w-auto sm:hidden">
                    <img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="hidden h-9 w-auto sm:block lg:h-10">
                </a>
                <nav class="hidden items-center gap-7 text-sm text-zinc-300 md:flex" aria-label="Navigazione principale">
                    <a class="transition hover:text-white" href="{{ route('home') }}">Home</a>
                    <a class="transition hover:text-white" href="{{ route('updates.index') }}">Updates</a>
                    <a class="transition hover:text-white" href="{{ route('about') }}">About</a>
                </nav>
                <div class="flex items-center gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-xl bg-[#E10600] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#f01812]">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-xl border border-white/15 px-4 py-2 text-sm font-semibold transition hover:border-white/30 hover:bg-white/[0.04]">Accedi</a>
                    @endauth
                </div>
            </div>
        </header>

        <main>{{ $slot }}</main>

        <footer class="border-t border-white/10 bg-black/10">
            <div class="mx-auto flex max-w-7xl flex-col gap-5 px-5 py-8 text-sm text-zinc-500 sm:flex-row sm:items-center sm:justify-between lg:px-8">
                <div class="flex items-center gap-4">
                    <img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-7 w-auto opacity-80">
                    <p class="hidden lg:block">Built for better decisions between laps.</p>
                </div>
                <div class="flex items-center gap-5">
                    <span>© {{ now()->year }} PitMetric</span>
                    <a href="{{ route('about') }}" class="hover:text-zinc-300">About</a>
                    <a href="{{ route('updates.index') }}" class="hover:text-zinc-300">Updates</a>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
