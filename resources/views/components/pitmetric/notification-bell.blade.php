@php
    $notificationUser = auth()->user();
    $workspaceId = $notificationUser ? app(\App\Services\WorkspaceContext::class)->currentId($notificationUser) : null;
    $notifications = $notificationUser
        ? $notificationUser->unreadNotifications()->latest()->limit(100)->get()
            ->filter(fn ($notification) => $workspaceId === null || (int) ($notification->data['workspace_id'] ?? 0) === (int) $workspaceId)
        : collect();
    $notificationCount = $notifications->count();
    $notificationItems = $notifications->take(8);
@endphp

<flux:dropdown position="bottom" align="end">
    <button
        type="button"
        class="relative inline-flex size-10 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-white/5 hover:text-zinc-100"
        aria-label="{{ app()->getLocale() === 'it' ? 'Avvisi' : 'Alerts' }}"
        data-pm-notification-bell
    >
        <flux:icon.bell class="size-5" />
        @if ($notificationCount > 0)
            <span class="absolute right-0 top-0 inline-flex min-w-5 -translate-y-1/4 translate-x-1/4 items-center justify-center rounded-full bg-[#E10600] px-1.5 py-0.5 text-[10px] font-black leading-none text-white">
                {{ $notificationCount > 99 ? '99+' : $notificationCount }}
            </span>
        @endif
    </button>

    <div class="w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-white/10 bg-[#15181d] shadow-2xl">
        <div class="flex items-center justify-between border-b border-white/8 px-4 py-3">
            <div>
                <p class="text-sm font-black text-zinc-100">{{ app()->getLocale() === 'it' ? 'Avvisi' : 'Alerts' }}</p>
                <p class="text-xs text-zinc-500">{{ $notificationCount }} {{ app()->getLocale() === 'it' ? 'da controllare' : 'to review' }}</p>
            </div>
        </div>

        <div class="max-h-[26rem] overflow-y-auto">
            @forelse ($notificationItems as $notification)
                @php
                    $routeName = $notification->data['route_name'] ?? 'dashboard';
                    $routeParams = is_array($notification->data['route_params'] ?? null) ? $notification->data['route_params'] : [];
                    $target = \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName, $routeParams) : route('dashboard');
                @endphp
                <a href="{{ $target }}" class="block border-b border-white/5 px-4 py-3 transition last:border-b-0 hover:bg-white/[0.04]">
                    <div class="flex items-start gap-3">
                        <span class="mt-1 size-2 shrink-0 rounded-full {{ ($notification->data['severity'] ?? 'info') === 'critical' ? 'bg-red-500' : (($notification->data['severity'] ?? 'info') === 'warning' ? 'bg-amber-400' : 'bg-sky-400') }}"></span>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-zinc-100">{{ $notification->data['title'] ?? (app()->getLocale() === 'it' ? 'Avviso' : 'Alert') }}</p>
                            @if (filled($notification->data['message'] ?? null))
                                <p class="mt-1 text-xs leading-5 text-zinc-400">{{ $notification->data['message'] }}</p>
                            @endif
                            <p class="mt-1 text-[10px] font-mono uppercase tracking-wide text-zinc-600">{{ $notification->created_at?->diffForHumans() }}</p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-5 py-8 text-center text-sm text-zinc-500">{{ app()->getLocale() === 'it' ? 'Nessun avviso aperto.' : 'No open alerts.' }}</div>
            @endforelse
        </div>
    </div>
</flux:dropdown>
