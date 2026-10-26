@can('launch-buttidesk')
    <a href="{{ route('buttidesk.launch') }}" {{ $attributes->class(['mx-2 mt-4 flex items-center gap-3 rounded-xl border border-blue-500/25 bg-blue-500/10 px-3 py-3 text-blue-300 transition hover:border-blue-400/50 hover:bg-blue-500/15']) }} data-buttidesk-switch>
        <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-blue-500/15"><flux:icon.rectangle-stack class="size-5" /></span>
        <span class="min-w-0 flex-1"><span class="block text-sm font-bold">ButtiDesk-Pro</span><span class="mt-0.5 block text-[11px] text-blue-200/70">{{ __('users.desk_switch_hint') }}</span></span>
        <flux:icon.arrow-up-right class="size-4 shrink-0" />
    </a>
@endcan
