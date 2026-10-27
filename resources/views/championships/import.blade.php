<x-crud-modal id="championship-import" :title="__('championship.import')" :trigger="__('championship.import')" size="max-w-4xl">
    <div data-pm-championship-import>
        <script type="application/json" data-import-config>{!! \Illuminate\Support\Js::encode([
            'csrf' => csrf_token(),
            'urls' => ['upload' => route('championships.imports.upload', $championship), 'preview' => route('championships.imports.preview', $championship), 'confirm' => route('championships.imports.confirm', $championship)],
            'fields' => \App\Services\ChampionshipImportService::FIELDS,
            'labels' => __('championship.wizard'), 'fieldLabels' => __('championship.fields'),
        ]) !!}</script>
        <ol class="mb-5 flex flex-wrap gap-2 text-xs font-bold text-pm-muted" aria-label="{{ __('championship.import') }}">@foreach (['file','mapping','preview'] as $index => $step)<li class="rounded-lg border border-pm-border px-3 py-2" data-import-progress="{{ $index }}">{{ $index + 1 }}. {{ __('championship.wizard.'.$step) }}</li>@endforeach</ol>
        <p role="alert" tabindex="-1" data-import-error hidden class="mb-4 whitespace-pre-line rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger"></p>
        <form data-import-step="0" data-import-upload class="space-y-4">
            <h3 class="font-bold text-pm-text" tabindex="-1">{{ __('championship.wizard.file') }}</h3>
            <label class="grid gap-2"><span class="pm-label">{{ __('championship.wizard.kind') }}</span><select class="pm-input" name="kind">@foreach (['participants','rounds','results','scoring'] as $kind)<option value="{{ $kind }}">{{ __('championship.'.$kind) }}</option>@endforeach</select></label>
            <label class="grid gap-2"><span class="pm-label">{{ __('championship.wizard.file') }}</span><input class="pm-input" type="file" name="file" accept=".xlsx,.csv,.tsv" required></label><p class="text-xs leading-6 text-pm-muted">{{ __('championship.wizard.file_help') }}</p>
            <button class="pm-race-button" type="submit">{{ __('championship.wizard.upload') }}</button>
        </form>
        <form data-import-step="1" data-import-map hidden class="space-y-4">
            <h3 class="font-bold text-pm-text" tabindex="-1">{{ __('championship.wizard.mapping') }}</h3>
            <div class="grid gap-3 sm:grid-cols-2"><label class="grid gap-2"><span class="pm-label">{{ __('championship.wizard.sheet') }}</span><select class="pm-input" name="sheet" data-import-sheet></select></label><label class="grid gap-2"><span class="pm-label">{{ __('championship.wizard.header_row') }}</span><input class="pm-input" type="number" name="header_row" min="1" max="20" value="1" required></label></div>
            <p class="text-xs leading-6 text-pm-muted">{{ __('championship.wizard.map_help') }}</p>
            <div class="grid gap-3 sm:grid-cols-2" data-import-fields></div>
            <details class="rounded-xl border border-pm-border p-3"><summary class="cursor-pointer text-xs font-bold text-pm-muted">{{ __('championship.wizard.sample') }}</summary><div class="mt-3 space-y-2" data-import-sample></div></details>
            <div class="flex flex-wrap gap-2"><button type="button" class="pm-ghost-button" data-import-go="0">{{ __('championship.wizard.change_file') }}</button><button class="pm-race-button" type="submit">{{ __('championship.wizard.check') }}</button></div>
        </form>
        <section data-import-step="2" hidden class="space-y-4">
            <h3 class="font-bold text-pm-text" tabindex="-1">{{ __('championship.wizard.preview') }}</h3>
            <p class="text-sm text-pm-text-secondary" data-import-summary></p>
            <p hidden class="rounded-xl border border-pm-warning/30 bg-pm-warning-subtle p-3 text-xs leading-6 text-pm-warning" data-import-score-warning>{{ __('championship.wizard.score_warning') }}</p>
            <div class="max-h-[40vh] space-y-2 overflow-y-auto" data-import-rows></div>
            <div class="flex flex-wrap gap-2"><button type="button" class="pm-ghost-button" data-import-go="1">{{ __('championship.wizard.back') }}</button><button type="button" class="pm-race-button" data-import-confirm>{{ __('championship.wizard.confirm') }}</button></div>
        </section>
    </div>
</x-crud-modal>
