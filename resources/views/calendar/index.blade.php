<x-layouts::app title="Calendario | PitMetric">
    @php
        $it = app()->getLocale() === 'it';
        $defaultStart = $selectedDay
            ? \Illuminate\Support\Carbon::parse($selectedDay.' 09:00', $timezone)
            : now($timezone)->addHour()->startOfHour();
        $defaultEnd = $defaultStart->copy()->addHour();
    @endphp
    <div class="pitmetric-app min-h-full w-full bg-pm-page px-4 py-5 pb-32 sm:px-6 lg:px-8 lg:py-7 lg:pb-7" data-pm-workspace>
        <div class="mx-auto w-full max-w-[1420px] space-y-5">
            <section class="pm-panel flex flex-col gap-5 p-5 sm:p-7 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-pm-accent">{{ $it ? 'Gestione / Calendario' : 'Management / Calendar' }}</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-pm-text sm:text-4xl">{{ $it ? 'Il tuo calendario' : 'Your calendar' }}</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-pm-text-secondary">{{ $it ? 'Impegni, incontri e follow-up. Gli eventi sono privati del tuo account, anche quando nascono da una mail dello Studio.' : 'Appointments, meetings and follow-ups. Events are private to your account, including those linked to Studio emails.' }}</p>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <x-crud-modal id="create-calendar-entry" :permission="null" :title="$it ? 'Nuovo evento' : 'New event'" :description="$sourceMail ? ($it ? 'Collega questo impegno al messaggio selezionato.' : 'Link this appointment to the selected message.') : ($it ? 'Scegli data, ora e dettagli. Potrai modificarli in seguito.' : 'Choose the date, time and details.')" :trigger="$it ? '+ Nuovo evento' : '+ New event'">
                        @if ($sourceMail)
                            <div class="mb-4 rounded-xl border border-pm-accent/25 bg-pm-accent/10 p-4 text-sm text-pm-text-secondary">
                                <div class="text-xs font-bold uppercase tracking-[0.12em] text-pm-accent">{{ $sourceMail->direction === 'sent' ? 'Mail inviata' : 'Mail ricevuta' }}</div>
                                <p class="mt-1 break-words font-semibold text-pm-text">{{ $sourceMail->subject ?: '(senza oggetto)' }}</p>
                                <a class="mt-2 inline-block text-xs font-semibold text-pm-accent underline underline-offset-4" href="{{ route('studio.mail.show', ['emailId' => $sourceMail->provider_id, 'direction' => $sourceMail->direction]) }}">{{ $it ? 'Apri conversazione' : 'Open conversation' }}</a>
                            </div>
                        @endif
                        <form method="POST" action="{{ route('calendar.store') }}" class="grid gap-4 sm:grid-cols-2">
                            @csrf
                            @if ($sourceMail)<input type="hidden" name="studio_mail_message_id" value="{{ $sourceMail->id }}">@endif
                            <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ $it ? 'Titolo *' : 'Title *' }}</span><input class="pm-input" name="title" required maxlength="180" value="{{ old('title', $sourceMail ? \Illuminate\Support\Str::limit($sourceMail->subject ?: 'Follow-up email', 180, '') : '') }}" placeholder="{{ $it ? 'Es. Riunione con il team' : 'E.g. Team meeting' }}"></label>
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Inizio *' : 'Starts *' }}</span><input class="pm-input" type="datetime-local" name="starts_at" required value="{{ old('starts_at', $defaultStart->format('Y-m-d\TH:i')) }}"></label>
                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Fine *' : 'Ends *' }}</span><input class="pm-input" type="datetime-local" name="ends_at" required value="{{ old('ends_at', $defaultEnd->format('Y-m-d\TH:i')) }}"></label>
                            <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ $it ? 'Luogo (facoltativo)' : 'Location (optional)' }}</span><input class="pm-input" name="location" maxlength="200" value="{{ old('location') }}" placeholder="{{ $it ? 'Online, circuito, sede...' : 'Online, circuit, office...' }}"></label>
                            <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><textarea class="pm-input min-h-28" name="description" maxlength="10000" placeholder="{{ $it ? 'Argomenti, promemoria, attività da preparare...' : 'Agenda, reminders, preparation...' }}">{{ old('description') }}</textarea></label>
                            <p class="text-xs text-pm-muted sm:col-span-2">{{ $it ? 'Fuso orario' : 'Timezone' }}: {{ $timezone }}</p>
                            <div class="flex justify-end sm:col-span-2"><button class="pm-race-button" type="submit">{{ $it ? 'Salva evento' : 'Save event' }}</button></div>
                        </form>
                    </x-crud-modal>
                </div>
            </section>

            @if (session('status'))<div role="status" class="rounded-xl border border-pm-success/25 bg-pm-success-subtle px-4 py-3 text-sm font-semibold text-pm-success">{{ session('status') }}</div>@endif
            @if ($errors->any())<div role="alert" class="rounded-xl border border-pm-danger/25 bg-pm-danger-subtle px-4 py-3 text-sm text-pm-danger"><strong>{{ $it ? 'Controlla i dati inseriti.' : 'Check the submitted details.' }}</strong><ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_370px]">
                <section class="pm-panel min-w-0 overflow-hidden">
                    <header class="flex flex-col gap-4 border-b border-pm-border p-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-5">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-muted">{{ $it ? 'Vista mensile' : 'Monthly view' }}</p>
                            <h2 class="mt-1 text-2xl font-black capitalize text-pm-text">{{ $monthStart->translatedFormat('F Y') }}</h2>
                            <p class="mt-1 text-xs text-pm-muted">{{ $entries->filter(fn ($entry) => $entry->starts_at->copy()->timezone($timezone)->format('Y-m') === $month)->count() }} {{ $it ? 'eventi nel mese' : 'events this month' }}</p>
                        </div>
                        <nav class="flex items-center gap-2" aria-label="{{ $it ? 'Cambia mese' : 'Change month' }}">
                            <a class="pm-ghost-button" href="{{ route('calendar.index', ['month' => $monthStart->copy()->subMonth()->format('Y-m')]) }}" aria-label="{{ $it ? 'Mese precedente' : 'Previous month' }}"><flux:icon.chevron-left class="size-4" /></a>
                            <a class="pm-ghost-button" href="{{ route('calendar.index') }}">{{ $it ? 'Oggi' : 'Today' }}</a>
                            <a class="pm-ghost-button" href="{{ route('calendar.index', ['month' => $monthStart->copy()->addMonth()->format('Y-m')]) }}" aria-label="{{ $it ? 'Mese successivo' : 'Next month' }}"><flux:icon.chevron-right class="size-4" /></a>
                        </nav>
                    </header>

                    <div class="grid grid-cols-7 border-b border-pm-border bg-pm-subtle/60 text-center text-[10px] font-bold uppercase tracking-[0.08em] text-pm-muted sm:text-xs">
                        @foreach (['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'] as $weekday)<div class="py-3">{{ $weekday }}</div>@endforeach
                    </div>
                    <div class="grid grid-cols-7 gap-px bg-pm-border">
                        @for ($date = $gridStart->copy(); $date->lte($gridEnd); $date->addDay())
                            @php
                                $key = $date->toDateString();
                                $dayEntries = $entriesByDay->get($key, collect());
                                $isCurrentMonth = $date->format('Y-m') === $month;
                                $isSelected = $selectedDay === $key;
                            @endphp
                            <a href="{{ route('calendar.index', ['month' => $month, 'day' => $key]) }}" @class(['group min-h-17 min-w-0 bg-pm-panel p-1.5 transition hover:bg-pm-subtle sm:min-h-29 sm:p-3', 'opacity-35' => ! $isCurrentMonth, 'bg-pm-accent/10 ring-1 ring-inset ring-pm-accent' => $isSelected]) aria-label="{{ $date->translatedFormat('l d F') }} · {{ $dayEntries->count() }} {{ $it ? 'eventi' : 'events' }}" @if ($isSelected) aria-current="date" @endif>
                                <span @class(['inline-grid size-7 place-items-center rounded-full text-xs font-bold sm:size-8 sm:text-sm', 'bg-pm-accent text-white' => $date->isToday(), 'text-pm-text-secondary' => ! $date->isToday()])>{{ $date->day }}</span>
                                @if ($dayEntries->isNotEmpty())
                                    <span class="mt-1 flex flex-wrap items-center gap-1 sm:hidden"><span class="size-1.5 rounded-full bg-pm-accent"></span><span class="text-[10px] font-bold text-pm-text">{{ $dayEntries->count() }}</span></span>
                                    <span class="mt-1 hidden space-y-1 sm:block">
                                        @foreach ($dayEntries->take(2) as $event)
                                            <span class="block truncate rounded-md border border-pm-accent/20 bg-pm-accent/10 px-1.5 py-1 text-[11px] font-semibold text-pm-text" title="{{ $event->title }}">{{ $event->starts_at->copy()->timezone($timezone)->format('H:i') }} {{ $event->title }}</span>
                                        @endforeach
                                        @if ($dayEntries->count() > 2)<span class="block text-[10px] font-bold text-pm-muted">+{{ $dayEntries->count() - 2 }} {{ $it ? 'altri' : 'more' }}</span>@endif
                                    </span>
                                @endif
                            </a>
                        @endfor
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-pm-border px-4 py-3 text-xs text-pm-muted sm:px-6">
                        <span>{{ $it ? 'Seleziona un giorno per vedere gli appuntamenti.' : 'Select a day to view appointments.' }}</span>
                        @if ($selectedDay)<a class="font-semibold text-pm-accent underline underline-offset-4" href="{{ route('calendar.index', ['month' => $month]) }}">{{ $it ? 'Mostra tutto il mese' : 'Show full month' }}</a>@endif
                    </div>
                </section>

                <section class="pm-panel min-w-0 overflow-hidden">
                    <header class="border-b border-pm-border px-5 py-5">
                        <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-pm-accent">Agenda</p>
                        <h2 class="mt-1 text-xl font-black text-pm-text">{{ $selectedDay ? \Illuminate\Support\Carbon::parse($selectedDay)->translatedFormat('d F Y') : ($it ? 'Eventi del mese' : 'Monthly events') }}</h2>
                        <p class="mt-1 text-xs text-pm-muted">{{ $agenda->count() }} {{ $it ? 'impegni in programma' : 'scheduled appointments' }}</p>
                    </header>
                    <div class="max-h-[720px] divide-y divide-pm-border overflow-y-auto">
                        @forelse ($agenda as $entry)
                            <article class="p-5" data-calendar-entry="{{ $entry->id }}">
                                <div class="flex min-w-0 gap-3">
                                    <div class="shrink-0 rounded-xl border border-pm-border bg-pm-subtle px-3 py-2 text-center">
                                        <div class="text-lg font-black text-pm-text">{{ $entry->starts_at->copy()->timezone($timezone)->format('d') }}</div>
                                        <div class="text-[10px] font-bold uppercase text-pm-muted">{{ $entry->starts_at->copy()->timezone($timezone)->translatedFormat('M') }}</div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="break-words text-sm font-bold text-pm-text [overflow-wrap:anywhere]">{{ $entry->title }}</p>
                                        <p class="mt-1 text-xs font-semibold text-pm-accent">{{ $entry->starts_at->copy()->timezone($timezone)->format('H:i') }}–{{ $entry->ends_at->copy()->timezone($timezone)->format('H:i') }}</p>
                                        @if ($entry->location)<p class="mt-1 break-words text-xs text-pm-text-secondary"><flux:icon.map-pin class="mr-1 inline size-3" />{{ $entry->location }}</p>@endif
                                    </div>
                                </div>
                                @if ($entry->description)<p class="mt-3 whitespace-pre-wrap break-words text-xs leading-5 text-pm-text-secondary [overflow-wrap:anywhere]">{{ $entry->description }}</p>@endif
                                @if ($entry->sourceMail && auth()->user()->can('manage-updates'))
                                    <a href="{{ route('studio.mail.show', ['emailId' => $entry->sourceMail->provider_id, 'direction' => $entry->sourceMail->direction]) }}" class="mt-3 inline-flex items-center gap-1.5 break-all text-xs font-semibold text-pm-accent hover:underline"><flux:icon.envelope class="size-4 shrink-0" />{{ $it ? 'Apri email collegata' : 'Open linked email' }}</a>
                                @endif
                                <div class="mt-4 flex flex-wrap items-center gap-2">
                                    <x-crud-modal id="edit-calendar-entry-{{ $entry->id }}" :permission="null" :title="$it ? 'Modifica evento' : 'Edit event'" :trigger="$it ? 'Modifica' : 'Edit'" trigger-class="pm-ghost-button">
                                        <form method="POST" action="{{ route('calendar.update', $entry) }}" class="grid gap-4 sm:grid-cols-2">
                                            @csrf @method('PUT')
                                            <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ $it ? 'Titolo *' : 'Title *' }}</span><input class="pm-input" name="title" required maxlength="180" value="{{ $entry->title }}"></label>
                                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Inizio *' : 'Starts *' }}</span><input class="pm-input" type="datetime-local" name="starts_at" required value="{{ $entry->starts_at->copy()->timezone($timezone)->format('Y-m-d\TH:i') }}"></label>
                                            <label class="grid gap-2"><span class="pm-label">{{ $it ? 'Fine *' : 'Ends *' }}</span><input class="pm-input" type="datetime-local" name="ends_at" required value="{{ $entry->ends_at->copy()->timezone($timezone)->format('Y-m-d\TH:i') }}"></label>
                                            <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ $it ? 'Luogo' : 'Location' }}</span><input class="pm-input" name="location" maxlength="200" value="{{ $entry->location }}"></label>
                                            <label class="grid gap-2 sm:col-span-2"><span class="pm-label">{{ $it ? 'Note' : 'Notes' }}</span><textarea class="pm-input min-h-28" name="description" maxlength="10000">{{ $entry->description }}</textarea></label>
                                            <div class="flex justify-end sm:col-span-2"><button class="pm-race-button" type="submit">{{ $it ? 'Salva modifiche' : 'Save changes' }}</button></div>
                                        </form>
                                    </x-crud-modal>
                                    <form method="POST" action="{{ route('calendar.destroy', $entry) }}" onsubmit="return confirm(@js($it ? 'Eliminare questo evento?' : 'Delete this event?'))">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs font-bold text-pm-danger underline underline-offset-4">{{ $it ? 'Elimina' : 'Delete' }}</button>
                                    </form>
                                </div>
                            </article>
                        @empty
                            <div class="px-6 py-14 text-center">
                                <flux:icon.calendar-days class="mx-auto size-8 text-pm-muted" />
                                <h3 class="mt-3 text-sm font-bold text-pm-text">{{ $it ? 'Nessun evento in programma' : 'No events scheduled' }}</h3>
                                <p class="mt-2 text-xs leading-5 text-pm-text-secondary">{{ $it ? 'Crea un evento o trasformane uno da una email.' : 'Create an event or turn an email into one.' }}</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-layouts::app>
