@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'imageAlt' => null,
    'ogType' => 'website',
])

@php
    $seoTitle = isset($title) ? $title.' · PitMetric' : 'PitMetric';
    $seoDescription = $description ?? __('pitmetric.home.intro');
    $seoImage = $image ?? 'https://images.unsplash.com/photo-1656978766399-1e117a291918?auto=format&fit=crop&fm=jpg&q=88&w=1600';

    if (! str_starts_with($seoImage, 'http://') && ! str_starts_with($seoImage, 'https://')) {
        $seoImage = url('/'.ltrim($seoImage, '/'));
    }

    $seoImageAlt = $imageAlt ?? (app()->getLocale() === 'it'
        ? 'PitMetric, software gestionale per motorsport'
        : 'PitMetric, motorsport management software');
    $openGraphLocale = app()->getLocale() === 'it' ? 'it_IT' : 'en_US';

    $routeName = request()->route()?->getName();
    $routeParameters = request()->route()?->parameters() ?? [];
    unset($routeParameters['locale']);

    $localizedRouteMap = [
        'home' => 'localized.home',
        'localized.home' => 'localized.home',
        'about' => 'localized.about',
        'localized.about' => 'localized.about',
        'app' => 'localized.app',
        'localized.app' => 'localized.app',
        'cookies' => 'localized.cookies',
        'localized.cookies' => 'localized.cookies',
        'updates.index' => 'localized.updates.index',
        'localized.updates.index' => 'localized.updates.index',
        'updates.show' => 'localized.updates.show',
        'localized.updates.show' => 'localized.updates.show',
    ];

    $localizedRouteName = $localizedRouteMap[$routeName] ?? null;

    $seoQueryParameters = [];

    if ($localizedRouteName === 'localized.updates.index' && request()->integer('page') > 1) {
        $seoQueryParameters['page'] = request()->integer('page');
    }

    $localizedUrl = function (string $locale) use ($localizedRouteName, $routeParameters, $seoQueryParameters): ?string {
        if ($localizedRouteName === null) {
            return null;
        }

        return route($localizedRouteName, array_merge(
            ['locale' => $locale],
            $routeParameters,
            $seoQueryParameters,
        ));
    };

    $canonicalUrl = $localizedUrl(app()->getLocale()) ?? url()->current();
    $englishUrl = $localizedUrl('en');
    $italianUrl = $localizedUrl('it');

    $publicHomeUrl = route('localized.home', ['locale' => app()->getLocale()]);
    $publicUpdatesUrl = route('localized.updates.index', ['locale' => app()->getLocale()]);
    $publicAppUrl = route('localized.app', ['locale' => app()->getLocale()]);
    $publicAboutUrl = route('localized.about', ['locale' => app()->getLocale()]);
    $publicCookiesUrl = route('localized.cookies', ['locale' => app()->getLocale()]);

    $languageRedirects = [];

    foreach (['en' => $englishUrl, 'it' => $italianUrl] as $localeCode => $languageUrl) {
        if ($languageUrl === null) {
            continue;
        }

        $redirectPath = parse_url($languageUrl, PHP_URL_PATH) ?: '/';

        if (request()->getQueryString()) {
            $redirectPath .= '?'.request()->getQueryString();
        }

        $languageRedirects[$localeCode] = $redirectPath;
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080a0d">

    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">

    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:site_name" content="PitMetric">
    <meta property="og:locale" content="{{ $openGraphLocale }}">
    <meta property="og:locale:alternate" content="{{ app()->getLocale() === 'it' ? 'en_US' : 'it_IT' }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:image:alt" content="{{ $seoImageAlt }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    <meta name="twitter:image:alt" content="{{ $seoImageAlt }}">

    <link rel="canonical" href="{{ $canonicalUrl }}">
    @if ($englishUrl && $italianUrl)
        <link rel="alternate" hreflang="en" href="{{ $englishUrl }}">
        <link rel="alternate" hreflang="it" href="{{ $italianUrl }}">
        <link rel="alternate" hreflang="x-default" href="{{ $englishUrl }}">
    @endif
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @fonts
    @vite('resources/js/public.js')
</head>
<body class="pm-public-site min-h-screen bg-[#07090c] text-zinc-100 antialiased selection:bg-[#E10600] selection:text-white">
    <div data-pm-scroll-progress class="pm-scroll-progress" aria-hidden="true"></div>

    <div class="min-h-screen">
        <header data-pm-public-header class="sticky top-0 z-50 border-b border-white/10 bg-[#07090c]/90 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-3 lg:px-8">
                <a href="{{ $publicHomeUrl }}" class="inline-flex items-center" aria-label="PitMetric home">
                    <img src="{{ asset('brand/pitmetric-compact-dark.svg') }}" alt="PitMetric" class="h-9 w-auto sm:hidden">
                    <img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="hidden h-10 w-auto sm:block">
                </a>
                <nav class="hidden items-center gap-8 text-sm font-semibold md:flex" aria-label="Main navigation">
                    <a class="border-b-2 pb-1 transition {{ request()->routeIs('home', 'localized.home') ? 'border-[#E10600] text-white' : 'border-transparent text-zinc-400 hover:text-white' }}" href="{{ $publicHomeUrl }}">{{ __('pitmetric.nav.home') }}</a>
                    <a class="border-b-2 pb-1 transition {{ request()->routeIs('updates.*', 'localized.updates.*') ? 'border-[#E10600] text-white' : 'border-transparent text-zinc-400 hover:text-white' }}" href="{{ $publicUpdatesUrl }}">{{ __('pitmetric.nav.updates') }}</a>
                    <a class="border-b-2 pb-1 transition {{ request()->routeIs('app', 'localized.app') ? 'border-[#E10600] text-white' : 'border-transparent text-zinc-400 hover:text-white' }}" href="{{ $publicAppUrl }}">App</a>
                    <a class="border-b-2 pb-1 transition {{ request()->routeIs('about', 'localized.about') ? 'border-[#E10600] text-white' : 'border-transparent text-zinc-400 hover:text-white' }}" href="{{ $publicAboutUrl }}">{{ __('pitmetric.nav.about') }}</a>
                </nav>
                <div class="flex items-center gap-2">
                    <button type="button" data-language-open class="pm-public-language inline-flex items-center" aria-label="{{ __('pitmetric.language.title') }}">
                        <span class="pm-public-language__label" aria-hidden="true">LANG</span>
                        <span>{{ strtoupper(app()->getLocale()) }}</span>
                    </button>
                    @auth
                        <a href="{{ route('dashboard') }}" class="pm-public-header-action pm-public-header-action--primary">{{ __('pitmetric.nav.dashboard') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="pm-public-header-action">{{ __('pitmetric.nav.login') }}</a>
                    @endauth
                </div>
            </div>
            <nav class="mx-auto flex max-w-7xl items-center gap-6 overflow-x-auto px-5 pb-3 text-xs font-semibold text-zinc-400 md:hidden" aria-label="Mobile navigation"><a href="{{ $publicHomeUrl }}" class="{{ request()->routeIs('home', 'localized.home') ? 'text-white' : '' }}">{{ __('pitmetric.nav.home') }}</a><a href="{{ $publicUpdatesUrl }}" class="{{ request()->routeIs('updates.*', 'localized.updates.*') ? 'text-white' : '' }}">{{ __('pitmetric.nav.updates') }}</a><a href="{{ $publicAppUrl }}" class="{{ request()->routeIs('app', 'localized.app') ? 'text-white' : '' }}">App</a><a href="{{ $publicAboutUrl }}" class="{{ request()->routeIs('about', 'localized.about') ? 'text-white' : '' }}">{{ __('pitmetric.nav.about') }}</a></nav>
        </header>

        <aside data-partner-notice class="pm-partner-notice" aria-label="{{ app()->getLocale() === 'it' ? 'Collaborazione PitMetric' : 'PitMetric collaboration' }}">
            <div class="flex items-center justify-between gap-4 border-b border-white/10 pb-3">
                <div class="flex items-center gap-2">
                    <span class="pm-partner-notice__pulse" aria-hidden="true"></span>
                    <span class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-[#ff625e]">Partners / Open</span>
                </div>
                <button type="button" data-partner-notice-close class="text-[10px] font-semibold uppercase tracking-[0.12em] text-zinc-500 transition hover:text-white">{{ app()->getLocale() === 'it' ? 'Nascondi' : 'Dismiss' }}</button>
            </div>
            <p class="mt-4 text-base font-semibold leading-6 text-white">{{ app()->getLocale() === 'it' ? 'Cerchiamo partner per sviluppare PitMetric sul campo.' : 'We are looking for partners to develop PitMetric in real motorsport workflows.' }}</p>
            <p class="mt-3 text-sm leading-6 text-zinc-400">{{ app()->getLocale() === 'it' ? 'Piloti, team e realtà motorsport possono contribuire con test, feedback e flussi di lavoro reali.' : 'Drivers, teams and motorsport organisations can contribute with testing, feedback and real workflows.' }}</p>
            <p class="mt-4 border-l-2 border-[#E10600] pl-3 text-sm font-semibold leading-6 text-zinc-200">{{ app()->getLocale() === 'it' ? 'Per chi collabora attivamente allo sviluppo, PitMetric è 100% gratuito: nessun canone e nessun costo di licenza.' : 'Active development partners use PitMetric 100% free: no subscription and no licence fee.' }}</p>
            <a href="mailto:simonebuttice05@gmail.com?subject=PitMetric%20Partnership" class="pm-home-text-link mt-5">{{ app()->getLocale() === 'it' ? 'Parliamone' : 'Talk to me' }}</a>
        </aside>

        <main>{{ $slot }}</main>

        <footer class="border-t border-white/10 bg-[#06080a]">
            <div class="mx-auto grid max-w-7xl gap-8 px-5 py-10 sm:grid-cols-[1fr_auto] sm:items-end lg:px-8">
                <div><img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-8 w-auto opacity-90"><p class="mt-4 max-w-md text-sm leading-6 text-zinc-500">{{ __('pitmetric.footer.tagline') }}</p><p class="mt-2 text-[11px] text-zinc-600">{{ __('pitmetric.footer.photo_note') }}</p></div>
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-zinc-500"><span>© {{ now()->year }} PitMetric</span><a href="{{ $publicAppUrl }}" class="hover:text-zinc-300">App</a><a href="{{ $publicAboutUrl }}" class="hover:text-zinc-300">{{ __('pitmetric.nav.about') }}</a><a href="{{ $publicUpdatesUrl }}" class="hover:text-zinc-300">{{ __('pitmetric.nav.updates') }}</a><button type="button" data-cookie-settings class="hover:text-zinc-300">{{ __('pitmetric.footer.cookies') }}</button></div>
            </div>
        </footer>
    </div>

    <div data-language-modal class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/85 p-5 backdrop-blur-md">
        <div class="relative w-full max-w-lg border border-white/10 bg-[#0d1014] p-7 shadow-2xl">
            <button type="button" data-language-close class="absolute right-5 top-5 border border-white/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.08em] text-zinc-400 hover:border-white/25 hover:text-white" aria-label="Close">Close</button>
            <img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-9 w-auto">
            <h2 class="mt-8 text-2xl font-black tracking-tight text-white">{{ __('pitmetric.language.title') }}</h2>
            <p class="mt-3 leading-7 text-zinc-400">{{ __('pitmetric.language.copy') }}</p>
            <div class="mt-7 grid gap-3 sm:grid-cols-2">
                @foreach (['en' => __('pitmetric.language.english'), 'it' => __('pitmetric.language.italian')] as $locale => $label)
                    <form method="POST" action="{{ route('locale.update') }}">@csrf<input type="hidden" name="locale" value="{{ $locale }}">@if (isset($languageRedirects[$locale]))<input type="hidden" name="redirect" value="{{ $languageRedirects[$locale] }}">@endif<button class="pm-public-locale-option flex w-full items-center justify-center gap-3 {{ app()->getLocale() === $locale ? 'bg-[#E10600] text-white' : 'border border-white/15 text-zinc-200 hover:border-white/30' }} px-4 py-3 font-semibold"><span class="pm-public-locale-code" aria-hidden="true">{{ strtoupper($locale) }}</span><span>{{ $label }}</span></button></form>
                @endforeach
            </div>
        </div>
    </div>

    <div data-cookie-banner class="fixed inset-x-4 bottom-4 z-[60] hidden max-w-3xl border border-white/10 bg-[#0d1014]/95 p-5 shadow-2xl backdrop-blur-xl md:left-1/2 md:right-auto md:w-[calc(100%-2rem)] md:-translate-x-1/2">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"><div><h2 class="font-bold text-white">{{ __('pitmetric.cookies.title') }}</h2><p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-400">{{ __('pitmetric.cookies.copy') }} <a href="{{ $publicCookiesUrl }}" class="text-[#ff4d49] underline underline-offset-4">{{ __('pitmetric.cookies.settings') }}</a></p></div><div class="flex shrink-0 flex-wrap gap-2"><button type="button" data-cookie-choice="necessary" class="border border-white/15 px-4 py-2 text-sm font-semibold text-zinc-200">{{ __('pitmetric.cookies.necessary') }}</button><button type="button" data-cookie-choice="preferences" class="bg-[#E10600] px-4 py-2 text-sm font-semibold text-white hover:bg-[#f01812]">{{ __('pitmetric.cookies.accept') }}</button></div></div>
    </div>
</body>
</html>
