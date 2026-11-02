@props(['user'])

<dl class="min-w-0 space-y-3 text-sm [overflow-wrap:anywhere]" data-user-activity="{{ $user->id }}">
    <div>
        <dt class="text-xs text-pm-muted">{{ __('users.records_created') }}</dt>
        <dd class="mt-1 text-lg font-black tabular-nums text-pm-text" data-records-created>{{ $user->records_created_count }}</dd>
    </div>
    <div>
        <dt class="text-xs text-pm-muted">{{ __('users.last_login') }}</dt>
        <dd class="mt-1 font-semibold text-pm-text-secondary" data-last-login>
            @if ($user->last_login_at)
                <time datetime="{{ $user->last_login_at->toIso8601String() }}">{{ $user->last_login_at->timezone('Europe/Rome')->format('d/m/Y H:i') }}</time>
            @else
                {{ __('users.login_unknown') }}
            @endif
        </dd>
    </div>
</dl>
