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

        $currentMediaSource = $editing ? $update->mediaSource() : null;
        $currentMediaStored = $editing && filled($update->media_path);
        $currentMediaHost = $editing && filled($update->media_url) ? strtolower((string) parse_url($update->media_url, PHP_URL_HOST)) : null;
        $currentMediaProvider = match (true) {
            in_array($currentMediaHost, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be'], true) => 'YouTube',
            in_array($currentMediaHost, ['vimeo.com', 'www.vimeo.com'], true) => 'Vimeo',
            $currentMediaStored => __('pitmetric.studio.media_source_uploaded'),
            filled($currentMediaHost) => __('pitmetric.studio.media_source_external'),
            default => null,
        };
        $currentMediaLabel = $currentMediaStored ? $update->media_path : $update->media_url;
        $initialMediaUrl = (string) old('media_url', $update->media_url ?? '');
        $oldMediaUrlWasEntered = ! $editing && old('media_url') !== null && trim((string) old('media_url')) !== '';
    @endphp

    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1180px] space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <a href="{{ route('studio.updates.index') }}" class="text-sm font-semibold text-pm-text-secondary transition hover:text-pm-text">← {{ __('pitmetric.studio.back') }}</a>
                    <h1 class="mt-3 text-3xl font-black tracking-[-0.035em] text-pm-text">{{ $editing ? __('pitmetric.studio.edit_post') : __('pitmetric.studio.create_post') }}</h1>
                    <p class="mt-2 text-sm text-pm-text-secondary">{{ __('pitmetric.studio.composer_hint', ['language' => $isItalian ? 'Italiano' : 'English']) }}</p>
                </div>
                @if ($editing && $update->status === 'published')
                    <a href="{{ route('updates.show', $update) }}" target="_blank" class="pm-ghost-button">{{ __('pitmetric.studio.view_public') }}</a>
                @endif
            </div>

            @if ($errors->any())
                <div class="rounded-xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger">
                    <p class="font-bold">{{ __('pitmetric.studio.fix_errors') }}</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ $editing ? route('studio.updates.update', $update) : route('studio.updates.store') }}" enctype="multipart/form-data" class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_300px]">
                @csrf
                @if ($editing) @method('PUT') @endif
                <input type="hidden" name="primary_locale" value="{{ $primaryLocale }}">

                <div class="space-y-5">
                    <section class="pm-panel p-5 sm:p-7">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-pm-accent">{{ $isItalian ? '🇮🇹 IT' : '🇬🇧 EN' }}</p>
                                <h2 class="mt-2 text-xl font-black text-pm-text">{{ __('pitmetric.studio.write_post') }}</h2>
                            </div>
                            <span class="rounded-full border border-pm-border bg-pm-subtle px-3 py-1 text-xs font-semibold text-pm-muted">{{ __('pitmetric.studio.primary_language') }}</span>
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

                    <section
                        class="pm-panel p-5 sm:p-7"
                        x-data="{ fileName: '', fileKind: '', mediaUrl: @js($initialMediaUrl), mediaUrlChanged: @js($oldMediaUrlWasEntered), removeMedia: false }"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-pm-muted">Media</p>
                                <h2 class="mt-2 text-xl font-black text-pm-text">{{ __('pitmetric.studio.add_media') }}</h2>
                                <p class="mt-2 text-sm leading-6 text-pm-text-secondary">{{ __('pitmetric.studio.media_help_clear') }}</p>
                            </div>
                            <span class="rounded-full border border-pm-border px-3 py-1 text-xs font-semibold text-pm-muted">{{ __('pitmetric.studio.optional') }}</span>
                        </div>

                        <div class="mt-5 rounded-xl border border-pm-border bg-pm-subtle p-4" aria-live="polite">
                            <div x-show="removeMedia" x-cloak class="flex items-start gap-3">
                                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-pm-danger-subtle text-pm-danger">×</span>
                                <div><p class="font-bold text-pm-text">{{ __('pitmetric.studio.media_status_remove') }}</p><p class="mt-1 text-sm text-pm-text-secondary">{{ __('pitmetric.studio.media_status_remove_help') }}</p></div>
                            </div>
                            <div x-show="!removeMedia && fileName" x-cloak class="flex items-start gap-3">
                                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-pm-accent/10 text-pm-accent">✓</span>
                                <div class="min-w-0"><p class="font-bold text-pm-text">{{ __('pitmetric.studio.media_status_file_ready') }}</p><p class="mt-1 truncate text-sm text-pm-text-secondary" x-text="fileName"></p><p class="mt-1 text-xs text-pm-muted">{{ __('pitmetric.studio.media_status_file_help') }}</p></div>
                            </div>
                            <div x-show="!removeMedia && !fileName && mediaUrlChanged && mediaUrl" x-cloak class="flex items-start gap-3">
                                <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-pm-accent/10 text-pm-accent">↗</span>
                                <div class="min-w-0"><p class="font-bold text-pm-text">{{ __('pitmetric.studio.media_status_link_ready') }}</p><p class="mt-1 truncate text-sm text-pm-text-secondary" x-text="mediaUrl"></p><p class="mt-1 text-xs text-pm-muted">{{ __('pitmetric.studio.media_status_link_help') }}</p></div>
                            </div>

                            @if ($currentMediaSource)
                                <div x-show="!removeMedia && !fileName && !(mediaUrlChanged && mediaUrl)" class="flex items-start gap-3">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-lg bg-emerald-500/10 text-emerald-400">✓</span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-bold text-pm-text">{{ __('pitmetric.studio.media_status_current') }}</p>
                                            <span class="rounded-full border border-pm-border bg-pm-surface px-2 py-0.5 text-[11px] font-bold uppercase tracking-[0.08em] text-pm-muted">{{ $currentMediaProvider }}</span>
                                            <span class="rounded-full border border-pm-border bg-pm-surface px-2 py-0.5 text-[11px] font-bold uppercase tracking-[0.08em] text-pm-muted">{{ $update->media_type === 'video' ? __('pitmetric.studio.media_video') : __('pitmetric.studio.media_image') }}</span>
                                        </div>
                                        <p class="mt-1 truncate text-sm text-pm-text-secondary">{{ $currentMediaLabel }}</p>
                                        <p class="mt-1 text-xs text-pm-muted">{{ $currentMediaStored ? __('pitmetric.studio.media_current_uploaded_help') : __('pitmetric.studio.media_current_external_help') }}</p>
                                    </div>
                                </div>
                            @else
                                <div x-show="!removeMedia && !fileName && !(mediaUrlChanged && mediaUrl)" class="flex items-start gap-3">
                                    <span class="grid size-9 shrink-0 place-items-center rounded-lg border border-pm-border bg-pm-surface text-pm-muted">—</span>
                                    <div><p class="font-bold text-pm-text">{{ __('pitmetric.studio.media_status_empty') }}</p><p class="mt-1 text-sm text-pm-text-secondary">{{ __('pitmetric.studio.media_status_empty_help') }}</p></div>
                                </div>
                            @endif
                        </div>

                        @if ($currentMediaSource)
                            <div class="mt-4 overflow-hidden rounded-xl border border-pm-border bg-black/20">
                                @if ($update->media_type === 'image')
                                    <img src="{{ $currentMediaSource }}" alt="{{ $update->mediaAltForLocale() }}" class="max-h-72 w-full object-cover">
                                @elseif ($update->videoEmbedUrl())
                                    <div class="aspect-video"><iframe src="{{ $update->videoEmbedUrl() }}" title="{{ $update->mediaAltForLocale() }}" class="size-full" loading="lazy" allowfullscreen></iframe></div>
                                @else
                                    <video src="{{ $currentMediaSource }}" controls class="max-h-72 w-full bg-black"></video>
                                @endif
                            </div>
                        @endif

                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                            <label class="pm-upload-zone grid min-h-40 cursor-pointer content-center rounded-xl p-5 text-center transition" :class="fileName ? 'border-pm-accent bg-pm-accent/5' : ''">
                                <div>
                                    <div class="mx-auto grid size-10 place-items-center rounded-lg border border-pm-border bg-pm-surface text-xl text-pm-accent">↑</div>
                                    <p class="mt-3 font-bold text-pm-text">{{ __('pitmetric.studio.upload_from_device') }}</p>
                                    <p class="mt-1 text-xs leading-5 text-pm-muted">{{ __('pitmetric.studio.upload_from_device_help') }}</p>
                                    <p x-show="fileName" x-cloak class="mt-3 truncate rounded-lg bg-pm-accent/10 px-3 py-2 text-xs font-bold text-pm-accent" x-text="fileName"></p>
                                </div>
                                <input
                                    x-ref="mediaFile"
                                    type="file"
                                    name="media"
                                    accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime"
                                    class="sr-only"
                                    @change="fileName = $event.target.files[0]?.name || ''; fileKind = $event.target.files[0]?.type || ''; if (fileName) { mediaUrl = ''; mediaUrlChanged = true; $refs.mediaUrl.value = ''; removeMedia = false; if ($refs.removeMedia) $refs.removeMedia.checked = false; }"
                                >
                            </label>

                            <div class="grid content-start gap-3 rounded-xl border border-pm-border bg-pm-subtle p-5 transition" :class="mediaUrlChanged && mediaUrl ? 'border-pm-accent bg-pm-accent/5' : ''">
                                <div>
                                    <div class="grid size-10 place-items-center rounded-lg border border-pm-border bg-pm-surface text-lg text-pm-accent">↗</div>
                                    <p class="mt-3 font-bold text-pm-text">{{ __('pitmetric.studio.use_external_link') }}</p>
                                    <p class="mt-1 text-xs leading-5 text-pm-muted">{{ __('pitmetric.studio.use_external_link_help') }}</p>
                                </div>
                                <input
                                    x-ref="mediaUrl"
                                    x-model="mediaUrl"
                                    type="url"
                                    name="media_url"
                                    value="{{ $initialMediaUrl }}"
                                    class="pm-input"
                                    placeholder="https://youtube.com/..."
                                    @input="mediaUrlChanged = true; if (mediaUrl) { fileName = ''; $refs.mediaFile.value = ''; removeMedia = false; if ($refs.removeMedia) $refs.removeMedia.checked = false; }"
                                >
                                <span class="pm-help">{{ __('pitmetric.studio.link_detect') }}</span>
                            </div>
                        </div>

                        @if ($currentMediaSource)
                            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-pm-border pt-4">
                                <p class="text-xs text-pm-muted">{{ __('pitmetric.studio.media_replace_help') }}</p>
                                <label class="text-sm font-semibold text-pm-danger">
                                    <input x-ref="removeMedia" x-model="removeMedia" type="checkbox" name="remove_media" value="1" class="mr-1 size-4 align-middle">
                                    {{ __('pitmetric.studio.remove_media') }}
                                </label>
                            </div>
                        @endif
                    </section>

                    <details class="pm-panel" @if($errors->has('translation_title') || $errors->has('translation_content')) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 sm:p-6">
                            <div><p class="text-xs font-semibold uppercase tracking-[0.14em] text-pm-muted">{{ $isItalian ? '🇬🇧 EN' : '🇮🇹 IT' }}</p><h2 class="mt-2 text-lg font-black text-pm-text">{{ __('pitmetric.studio.add_translation', ['language' => $translationLabel]) }}</h2><p class="mt-1 text-sm text-pm-text-secondary">{{ __('pitmetric.studio.translation_optional') }}</p></div>
                            <span class="text-xl text-pm-muted">＋</span>
                        </summary>
                        <div class="border-t border-pm-border p-5 sm:p-6">
                            <div class="grid gap-4">
                                <label class="grid gap-2"><span class="pm-label">{{ __('pitmetric.studio.post_title') }}</span><input name="translation_title" value="{{ $translationTitle }}" maxlength="180" class="pm-input"></label>
                                <label class="grid gap-2"><span class="pm-label">{{ __('pitmetric.studio.content') }}</span><textarea name="translation_content" rows="8" class="pm-input">{{ $translationContent }}</textarea></label>
                            </div>
                        </div>
                    </details>

                    <details class="pm-panel">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 sm:p-6"><div><h2 class="font-black text-pm-text">{{ __('pitmetric.studio.advanced') }}</h2><p class="mt-1 text-sm text-pm-text-secondary">{{ __('pitmetric.studio.advanced_copy') }}</p></div><span class="text-xl text-pm-muted">＋</span></summary>
                        <div class="grid gap-4 border-t border-pm-border p-5 sm:p-6">
                            <label class="grid gap-2"><span class="pm-label">{{ __('pitmetric.studio.excerpt') }}</span><textarea name="primary_excerpt" rows="3" maxlength="500" class="pm-input">{{ $primaryExcerpt }}</textarea></label>
                            <label class="grid gap-2"><span class="pm-label">{{ __('pitmetric.studio.slug') }}</span><input name="slug" value="{{ old('slug', $update->slug) }}" maxlength="190" class="pm-input font-mono" placeholder="{{ __('pitmetric.studio.slug_placeholder') }}"><span class="pm-help">{{ __('pitmetric.studio.slug_help') }}</span></label>
                            <input type="hidden" name="translation_excerpt" value="{{ $translationExcerpt }}">
                        </div>
                    </details>
                </div>

                <aside class="xl:sticky xl:top-6 xl:self-start">
                    <section class="pm-panel p-5 sm:p-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-pm-muted">{{ __('pitmetric.studio.publish') }}</p>
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
    </div>
</x-layouts::app>
