@props([
    'id',
    'title',
    'description' => null,
    'trigger' => null,
    'triggerClass' => 'pm-race-button',
    'size' => 'max-w-3xl',
    'permission' => 'team-write',
])

@if ($permission === null || auth()->user()?->can($permission))
@if ($trigger)
    <button type="button" class="{{ $triggerClass }}" aria-haspopup="dialog" aria-controls="{{ $id }}" data-test="{{ $id }}-trigger" onclick="document.getElementById('{{ $id }}').showModal()">
        {{ $trigger }}
    </button>
@endif

<dialog id="{{ $id }}" data-pm-crud-dialog data-test="{{ $id }}-dialog" aria-labelledby="{{ $id }}-title" @if ($description) aria-describedby="{{ $id }}-description" @endif class="w-[min(94vw,72rem)] {{ $size }} rounded-2xl border border-pm-border bg-pm-panel p-0 text-pm-text shadow-2xl backdrop:bg-black/70">
    <div class="max-h-[88vh] overflow-y-auto p-5 sm:p-6">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 id="{{ $id }}-title" class="text-lg font-black text-pm-text sm:text-xl">{{ $title }}</h2>
                @if ($description)
                    <p id="{{ $id }}-description" class="mt-1 text-sm leading-6 text-pm-text-secondary">{{ $description }}</p>
                @endif
            </div>
            <button type="button" class="grid size-9 shrink-0 place-items-center rounded-lg border border-pm-border bg-pm-subtle text-lg text-pm-muted transition hover:text-pm-text" onclick="document.getElementById('{{ $id }}').close()" aria-label="{{ __('Close') }}">×</button>
        </div>

        @if ($errors->any() && old('_pm_dialog') === $id)
            <div class="mb-4 rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger" role="alert" tabindex="-1" data-pm-form-errors>
                <p class="font-bold">{{ __('workflow.check_fields') }}</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
            <script type="application/json" data-pm-form-state>{!! \Illuminate\Support\Js::encode(['input' => collect(session()->getOldInput())->except(['_token', 'password', 'password_confirmation', 'current_password'])->all(), 'errors' => $errors->messages()]) !!}</script>
        @endif
        {{ $slot }}
        <div class="mt-4 border-t border-pm-border pt-3"><button type="button" class="pm-ghost-button" onclick="document.getElementById('{{ $id }}').close()">{{ __('workflow.cancel') }}</button></div>
    </div>
</dialog>
@endif
