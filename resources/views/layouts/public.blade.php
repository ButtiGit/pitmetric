<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080a0d">
    <title>{{ $title ?? 'PitMetric' }}{{ isset($title) ? ' · PitMetric' : '' }}</title>
    <meta name="description" content="{{ $description ?? __('pitmetric.home.intro') }}">
    <meta property="og:title" content="{{ $title ?? 'PitMetric' }}">
    <meta property="og:description" content="{{ $description ?? __('pitmetric.home.intro') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="PitMetric">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="pm-public-site min-h-screen bg-[#07090c] text-zinc-100 antialiased selection:bg-[#E10600] selection:text-white">
    <div data-pm-scroll-progress class="pm-scroll-progress" aria-hidden="true"></div>

    <div class="min-h-screen">
        <header data-pm-public-header class="sticky top-0 z-50 border-b border-white/10 bg-[#07090c]/90 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-3 lg:px-8">
                <a href="{{ route('home') }}" class="inline-flex items-center" aria-label="PitMetric home">
                    <img src="{{ asset('brand/pitmetric-compact-dark.svg') }}" alt="PitMetric" class="h-9 w-auto sm:hidden">
                    <img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="hidden h-10 w-auto sm:block">
                </a>
                <nav class="hidden items-center gap-8 text-sm font-semibold md:flex" aria-label="Main navigation">
                    <a class="border-b-2 pb-1 transition {{ request()->routeIs('home') ? 'border-[#E10600] text-white' : 'border-transparent text-zinc-400 hover:text-white' }}" href="{{ route('home') }}">{{ __('pitmetric.nav.home') }}</a>
                    <a class="border-b-2 pb-1 transition {{ request()->routeIs('updates.*') ? 'border-[#E10600] text-white' : 'border-transparent text-zinc-400 hover:text-white' }}" href="{{ route('updates.index') }}">{{ __('pitmetric.nav.updates') }}</a>
                    <a class="border-b-2 pb-1 transition {{ request()->routeIs('about') ? 'border-[#E10600] text-white' : 'border-transparent text-zinc-400 hover:text-white' }}" href="{{ route('about') }}">{{ __('pitmetric.nav.about') }}</a>
                </nav>
                <div class="flex items-center gap-2">
                    <button type="button" data-language-open class="pm-ui-locale" aria-label="{{ __('pitmetric.language.title') }}"><span class="pm-ui-locale__mark" aria-hidden="true"></span><span>{{ strtoupper(app()->getLocale()) }}</span></button>
                    @auth
                        <a href="{{ route('dashboard') }}" class="pm-ui-header-action pm-ui-header-action--primary">{{ __('pitmetric.nav.dashboard') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="pm-ui-header-action">{{ __('pitmetric.nav.login') }}</a>
                    @endauth
                </div>
            </div>
            <nav class="mx-auto flex max-w-7xl items-center gap-6 overflow-x-auto px-5 pb-3 text-xs font-semibold text-zinc-400 md:hidden" aria-label="Mobile navigation"><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'text-white' : '' }}">{{ __('pitmetric.nav.home') }}</a><a href="{{ route('updates.index') }}" class="{{ request()->routeIs('updates.*') ? 'text-white' : '' }}">{{ __('pitmetric.nav.updates') }}</a><a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'text-white' : '' }}">{{ __('pitmetric.nav.about') }}</a></nav>
        </header>

        <main>{{ $slot }}</main>

        <footer class="border-t border-white/10 bg-[#06080a]">
            <div class="mx-auto grid max-w-7xl gap-8 px-5 py-10 sm:grid-cols-[1fr_auto] sm:items-end lg:px-8">
                <div><img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-8 w-auto opacity-90"><p class="mt-4 max-w-md text-sm leading-6 text-zinc-500">{{ __('pitmetric.footer.tagline') }}</p><p class="mt-2 text-[11px] text-zinc-600">{{ __('pitmetric.footer.photo_note') }}</p></div>
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-zinc-500"><span>© {{ now()->year }} PitMetric</span><a href="{{ route('about') }}" class="hover:text-zinc-300">{{ __('pitmetric.nav.about') }}</a><a href="{{ route('updates.index') }}" class="hover:text-zinc-300">{{ __('pitmetric.nav.updates') }}</a><button type="button" data-cookie-settings class="hover:text-zinc-300">{{ __('pitmetric.footer.cookies') }}</button></div>
            </div>
        </footer>
    </div>

    <div data-language-modal class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/85 p-5 backdrop-blur-md">
        <div class="pm-ui-modal relative w-full max-w-lg border border-white/10 bg-[#0d1014] p-7 shadow-2xl">
            <button type="button" data-language-close class="absolute right-5 top-5 grid size-9 place-items-center border border-white/10 text-sm text-zinc-400 transition hover:border-white/25 hover:text-white" aria-label="Close"><span class="pm-ui-close-mark" aria-hidden="true"></span></button>
            <img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-9 w-auto">
            <h2 class="mt-8 text-2xl font-black tracking-tight text-white">{{ __('pitmetric.language.title') }}</h2>
            <p class="mt-3 leading-7 text-zinc-400">{{ __('pitmetric.language.copy') }}</p>
            <div class="mt-7 grid gap-3 sm:grid-cols-2">
                @foreach (['en' => __('pitmetric.language.english'), 'it' => __('pitmetric.language.italian')] as $locale => $label)
                    <form method="POST" action="{{ route('locale.update') }}">@csrf<input type="hidden" name="locale" value="{{ $locale }}"><button class="pm-ui-language-option {{ app()->getLocale() === $locale ? 'pm-ui-language-option--active' : '' }}"><span class="pm-ui-language-code">{{ strtoupper($locale) }}</span><span>{{ $label }}</span></button></form>
                @endforeach
            </div>
        </div>
    </div>

    <div data-cookie-banner class="pm-ui-cookie fixed inset-x-4 bottom-4 z-[60] hidden max-w-3xl border border-white/10 bg-[#0d1014]/95 p-5 shadow-2xl backdrop-blur-xl md:left-1/2 md:right-auto md:w-[calc(100%-2rem)] md:-translate-x-1/2">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between"><div><h2 class="font-bold text-white">{{ __('pitmetric.cookies.title') }}</h2><p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-400">{{ __('pitmetric.cookies.copy') }} <a href="{{ route('cookies') }}" class="text-[#ff4d49] underline underline-offset-4">{{ __('pitmetric.cookies.settings') }}</a></p></div><div class="flex shrink-0 flex-wrap gap-2"><button type="button" data-cookie-choice="necessary" class="pm-ui-header-action">{{ __('pitmetric.cookies.necessary') }}</button><button type="button" data-cookie-choice="preferences" class="pm-ui-header-action pm-ui-header-action--primary">{{ __('pitmetric.cookies.accept') }}</button></div></div>
    </div>

    <script>
        (() => {
            const cookie = (name) => document.cookie.split('; ').find(row => row.startsWith(name + '='))?.split('=')[1];
            const setCookie = (name, value, days = 365) => { const secure = location.protocol === 'https:' ? '; Secure' : ''; document.cookie = `${name}=${value}; Max-Age=${days * 86400}; Path=/; SameSite=Lax${secure}`; };
            const languageModal = document.querySelector('[data-language-modal]');
            const openLanguage = () => { languageModal?.classList.remove('hidden'); languageModal?.classList.add('flex'); };
            const closeLanguage = () => { languageModal?.classList.add('hidden'); languageModal?.classList.remove('flex'); };
            if (!cookie('pitmetric_locale')) openLanguage();
            document.querySelectorAll('[data-language-open]').forEach(button => button.addEventListener('click', openLanguage));
            document.querySelectorAll('[data-language-close]').forEach(button => button.addEventListener('click', closeLanguage));
            languageModal?.addEventListener('click', event => { if (event.target === languageModal && cookie('pitmetric_locale')) closeLanguage(); });
            const cookieBanner = document.querySelector('[data-cookie-banner]');
            const showCookieBanner = () => cookieBanner?.classList.remove('hidden');
            if (!cookie('pitmetric_cookie_consent')) showCookieBanner();
            document.querySelectorAll('[data-cookie-choice]').forEach(button => button.addEventListener('click', () => { setCookie('pitmetric_cookie_consent', button.dataset.cookieChoice); cookieBanner?.classList.add('hidden'); }));
            document.querySelectorAll('[data-cookie-settings]').forEach(button => button.addEventListener('click', showCookieBanner));
        })();
    </script>
</body>
</html>
