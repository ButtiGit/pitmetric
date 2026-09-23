<x-layouts::app :title="app()->getLocale() === 'it' ? 'Avvisi' : 'Alerts'">
    @php($it = app()->getLocale() === 'it')
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1100px] space-y-5">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <x-pitmetric.page-header :title="$it ? 'Da completare' : 'Follow-up inbox'" :description="$it ? 'Qui finiscono le cose che PitMetric ti ha lasciato salvare al volo senza bloccarti in pista.' : 'Things PitMetric let you save immediately without blocking your trackside workflow.'" />
                <span class="rounded-full border border-white/10 bg-white/[0.03] px-3 py-1.5 font-mono text-xs font-black text-pm-text">{{ $tasks->count() }} {{ $it ? 'aperti' : 'open' }}</span>
            </div>

            @if (session('status'))<p role="status" class="pm-feedback">{{ session('status') }}</p>@endif

            <div class="space-y-3">
                @forelse ($tasks as $task)
                    <article class="pm-panel p-4 sm:p-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-amber-400/10 px-2 py-1 text-[10px] font-black uppercase tracking-[0.1em] text-amber-300">{{ $it ? 'Da fare' : 'To do' }}</span>
                                    <span class="text-[10px] font-bold uppercase tracking-[0.1em] text-pm-muted">{{ str_replace('_', ' ', $task->kind) }}</span>
                                </div>
                                <h2 class="mt-2 text-base font-black text-pm-text">{{ $task->title }}</h2>
                                @if ($task->description)<p class="mt-2 max-w-3xl text-sm leading-6 text-pm-text-secondary">{{ $task->description }}</p>@endif
                                <p class="mt-2 text-xs text-pm-muted">{{ $task->created_at?->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                @if ($task->target_route && \Illuminate\Support\Facades\Route::has($task->target_route))
                                    <a class="pm-race-button" href="{{ route($task->target_route) }}">{{ $it ? 'Completa' : 'Complete' }}</a>
                                @endif
                                @can('team-write')
                                    <form method="POST" action="{{ route('follow-ups.complete', $task) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="pm-ghost-button" type="submit">{{ $it ? 'Segna fatto' : 'Mark done' }}</button>
                                    </form>
                                @endcan
                            </div>
                        </div>
                    </article>
                @empty
                    <x-pitmetric.empty-state :title="$it ? 'Tutto a posto' : 'All caught up'" :description="$it ? 'Non ci sono dati da completare. Puoi continuare a usare la cattura rapida in pista.' : 'There is nothing waiting for completion. Keep using quick capture trackside.'" />
                @endforelse
            </div>
        </div>
    </div>
</x-layouts::app>
