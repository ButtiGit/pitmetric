<x-layouts::app :title="$update->exists ? __('pitmetric.studio.edit_post') : __('pitmetric.studio.create_post')">
    @php
        $editing = $update->exists;
        $primaryLocale = app()->getLocale() === 'it' ? 'it' : 'en';
        $isItalian = $primaryLocale === 'it';

        $primaryTitle = old('primary_title', $isItalian ? ($update->title_it ?: $update->title) : $update->title);
        $primaryExcerpt = old('primary_excerpt', $isItalian ? ($update->excerpt_it ?: $update->excerpt) : $update->excerpt);
        $primaryContent = old('primary_content', $isItalian ? ($update->content_it ?: $update->content) : $update->content);
        $translationTitle = old('translation_title', $isItalian ? ($update->title_it ? $update->title : '') : $update->title_it);
        $translationExcerpt = old('translation_excerpt', $isItalian ? ($update->excerpt_it ? $update->excerpt : '') : $update->excerpt_it);
        $translationContent = old('translation_content', $isItalian ? ($update->content_it ? $update->content : '') : $update->content_it);
        $translationLabel = $isItalian ? 'English' : 'Italiano';
    @endphp

    <div class="pitmetric-app pm-page flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('studio.updates.index') }}" class="text-sm font-semibold text-pm-text-secondary transition hover:text-pm-text">← {{ __('pitmetric.studio.back') }}</a>
                <h1 class="mt-3 text-3xl font-black tracking-[-0.04em] text-pm-text">{{ $editing ? __('pitmetric.studio.edit_post') : __('pitmetric.studio.create_post') }}</h1>
                <p class="mt-2 text-sm text-pm-text-secondary">{{ __('pitmetric.studio.composer_hint', ['language' => $isItalian ? 'Italiano' : 'English']) }}</p>
            </div>
            @if ($editing && $update->status === 'published')
                <a href="{{ route('updates.show', $update) }}" target="_blank" class="pm-ghost-button">{{ __('pitmetric.studio.view_public') }}</a>
            @endif
        </div>

        @if ($errors->any())
            <div class="rounded-2xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger">
                <p class="font-bold">{{ __('pitmetric.studio.fix_errors') }}</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ $editing ? route('studio.updates.update', $update) : route('studio.updates.store') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            @csrf
            @if ($editing) @method('PUT') @endif
            <input type="hidden" name="primary_locale" value="{{ $primaryLocale }}">

            <div class="space-y-5">
                <section class="pm-elevated rounded-3xl p-5 sm:p-7">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="font-mono text-xs font-bold uppercase tracking-[0.16em] text-pm-accent">{{ $isItalian ? '🇮🇹 IT' : '🇬🇧 EN' }}</p>
                            <h2 class="mt-2 text-xl font-black text-pm-text">{{ __('pitmetric.studio.write_post') }}</h2>
                        </div>
                        <span class="rounded-full bg-pm-accent-subtle px-3 py-1 text-xs font-bold text-pm-accent">{{ __('pitmetric.studio.primary_language') }}</span>
                    </div>

                    <div class="mt-6 grid gap-5">
                        <label class="grid gap-2">
                            <span class="pm-label">{{ __('pitmetric.studio.post_title') }}</span>
                            <input name="primary_title" value="{{ $primaryTitle }}" maxlength="180" required class="pm-input !text-lg !font-bold" placeholder="{{ __('pitmetric.studio.title_placeholder') }}">
                        </label>
                        <label class="grid gap-2">
                            <span class="pm-label">{{ __('pitmetric.studio.content') }}</span>
                            <textarea name="primary_content" rows="13" class="pm-input resize-y" placeholder="{{ __('pitmetric.studio.content_placeholder_simple') }}">{{ $primaryContent }}</textarea>
                            <span class="pm-help">{{ __('pitmetric.studio.content_help_simple') }}</span>
                        </label>
                    </div>
                </section>

                <section class="pm-elevated rounded-3xl p-5 sm:p-7">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-mono text-xs font-bold uppercase tracking-[0.16em] text-pm-accent">MEDIA</p>
                            <h2 class="mt-2 text-xl font-black text-pm-text">{{ __('pitmetric.studio.add_media') }}</h2>
                            <p class="mt-2 text-sm leading-6 text-pm-text-secondary">{{ __('pitmetric.studio.media_help_simple') }}</p>
                        </div>
                        <span class="rounded-full border border-pm-border px-3 py-1 text-xs font-bold text-pm-muted">{{ __('pitmetric.studio.optional') }}</span>
                    </div>

                    @if ($editing && $update->mediaSource())
                        <div class="mt-5 flex items-center justify-between gap-4 rounded-2xl border border-pm-border bg-pm-subtle p-4">
                            <div class="min-w-0"><p class="text-xs font-bold uppercase tracking-[0.12em] text-pm-muted">{{ __('pitmetric.studio.current_media') }}</p><p class="mt-1 truncate text-sm text-pm-text-secondary">{{ $update->media_path ?? $update->media_url }}</p></div>
                            <label class="shrink-0 text-sm font-semibold text-pm-danger"><input type="checkbox" name="remove_media" value="1" class="mr-1 size-4 align-middle"> {{ __('pitmetric.studio.remove_media') }}</label>
                        </div>
                    @endif

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <label class="pm-upload-zone grid min-h-32 cursor-pointer place-items-center rounded-2xl p-5 text-center">
                            <div><div class="mx-auto grid size-10 place-items-center rounded-xl bg-pm-surface text-xl text-pm-accent">＋</div><p class="mt-3 font-bold text-pm-text">{{ __('pitmetric.studio.choose_file') }}</p><p class="mt-1 text-xs text-pm-muted">JPG · PNG · WEBP · GIF · MP4 · WEBM · MOV</p></div>
                            <input type="file" name="media" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime" class="sr-only">
                        </label>
                        <label class="grid content-start gap-2 rounded-2xl border border-pm-border bg-pm-subtle p-5">
                            <span class="pm-label">{{ __('pitmetric.studio.or_paste_link') }}</span>
                            <input type="url" name="media_url" value="{{ old('media_url', $update->media_url) }}" class="pm-input" placeholder="https://youtube.com/...">
                            <span class="pm-help">{{ __('pitmetric.studio.link_detect') }}</span>
                        </label>
                    </div>
                </section>

                <details class="pm-elevated rounded-3xl" @if($errors->has('translation_title') || $errors->has('translation_content')) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 sm:p-7">
                        <div><p class="font-mono text-xs font-bold uppercase tracking-[0.16em] text-pm-accent">{{ $isItalian ? '🇬🇧 EN' : '🇮🇹 IT' }}</p><h2 class="mt-2 text-lg font-black text-pm-text">{{ __('pitmetric.studio.add_translation', ['language' => $translationLabel]) }}</h2><p class="mt-1 text-sm text-pm-text-secondary">{{ __('pitmetric.studio.translation_optional') }}</p></div>
                        <span class="text-2xl text-pm-muted">＋</span>
                    </summary>
                    <div class="border-t border-pm-border p-5 sm:p-7">
                        <div class="grid gap-4">
                            <label class="grid gap-2"><span class="pm-label">{{ __('pitmetric.studio.post_title') }}</span><input name="translation_title" value="{{ $translationTitle }}" maxlength="180" class="pm-input"></label>
                            <label class="grid gap-2"><span class="pm-label">{{ __('pitmetric.studio.content') }}</span><textarea name="translation_content" rows="8" class="pm-input">{{ $translationContent }}</textarea></label>
                        </div>
                    </div>
                </details>

                <details class="pm-elevated rounded-3xl">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 sm:p-7"><div><h2 class="font-black text-pm-text">{{ __('pitmetric.studio.advanced') }}</h2><p class="mt-1 text-sm text-pm-text-secondary">{{ __('pitmetric.studio.advanced_copy') }}</p></div><span class="text-2xl text-pm-muted">＋</span></summary>
                    <div class="grid gap-4 border-t border-pm-border p-5 sm:p-7">
                        <label class="grid gap-2"><span class="pm-label">{{ __('pitmetric.studio.excerpt') }}</span><textarea name="primary_excerpt" rows="3" maxlength="500" class="pm-input">{{ $primaryExcerpt }}</textarea></label>
                        <label class="grid gap-2"><span class="pm-label">{{ __('pitmetric.studio.slug') }}</span><input name="slug" value="{{ old('slug', $update->slug) }}" maxlength="190" class="pm-input font-mono" placeholder="{{ __('pitmetric.studio.slug_placeholder') }}"><span class="pm-help">{{ __('pitmetric.studio.slug_help') }}</span></label>
                        <input type="hidden" name="translation_excerpt" value="{{ $translationExcerpt }}">
                    </div>
                </details>
            </div>

            <aside class="xl:sticky xl:top-6 xl:self-start">
                <section class="pm-race-card p-5 sm:p-6">
                    <p class="font-mono text-xs font-bold uppercase tracking-[0.16em] text-pm-accent">{{ __('pitmetric.studio.publish') }}</p>
                    <h2 class="mt-3 text-lg font-black text-pm-text">{{ __('pitmetric.studio.ready_question') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-pm-text-secondary">{{ __('pitmetric.studio.publish_help_simple') }}</p>
                    <div class="mt-6 grid gap-3">
                        <button type="submit" name="status" value="published" class="pm-race-button w-full justify-center">{{ __('pitmetric.studio.publish_now') }}</button>
                        <button type="submit" name="status" value="draft" class="pm-ghost-button w-full justify-center">{{ __('pitmetric.studio.save_draft') }}</button>
                    </div>
                    <div class="mt-5 border-t border-pm-border pt-4 text-xs leading-5 text-pm-muted">{{ __('pitmetric.studio.publication_note') }}</div>
                </section>
            </aside>
        </form>
    </div>
</x-layouts::app>
