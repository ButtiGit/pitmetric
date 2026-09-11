<x-layouts::app :title="$update->exists ? __('pitmetric.studio.edit_post') : __('pitmetric.studio.create_post')">
    @php($editing = $update->exists)
    <div class="pitmetric-app pm-page flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <a href="{{ route('studio.updates.index') }}" class="text-sm font-semibold text-pm-text-secondary hover:text-pm-text">← {{ __('pitmetric.studio.back') }}</a>
                <h1 class="mt-3 text-3xl font-black tracking-[-0.035em] text-pm-text">{{ $editing ? __('pitmetric.studio.edit_post') : __('pitmetric.studio.create_post') }}</h1>
            </div>
            @if ($editing && $update->status === 'published')
                <a href="{{ route('updates.show', $update) }}" target="_blank" class="pm-ghost-button">{{ __('pitmetric.studio.view_public') }}</a>
            @endif
        </div>

        @if ($errors->any())
            <div class="rounded-2xl border border-pm-danger/30 bg-pm-danger-subtle p-4 text-sm text-pm-danger">
                <p class="font-bold">{{ __('pitmetric.studio.fix_errors') }}</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ $editing ? route('studio.updates.update', $update) : route('studio.updates.store') }}" enctype="multipart/form-data" class="grid gap-6 xl:grid-cols-[1fr_360px]">
            @csrf
            @if ($editing) @method('PUT') @endif

            <div class="space-y-6">
                <section class="pm-elevated rounded-3xl p-6 sm:p-8">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="font-mono text-xs font-bold uppercase tracking-[0.18em] text-pm-accent">EN</p>
                            <h2 class="mt-2 text-xl font-black text-pm-text">{{ __('pitmetric.studio.english_content') }}</h2>
                        </div>
                        <span class="rounded-full bg-pm-accent-subtle px-3 py-1 text-xs font-bold text-pm-accent">{{ __('pitmetric.studio.required') }}</span>
                    </div>
                    <div class="mt-6 grid gap-5">
                        <label class="grid gap-2"><span class="pm-label">{{ __('pitmetric.studio.post_title') }}</span><input name="title" value="{{ old('title', $update->title) }}" maxlength="180" required class="pm-input" placeholder="PitMetric beta: configuration snapshots"></label>
                        <label class="grid gap-2"><span class="pm-label">{{ __('pitmetric.studio.slug') }}</span><input name="slug" value="{{ old('slug', $update->slug) }}" maxlength="190" class="pm-input font-mono" placeholder="{{ __('pitmetric.studio.slug_placeholder') }}"><span class="pm-help">{{ __('pitmetric.studio.slug_help') }}</span></label>
                        <label class="grid gap-2"><span class="pm-label">{{ __('pitmetric.studio.excerpt') }}</span><textarea name="excerpt" rows="3" maxlength="500" class="pm-input" placeholder="{{ __('pitmetric.studio.excerpt_placeholder') }}">{{ old('excerpt', $update->excerpt) }}</textarea><span class="pm-help">{{ __('pitmetric.studio.excerpt_help') }}</span></label>
                        <label class="grid gap-2"><span class="pm-label">{{ __('pitmetric.studio.content') }}</span><textarea name="content" rows="12" class="pm-input" placeholder="{{ __('pitmetric.studio.content_placeholder') }}">{{ old('content', $update->content) }}</textarea><span class="pm-help">{{ __('pitmetric.studio.content_help') }}</span></label>
                    </div>
                </section>

                <details class="pm-elevated rounded-3xl" {{ old('title_it', $update->title_it) || old('content_it', $update->content_it) ? 'open' : '' }}>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-6 sm:p-8">
                        <div><p class="font-mono text-xs font-bold uppercase tracking-[0.18em] text-pm-accent">IT</p><h2 class="mt-2 text-xl font-black text-pm-text">{{ __('pitmetric.studio.italian_content') }}</h2><p class="mt-2 text-sm text-pm-text-secondary">{{ __('pitmetric.studio.translation_help') }}</p></div>
                        <span class="text-2xl text-pm-muted">+</span>
                    </summary>
                    <div class="border-t border-pm-border p-6 sm:p-8">
                        <div class="grid gap-5">
                            <label class="grid gap-2"><span class="pm-label">{{ __('pitmetric.studio.post_title') }}</span><input name="title_it" value="{{ old('title_it', $update->title_it) }}" maxlength="180" class="pm-input"></label>
                            <label class="grid gap-2"><span class="pm-label">{{ __('pitmetric.studio.excerpt') }}</span><textarea name="excerpt_it" rows="3" maxlength="500" class="pm-input">{{ old('excerpt_it', $update->excerpt_it) }}</textarea></label>
                            <label class="grid gap-2"><span class="pm-label">{{ __('pitmetric.studio.content') }}</span><textarea name="content_it" rows="10" class="pm-input">{{ old('content_it', $update->content_it) }}</textarea></label>
                        </div>
                    </div>
                </details>

                <section class="pm-elevated rounded-3xl p-6 sm:p-8">
                    <div><p class="font-mono text-xs font-bold uppercase tracking-[0.18em] text-pm-accent">MEDIA</p><h2 class="mt-2 text-xl font-black text-pm-text">{{ __('pitmetric.studio.media') }}</h2><p class="mt-2 text-sm leading-6 text-pm-text-secondary">{{ __('pitmetric.studio.media_help') }}</p></div>

                    @if ($editing && $update->mediaSource())
                        <div class="mt-6 overflow-hidden rounded-2xl border border-pm-border bg-pm-subtle p-4">
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-pm-muted">{{ __('pitmetric.studio.current_media') }}</p>
                            <p class="mt-2 truncate text-sm text-pm-text-secondary">{{ $update->media_path ?? $update->media_url }}</p>
                            <label class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-pm-danger"><input type="checkbox" name="remove_media" value="1" class="size-4"> {{ __('pitmetric.studio.remove_media') }}</label>
                        </div>
                    @endif

                    <div class="mt-6 grid gap-5 lg:grid-cols-2">
                        <label class="pm-upload-zone grid min-h-40 cursor-pointer place-items-center rounded-2xl p-6 text-center">
                            <div><p class="font-bold text-pm-text">{{ __('pitmetric.studio.upload_media') }}</p><p class="mt-2 text-xs leading-5 text-pm-muted">JPG, PNG, WEBP, GIF, MP4, WEBM, MOV · max 50 MB</p></div>
                            <input type="file" name="media" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime" class="sr-only">
                        </label>
                        <div class="grid gap-4">
                            <label class="grid gap-2"><span class="pm-label">{{ __('pitmetric.studio.external_url') }}</span><input type="url" name="media_url" value="{{ old('media_url', $update->media_url) }}" class="pm-input" placeholder="https://..."></label>
                            <label class="grid gap-2"><span class="pm-label">{{ __('pitmetric.studio.media_type') }}</span><select name="media_type" class="pm-input"><option value="">—</option><option value="image" @selected(old('media_type', $update->media_type) === 'image')>{{ __('pitmetric.studio.image') }}</option><option value="video" @selected(old('media_type', $update->media_type) === 'video')>{{ __('pitmetric.studio.video') }}</option></select></label>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-5 md:grid-cols-2">
                        <label class="grid gap-2"><span class="pm-label">{{ __('pitmetric.studio.alt_en') }}</span><input name="media_alt" value="{{ old('media_alt', $update->media_alt) }}" maxlength="255" class="pm-input"></label>
                        <label class="grid gap-2"><span class="pm-label">{{ __('pitmetric.studio.alt_it') }}</span><input name="media_alt_it" value="{{ old('media_alt_it', $update->media_alt_it) }}" maxlength="255" class="pm-input"></label>
                    </div>
                </section>
            </div>

            <aside class="xl:sticky xl:top-6 xl:self-start">
                <section class="pm-elevated rounded-3xl p-6">
                    <p class="font-mono text-xs font-bold uppercase tracking-[0.18em] text-pm-accent">{{ __('pitmetric.studio.publish') }}</p>
                    <div class="mt-5 space-y-4 text-sm text-pm-text-secondary">
                        <div class="flex items-center justify-between gap-4"><span>{{ __('pitmetric.studio.status') }}</span><span class="font-bold text-pm-text">{{ $editing ? __('pitmetric.studio.'.$update->status) : __('pitmetric.studio.draft') }}</span></div>
                        <div class="h-px bg-pm-border"></div>
                        <p class="leading-6">{{ __('pitmetric.studio.publish_help') }}</p>
                    </div>
                    <div class="mt-6 grid gap-3">
                        <button type="submit" name="status" value="published" class="pm-race-button w-full justify-center">{{ __('pitmetric.studio.publish_now') }}</button>
                        <button type="submit" name="status" value="draft" class="pm-ghost-button w-full justify-center">{{ __('pitmetric.studio.save_draft') }}</button>
                        <a href="{{ route('studio.updates.index') }}" class="text-center text-sm font-semibold text-pm-muted hover:text-pm-text">{{ __('pitmetric.studio.cancel') }}</a>
                    </div>
                </section>
            </aside>
        </form>
    </div>
</x-layouts::app>
