<x-layouts::app title="PitMetric Mail">
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[1080px] space-y-5">
            @if (session('status'))
                <div class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('studio.mail.index') }}" class="pm-ghost-button">Indietro all'Inbox</a>
                <a href="{{ route('studio.outreach.index') }}" class="pm-ghost-button">Nuova pubblicità</a>
            </div>

            <section class="pm-panel overflow-hidden">
                <div class="border-b border-pm-border px-6 py-6 sm:px-8">
                    <div class="text-xs font-semibold uppercase tracking-[0.14em] text-pm-accent">Email ricevuta</div>
                    <h1 class="mt-3 break-words text-2xl font-black tracking-[-0.025em] text-pm-text sm:text-3xl">{{ trim((string) ($email['subject'] ?? '')) ?: '(senza oggetto)' }}</h1>
                    <div class="mt-5 grid gap-2 text-sm text-pm-text-secondary sm:grid-cols-[90px_1fr]">
                        <div class="font-semibold text-pm-muted">Da</div><div class="break-all">{{ $email['from'] ?? '' }}</div>
                        <div class="font-semibold text-pm-muted">A</div><div class="break-all">{{ implode(', ', is_array($email['to'] ?? null) ? $email['to'] : []) }}</div>
                        @if (! empty($email['cc']))
                            <div class="font-semibold text-pm-muted">CC</div><div class="break-all">{{ implode(', ', is_array($email['cc']) ? $email['cc'] : []) }}</div>
                        @endif
                        <div class="font-semibold text-pm-muted">Ricevuta</div><div>{{ ! empty($email['created_at']) ? \Illuminate\Support\Carbon::parse((string) $email['created_at'])->format('d/m/Y H:i') : '' }}</div>
                    </div>
                </div>

                <div class="px-6 py-7 sm:px-8">
                    <div class="whitespace-pre-wrap break-words text-[15px] leading-7 text-pm-text-secondary">{{ $body !== '' ? $body : 'Il messaggio non contiene una versione testuale leggibile.' }}</div>

                    @if (! empty($email['attachments']) && is_array($email['attachments']))
                        <div class="mt-7 border-t border-pm-border pt-5">
                            <div class="text-xs font-semibold uppercase tracking-[0.12em] text-pm-muted">Allegati</div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($email['attachments'] as $attachment)
                                    <span class="rounded-lg border border-pm-border bg-pm-subtle px-3 py-2 text-xs font-semibold text-pm-text-secondary">{{ $attachment['filename'] ?? 'Allegato' }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            <section class="pm-panel p-6 sm:p-8">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-[0.14em] text-pm-success">Risposta diretta</div>
                        <h2 class="mt-2 text-2xl font-black text-pm-text">Rispondi da PitMetric</h2>
                        <p class="mt-2 text-sm text-pm-text-secondary">La risposta sarà brandizzata e le successive risposte torneranno nell'Inbox Studio.</p>
                    </div>
                    <div class="text-xs text-pm-muted">A: <span class="font-semibold text-pm-text-secondary">{{ $replyAddress ?? 'indirizzo non disponibile' }}</span></div>
                </div>

                <form method="POST" action="{{ route('studio.mail.reply', (string) ($email['id'] ?? '')) }}" class="mt-6 space-y-5">
                    @csrf
                    <div>
                        <label for="subject" class="text-sm font-bold text-pm-text">Oggetto</label>
                        @php $replySubject = str_starts_with(strtolower((string) ($email['subject'] ?? '')), 're:') ? (string) ($email['subject'] ?? '') : 'Re: '.((string) ($email['subject'] ?? '')); @endphp
                        <input id="subject" name="subject" required maxlength="180" value="{{ old('subject', $replySubject) }}" class="mt-2 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm text-pm-text outline-none transition focus:border-pm-accent">
                        @error('subject')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="message" class="text-sm font-bold text-pm-text">Messaggio</label>
                        <textarea id="message" name="message" rows="8" required maxlength="12000" class="mt-2 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm leading-6 text-pm-text outline-none transition focus:border-pm-accent" placeholder="Scrivi la risposta...">{{ old('message') }}</textarea>
                        @error('message')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                    </div>
                    <div class="flex justify-end border-t border-pm-border pt-5">
                        <button type="submit" class="pm-race-button" @disabled($replyAddress === null)>Invia risposta</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-layouts::app>
