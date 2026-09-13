<x-layouts::app :title="__('users.title')">
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-3 py-4 sm:px-6 sm:py-5 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1260px] space-y-4 sm:space-y-5">
            @if (session('status'))
                <div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success" role="status">{{ session('status') }}</div>
            @endif

            @if (session('studio_setup_error'))
                <div class="rounded-xl border border-pm-warning/30 bg-pm-warning-subtle px-4 py-3 text-sm font-semibold text-pm-warning" role="alert">{{ session('studio_setup_error') }}</div>
            @endif

            @if (! $accessControlReady)
                <div class="rounded-xl border border-pm-warning/30 bg-pm-warning-subtle px-4 py-3 text-sm text-pm-warning" role="status">{{ __('users.setup_required') }}</div>
            @endif

            <section class="pm-panel p-5 sm:p-8">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">{{ __('users.eyebrow') }}</p>
                        <h1 class="mt-2 text-3xl font-black tracking-[-0.035em] text-pm-text sm:text-4xl">{{ __('users.title') }}</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-pm-text-secondary">{{ __('users.intro') }}</p>
                    </div>
                    <div class="rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 lg:text-right">
                        <p class="text-[11px] font-bold uppercase tracking-[0.1em] text-pm-muted">{{ __('users.total') }}</p>
                        <p class="mt-1 text-2xl font-black text-pm-text">{{ $users->total() }}</p>
                    </div>
                </div>
            </section>

            <section class="pm-panel p-4 sm:p-5">
                <form method="GET" action="{{ route('studio.users.index') }}" class="flex flex-col gap-3 sm:flex-row">
                    <label class="min-w-0 flex-1">
                        <span class="sr-only">{{ __('users.search_placeholder') }}</span>
                        <input class="pm-input" type="search" name="q" value="{{ $search }}" placeholder="{{ __('users.search_placeholder') }}" autocomplete="off">
                    </label>
                    <button type="submit" class="pm-race-button w-full sm:w-auto">{{ __('users.search') }}</button>
                    @if ($search !== '')
                        <a href="{{ route('studio.users.index') }}" class="pm-ghost-button w-full sm:w-auto">{{ __('users.clear_search') }}</a>
                    @endif
                </form>
            </section>

            <section class="space-y-3 lg:hidden">
                @forelse ($users as $user)
                    @php($isEditor = \Illuminate\Support\Facades\Gate::forUser($user)->allows('manage-updates'))
                    <article class="pm-panel p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h2 class="truncate font-black text-pm-text">{{ $user->name }}</h2>
                                <p class="mt-1 break-all text-sm text-pm-text-secondary">{{ $user->email }}</p>
                            </div>
                            @if ($isEditor)
                                <span class="shrink-0 rounded-full bg-pm-accent-subtle px-2.5 py-1 text-[11px] font-bold text-pm-accent">{{ __('users.editor') }}</span>
                            @elseif ($user->hasDatabaseAccess())
                                <span class="shrink-0 rounded-full bg-pm-success-subtle px-2.5 py-1 text-[11px] font-bold text-pm-success">{{ __('users.active') }}</span>
                            @else
                                <span class="shrink-0 rounded-full bg-pm-subtle px-2.5 py-1 text-[11px] font-bold text-pm-muted">{{ __('users.paused') }}</span>
                            @endif
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 border-t border-pm-border pt-4 text-sm">
                            <div><p class="text-xs text-pm-muted">{{ __('users.verification') }}</p><p class="mt-1 font-semibold text-pm-text-secondary">{{ $user->email_verified_at ? __('users.verified') : __('users.unverified') }}</p></div>
                            <div><p class="text-xs text-pm-muted">{{ __('users.registered') }}</p><p class="mt-1 font-semibold text-pm-text-secondary">{{ $user->created_at?->format('d/m/Y') }}</p></div>
                        </div>

                        <div class="mt-4">
                            @if ($isEditor)
                                <span class="text-sm font-semibold text-pm-muted">{{ __('users.editor_access') }}</span>
                            @elseif ($accessControlReady)
                                <form method="POST" action="{{ route('studio.users.access', $user) }}" onsubmit="return confirm(@js($user->hasDatabaseAccess() ? __('users.disable_confirm') : __('users.enable_confirm')))" >
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="database_access_enabled" value="{{ $user->hasDatabaseAccess() ? '0' : '1' }}">
                                    <button type="submit" class="{{ $user->hasDatabaseAccess() ? 'pm-danger-button' : 'pm-race-button' }} w-full">{{ $user->hasDatabaseAccess() ? __('users.disable') : __('users.enable') }}</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="pm-panel p-8 text-center text-sm text-pm-muted">{{ __('users.empty') }}</div>
                @endforelse
            </section>

            <section class="pm-panel hidden overflow-hidden lg:block">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-pm-border bg-pm-subtle text-[11px] uppercase tracking-[0.1em] text-pm-muted">
                            <tr>
                                <th class="px-5 py-3">{{ __('users.user') }}</th>
                                <th class="px-5 py-3">{{ __('users.verification') }}</th>
                                <th class="px-5 py-3">{{ __('users.registered') }}</th>
                                <th class="px-5 py-3">{{ __('users.access') }}</th>
                                <th class="px-5 py-3 text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-pm-border">
                            @forelse ($users as $user)
                                @php($isEditor = \Illuminate\Support\Facades\Gate::forUser($user)->allows('manage-updates'))
                                <tr class="transition hover:bg-pm-hover">
                                    <td class="px-5 py-4"><p class="font-bold text-pm-text">{{ $user->name }}</p><p class="mt-1 text-xs text-pm-text-secondary">{{ $user->email }}</p></td>
                                    <td class="px-5 py-4"><span class="font-semibold {{ $user->email_verified_at ? 'text-pm-success' : 'text-pm-muted' }}">{{ $user->email_verified_at ? __('users.verified') : __('users.unverified') }}</span></td>
                                    <td class="px-5 py-4 text-pm-text-secondary">{{ $user->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-5 py-4">
                                        @if ($isEditor)
                                            <span class="rounded-full bg-pm-accent-subtle px-2.5 py-1 text-xs font-bold text-pm-accent">{{ __('users.editor') }}</span>
                                        @elseif ($user->hasDatabaseAccess())
                                            <span class="rounded-full bg-pm-success-subtle px-2.5 py-1 text-xs font-bold text-pm-success">{{ __('users.active') }}</span>
                                        @else
                                            <span class="rounded-full bg-pm-subtle px-2.5 py-1 text-xs font-bold text-pm-muted">{{ __('users.paused') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        @if ($isEditor)
                                            <span class="text-xs font-semibold text-pm-muted">{{ __('users.editor_access') }}</span>
                                        @elseif ($accessControlReady)
                                            <form method="POST" action="{{ route('studio.users.access', $user) }}" class="inline" onsubmit="return confirm(@js($user->hasDatabaseAccess() ? __('users.disable_confirm') : __('users.enable_confirm')))" >
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="database_access_enabled" value="{{ $user->hasDatabaseAccess() ? '0' : '1' }}">
                                                <button type="submit" class="{{ $user->hasDatabaseAccess() ? 'pm-danger-button' : 'pm-race-button' }}">{{ $user->hasDatabaseAccess() ? __('users.disable') : __('users.enable') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-5 py-12 text-center text-pm-muted">{{ __('users.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            @if ($users->hasPages())
                <div>{{ $users->links() }}</div>
            @endif
        </div>
    </div>
</x-layouts::app>
