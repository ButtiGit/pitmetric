<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#07090c] antialiased text-zinc-100">
        <div class="grid min-h-svh lg:grid-cols-[1.05fr_.95fr]">
            <aside class="relative hidden overflow-hidden border-r border-white/10 lg:block">
                <img src="https://images.unsplash.com/photo-1765202661219-cec5ad98f324?auto=format&fit=crop&fm=jpg&q=82&w=1800" alt="" class="absolute inset-0 h-full w-full object-cover">
                <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(5,7,9,.18),rgba(5,7,9,.88))]"></div>
                <div class="relative flex h-full flex-col justify-between p-10 xl:p-14">
                    <a href="{{ route('home') }}" class="inline-flex" wire:navigate aria-label="PitMetric home"><img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-11 w-auto"></a>
                    <div class="max-w-xl"><p class="font-mono text-xs uppercase tracking-[0.2em] text-[#ff4b47]">PitMetric</p><p class="mt-4 text-4xl font-black leading-tight tracking-[-0.04em] text-white">{{ __('pitmetric.home.title_1') }}<br>{{ __('pitmetric.home.title_2') }}</p><p class="mt-5 max-w-lg text-sm leading-7 text-zinc-300">{{ __('pitmetric.home.track_note') }}</p></div>
                </div>
            </aside>

            <main class="relative flex min-h-svh items-center justify-center px-6 py-12 sm:px-10">
                <div class="absolute right-5 top-5 flex gap-2">
                    @foreach (['en' => '🇬🇧 EN', 'it' => '🇮🇹 IT'] as $locale => $label)
                        <form method="POST" action="{{ route('locale.update') }}">@csrf<input type="hidden" name="locale" value="{{ $locale }}"><button class="rounded-full border px-3 py-1.5 text-xs font-semibold {{ app()->getLocale() === $locale ? 'border-[#E10600] bg-[#E10600] text-white' : 'border-white/10 text-zinc-400 hover:border-white/25 hover:text-white' }}">{{ $label }}</button></form>
                    @endforeach
                </div>
                <div class="w-full max-w-sm">
                    <a href="{{ route('home') }}" class="mb-8 flex items-center justify-center lg:hidden" wire:navigate aria-label="PitMetric home"><img src="{{ asset('brand/pitmetric-primary-dark.svg') }}" alt="PitMetric" class="h-11 w-auto"></a>
                    <div class="rounded-3xl border border-white/10 bg-white/[0.025] p-6 shadow-2xl shadow-black/20 sm:p-8">
                        {{ $slot }}
                    </div>
                </div>
            </main>
        </div>

        @persist('toast')<flux:toast.group><flux:toast /></flux:toast.group>@endpersist
        @fluxScripts
    </body>
</html>
