@php
    $show = request()->routeIs('sessions.*') || request()->routeIs('timing.*') || request()->routeIs('circuits.*');
    $captures = collect();

    if ($show && auth()->check() && \Illuminate\Support\Facades\Schema::hasTable('track_captures')) {
        $captures = \App\Models\TrackCapture::query()
            ->with(['circuit', 'circuitLayout'])
            ->latest('occurred_at')
            ->latest('id')
            ->limit(12)
            ->get();
    }
@endphp

@if ($show && $captures->isNotEmpty())
    <div class="mx-auto w-full max-w-[1360px] px-4 pt-4 sm:px-6 lg:px-8">
        @if (request()->routeIs('circuits.*'))
            @php($pending = $captures->where('status', 'needs_attention'))
            @if ($pending->isNotEmpty())
                <section class="rounded-2xl border border-amber-400/20 bg-amber-400/[0.05] p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.14em] text-amber-300">{{ app()->getLocale() === 'it' ? 'Da completare' : 'Needs completion' }}</p>
                            <h2 class="mt-1 text-base font-black text-pm-text">{{ app()->getLocale() === 'it' ? 'Circuiti usati al volo ma non ancora creati' : 'Circuits used in quick capture but not created yet' }}</h2>
                        </div>
                        <a href="{{ route('follow-ups.index') }}" class="pm-ghost-button">{{ app()->getLocale() === 'it' ? 'Apri avvisi' : 'Open alerts' }}</a>
                    </div>
                    <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($pending->groupBy(fn ($capture) => mb_strtolower($capture->circuit_name ?? ''))->take(6) as $group)
                            @php($first = $group->first())
                            <div class="rounded-xl border border-white/8 bg-black/10 p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-black text-pm-text">{{ $first->circuit_name }}</p>
                                        <p class="mt-1 text-xs text-pm-muted">{{ $group->count() }} {{ app()->getLocale() === 'it' ? 'tempi salvati' : 'saved laps' }}</p>
                                    </div>
                                    <span class="rounded-full bg-amber-400/10 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-amber-300">{{ app()->getLocale() === 'it' ? 'Da creare' : 'Create' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        @else
            <section class="rounded-2xl border border-white/8 bg-white/[0.025] p-4 sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.14em] text-[#E10600]">{{ app()->getLocale() === 'it' ? 'Tempi catturati al volo' : 'Quick timing capture' }}</p>
                        <h2 class="mt-1 text-base font-black text-pm-text">{{ app()->getLocale() === 'it' ? 'Ultimi tempi salvati in pista' : 'Latest trackside timing records' }}</h2>
                    </div>
                    <a href="{{ route('follow-ups.index') }}" class="text-xs font-bold text-pm-muted hover:text-pm-text">{{ app()->getLocale() === 'it' ? 'Avvisi e dati incompleti' : 'Alerts and incomplete data' }}</a>
                </div>
                <div class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($captures->take(8) as $capture)
                        <article class="rounded-xl border border-pm-border bg-pm-subtle p-3 {{ (string) request('captured') === (string) $capture->id ? 'ring-1 ring-[#E10600]/60' : '' }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-black text-pm-text">{{ $capture->circuit?->name ?? $capture->circuit_name }}</p>
                                    <p class="mt-1 font-mono text-xl font-black text-white">{{ $capture->formattedLapTime() }}</p>
                                </div>
                                @if ($capture->status === 'needs_attention')
                                    <span title="{{ app()->getLocale() === 'it' ? 'Circuito da completare' : 'Circuit needs completion' }}" class="shrink-0 rounded-full bg-amber-400/10 px-2 py-1 text-[10px] font-black text-amber-300">!</span>
                                @else
                                    <span class="shrink-0 rounded-full bg-emerald-400/10 px-2 py-1 text-[10px] font-black text-emerald-300">✓</span>
                                @endif
                            </div>
                            <p class="mt-2 text-[11px] text-pm-muted">{{ $capture->occurred_at?->format('d/m H:i') }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endif
