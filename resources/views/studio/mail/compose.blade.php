<x-layouts::app title="Nuova mail PitMetric">
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 sm:px-6 lg:px-8 lg:py-7">
        <div class="mx-auto w-full max-w-[980px] space-y-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('studio.mail.index') }}" class="pm-ghost-button">Inbox</a>
                <a href="{{ route('studio.outreach.index') }}" class="pm-ghost-button">Outreach pubblicitario</a>
            </div>

            <section class="pm-panel p-6 sm:p-8">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.14em] text-pm-accent">Studio / Mail</div>
                    <h1 class="mt-3 text-3xl font-black tracking-[-0.035em] text-pm-text">Nuova mail</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-pm-text-secondary">Scrivi una normale email PitMetric a un contatto. Per presentazioni commerciali e pubblicità usa invece Outreach, che include formati, anteprima e opt-out.</p>
                </div>

                <form method="POST" action="{{ route('studio.mail.send') }}" class="mt-7 space-y-5">
                    @csrf
                    <div>
                        <label for="to" class="text-sm font-bold text-pm-text">A</label>
                        <input id="to" name="to" type="email" required maxlength="254" value="{{ old('to') }}" class="mt-2 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm text-pm-text outline-none transition focus:border-pm-accent" placeholder="contatto@example.com">
                        @error('to')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="subject" class="text-sm font-bold text-pm-text">Oggetto</label>
                        <input id="subject" name="subject" required maxlength="180" value="{{ old('subject') }}" class="mt-2 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm text-pm-text outline-none transition focus:border-pm-accent" placeholder="Oggetto della mail">
                        @error('subject')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="message" class="text-sm font-bold text-pm-text">Messaggio</label>
                        <textarea id="message" name="message" rows="10" required maxlength="12000" class="mt-2 w-full rounded-xl border border-pm-border bg-pm-subtle px-4 py-3 text-sm leading-6 text-pm-text outline-none transition focus:border-pm-accent" placeholder="Scrivi il messaggio...">{{ old('message') }}</textarea>
                        @error('message')<p class="mt-2 text-sm font-semibold text-pm-danger">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex flex-col gap-3 border-t border-pm-border pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="text-xs leading-5 text-pm-muted">
                            Da: <span class="font-semibold text-pm-text-secondary">{{ config('services.resend.studio_from_name') }} &lt;{{ config('services.resend.studio_from_address') }}&gt;</span><br>
                            Risposte: <span class="font-mono text-pm-text-secondary">{{ config('services.resend.studio_reply_to') }}</span>
                        </div>
                        <button type="submit" class="pm-race-button">Invia mail</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-layouts::app>
