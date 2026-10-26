@props(['user', 'ready'])
<div class="space-y-2" data-buttidesk-access="{{ $user->getKey() }}">
    @if (\Illuminate\Support\Facades\Gate::forUser($user)->allows('manage-updates'))
        <span class="text-xs font-semibold text-blue-400">{{ __('users.desk_editor_access') }}</span>
    @elseif (! $ready)
        <span class="text-xs text-pm-muted">{{ __('users.desk_not_ready') }}</span>
    @else
        <p class="text-xs font-semibold {{ $user->hasButtiDeskAccess() ? 'text-blue-400' : 'text-pm-muted' }}">{{ $user->hasButtiDeskAccess() ? __('users.desk_active') : __('users.desk_paused') }}</p>
        <form method="POST" action="{{ route('studio.users.buttidesk-access', $user) }}" onsubmit="return confirm(@js($user->hasButtiDeskAccess() ? __('users.desk_disable_confirm') : __('users.desk_enable_confirm')))">
            @csrf
            @method('PATCH')
            <input type="hidden" name="buttidesk_access_enabled" value="{{ $user->hasButtiDeskAccess() ? '0' : '1' }}">
            <button type="submit" class="pm-ghost-button !px-3 !py-2 !text-xs">{{ $user->hasButtiDeskAccess() ? __('users.desk_disable') : __('users.desk_enable') }}</button>
        </form>
    @endif
</div>
