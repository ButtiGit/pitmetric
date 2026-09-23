@php
    $count = 0;
    $user = auth()->user();

    if ($user instanceof \App\Models\User
        && \Illuminate\Support\Facades\Schema::hasTable('follow_up_tasks')
        && \Illuminate\Support\Facades\Schema::hasTable('workspace_user')) {
        $workspaceId = request()->hasSession() ? request()->session()->get('pitmetric.current_workspace_id') : null;
        $membership = \Illuminate\Support\Facades\DB::table('workspace_user')
            ->where('user_id', $user->getKey());

        if (\Illuminate\Support\Facades\Schema::hasColumn('workspace_user', 'status')) {
            $membership->where('status', 'active');
        }

        if (is_numeric($workspaceId)) {
            $workspaceId = (int) $workspaceId;
            $validSelection = (clone $membership)->where('workspace_id', $workspaceId)->exists();
            $workspaceId = $validSelection ? $workspaceId : null;
        }

        if (! is_int($workspaceId)) {
            $selected = (clone $membership)->orderBy('workspace_id')->value('workspace_id');
            $workspaceId = is_numeric($selected) ? (int) $selected : null;
        }

        if ($workspaceId !== null) {
            $count = \Illuminate\Support\Facades\DB::table('follow_up_tasks')
                ->where('workspace_id', $workspaceId)
                ->where('status', 'open')
                ->count();
        }
    }
@endphp

<a
    href="{{ route('follow-ups.index') }}"
    class="fixed right-14 top-3 z-50 inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-[#171a20]/95 text-zinc-200 shadow-lg backdrop-blur transition hover:border-white/20 hover:bg-[#1d2128] lg:right-5 lg:top-5"
    aria-label="{{ app()->getLocale() === 'it' ? 'Avvisi da completare' : 'Follow-up alerts' }}"
    title="{{ app()->getLocale() === 'it' ? 'Avvisi da completare' : 'Follow-up alerts' }}"
>
    <flux:icon.bell class="size-5" />
    @if ($count > 0)
        <span class="absolute -right-1 -top-1 min-w-5 rounded-full bg-[#E10600] px-1.5 py-0.5 text-center font-mono text-[10px] font-black leading-4 text-white">{{ $count > 99 ? '99+' : $count }}</span>
    @endif
</a>
