<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#080a0d">
    <title>{{ $title ?? 'PitMetric' }}{{ isset($title) ? ' · PitMetric' : '' }}</title>
    <meta name="description" content="{{ $description ?? 'PitMetric motorsport software for tracking configurations, components, sessions, maintenance and costs.' }}">
    <meta property="og:title" content="{{ $title ?? 'PitMetric' }}">
    <meta property="og:description" content="{{ $description ?? 'Know every lap. Track every component.' }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="PitMetric">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
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
                <nav class="hidden items-center gap-7 text-sm text-zinc-300 md:flex" aria-label="Main navigation">
                    <a class="transition hover:text-white" href="{{ route('home') }}">{{ __('pitmetric.nav.home') }}</a>
                    <a class="transition hover:text-white" href="{{ route('updates.index') }}">{{ __('pitmetric.nav.updates') }}</a>
                    <a class="transition hover:text-white" href="{{ route('about') }}">{{ __('pitmetric.nav.about') }}</a>
                </nav>
                <div class="flex items-center gap-2">
                    <button type="button" data-language-open class="inline-flex items-center gap-2 rounded-xl border border-white/15 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-300 transition hover:border-white/30 hover:text-white">
                        <span aria-hidden="true">{{ app()->getLocale() === 'it' ? '🇮🇹' : '🇬🇧' }}</span>
                        <span>{{ strtoupper(app()->getLocale()) }}</span>
                    </button>
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-xl bg-[#E10600] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#f01812]">{{ __('pitmetric.nav.dashboard') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-xl border border-white/15 px-4 py-2 text-sm font-semibold transition hover:border-white/30 hover:bg-white/[0.04]">{{ __('pitmetric.nav.login') }}</a>
                    @endauth
                </div>
            </div>
        </header>

        <main>{{ $slot }}</main>

        <footer class="border-t border-white/10 bg-black/10">
            <div class="mx-auto flex max-w-7xl flex-col gap-5 px-5 py-8 text-sm text-zinc-500 sm:flex-row sm:items-center sm:justify-between lg:px-8">
                <div class="flex items-center gap-4">
                    <img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-7 w-auto opacity-80">
                    <p class="hidden lg:block">{{ __('pitmetric.footer.tagline') }}</p>
                </div>
                <div class="flex flex-wrap items-center gap-5">
                    <span>© {{ now()->year }} PitMetric</span>
                    <a href="{{ route('about') }}" class="hover:text-zinc-300">{{ __('pitmetric.nav.about') }}</a>
                    <a href="{{ route('updates.index') }}" class="hover:text-zinc-300">{{ __('pitmetric.nav.updates') }}</a>
                    <button type="button" data-cookie-settings class="hover:text-zinc-300">{{ __('pitmetric.footer.cookies') }}</button>
                </div>
            </div>
        </footer>
    </div>

    <div data-language-modal class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/80 p-5 backdrop-blur-sm">
        <div class="w-full max-w-lg rounded-3xl border border-white/10 bg-[#101318] p-7 shadow-2xl">
            <img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-9 w-auto">
            <h2 class="mt-7 text-2xl font-bold text-white">{{ __('pitmetric.language.title') }}</h2>
            <p class="mt-3 leading-7 text-zinc-400">{{ __('pitmetric.language.copy') }}</p>
            <div class="mt-7 grid gap-3 sm:grid-cols-2">
                @foreach ([
                    'en' => ['🇬🇧', __('pitmetric.language.english')],
                    'it' => ['🇮🇹', __('pitmetric.language.italian')],
                ] as $locale => [$flag, $label])
                    <form method="POST" action="{{ route('locale.update') }}">
                        @csrf
                        <input type="hidden" name="locale" value="{{ $locale }}">
                        <button class="flex w-full items-center justify-center gap-3 rounded-xl {{ app()->getLocale() === $locale ? 'bg-[#E10600] text-white' : 'border border-white/15 text-zinc-200 hover:border-white/30' }} px-4 py-3 font-semibold">
                            <span class="text-xl" aria-hidden="true">{{ $flag }}</span>
                            <span>{{ $label }}</span>
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>

    <div data-cookie-banner class="fixed inset-x-4 bottom-4 z-[60] hidden max-w-3xl rounded-2xl border border-white/10 bg-[#101318]/95 p-5 shadow-2xl backdrop-blur md:left-1/2 md:right-auto md:w-[calc(100%-2rem)] md:-translate-x-1/2">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="font-bold text-white">{{ __('pitmetric.cookies.title') }}</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-400">{{ __('pitmetric.cookies.copy') }} <a href="{{ route('cookies') }}" class="text-[#ff4d49] underline underline-offset-4">{{ __('pitmetric.cookies.settings') }}</a></p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <button type="button" data-cookie-choice="necessary" class="rounded-xl border border-white/15 px-4 py-2 text-sm font-semibold text-zinc-200">{{ __('pitmetric.cookies.necessary') }}</button>
                <button type="button" data-cookie-choice="preferences" class="rounded-xl bg-[#E10600] px-4 py-2 text-sm font-semibold text-white hover:bg-[#f01812]">{{ __('pitmetric.cookies.accept') }}</button>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const cookie = (name) => document.cookie.split('; ').find(row => row.startsWith(name + '='))?.split('=')[1];
            const setCookie = (name, value, days = 365) => {
                const secure = location.protocol === 'https:' ? '; Secure' : '';
                document.cookie = `${name}=${value}; Max-Age=${days * 86400}; Path=/; SameSite=Lax${secure}`;
            };

            const languageModal = document.querySelector('[data-language-modal]');
            if (!cookie('pitmetric_locale')) {
                languageModal?.classList.remove('hidden');
                languageModal?.classList.add('flex');
            }
            document.querySelectorAll('[data-language-open]').forEach(button => button.addEventListener('click', () => {
                languageModal?.classList.remove('hidden');
                languageModal?.classList.add('flex');
            }));

            const cookieBanner = document.querySelector('[data-cookie-banner]');
            const showCookieBanner = () => cookieBanner?.classList.remove('hidden');
            if (!cookie('pitmetric_cookie_consent')) showCookieBanner();
            document.querySelectorAll('[data-cookie-choice]').forEach(button => button.addEventListener('click', () => {
                setCookie('pitmetric_cookie_consent', button.dataset.cookieChoice);
                cookieBanner?.classList.add('hidden');
            }));
            document.querySelectorAll('[data-cookie-settings]').forEach(button => button.addEventListener('click', showCookieBanner));
        })();
    </script>
</body>
</html>
