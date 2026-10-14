<x-buttidesk.shell title="ButtiDesk-Pro | Calendario">
    <div class="bd-hero"><div><p class="bd-eyebrow">SCHEDULE / PLANNER</p><h1 class="bd-title">Calendario impegni</h1><p class="bd-desc">Visite, scadenze e attività pianificate, collegabili a ticket e contatti. Esportabile o sottoscrivibile in altre app calendario con iCal.</p></div><div class="bd-actions"><a class="bd-btn bd-btn--ghost" href="{{ route('buttidesk.calendar.download') }}">Esporta .ics </a></div></div>
    <div class="bd-columns">
        <section class="bd-panel">
            <div class="bd-panel-heading"><div><h2>{{ $monthStart->translatedFormat('F Y') }}</h2><p>{{ $appointments->count() }} impegni nel periodo</p></div><div class="bd-actions"><a class="bd-btn bd-btn--ghost" href="{{ route('buttidesk.calendar.index', ['month'=>$monthStart->copy()->subMonth()->format('Y-m')]) }}">«</a><a class="bd-btn bd-btn--ghost" href="{{ route('buttidesk.calendar.index') }}">Oggi</a><a class="bd-btn bd-btn--ghost" href="{{ route('buttidesk.calendar.index', ['month'=>$monthStart->copy()->addMonth()->format('Y-m')]) }}">»</a></div></div>
            <div class="bd-calendar" style="border-bottom:0">@foreach(['Lun','Mar','Mer','Gio','Ven','Sab','Dom'] as $name)<div class="bd-weekday">{{ $name }}</div>@endforeach</div>
            @php($gridStart = $monthStart->copy()->startOfWeek())
            @php($gridEnd = $monthStart->copy()->endOfMonth()->endOfWeek())
            <div class="bd-calendar">
                @for ($date = $gridStart->copy(); $date->lte($gridEnd); $date->addDay())
                    <div class="bd-day {{ $date->month!==$monthStart->month ? 'bd-day--outside' : '' }} {{ $date->isToday() ? 'bd-day--today' : '' }}">
                        <div class="bd-day-number">{{ $date->format('d') }}</div>
                        @foreach($appointments as $event)
                            @if($event->starts_at->copy()->timezone(config('buttidesk.timezone'))->toDateString()===$date->toDateString())
                            <span class="bd-event" title="{{ $event->title }}">{{ $event->starts_at->copy()->timezone(config('buttidesk.timezone'))->format('H:i') }} {{ $event->title }}</span>
                            @endif
                        @endforeach
                    </div>
                @endfor
            </div>
            <div class="bd-panel-heading"><h2>Elenco appuntamenti</h2></div>
            @forelse($appointments as $event)
                <div class="bd-item"><div class="bd-item-mark">{{ $event->starts_at->copy()->timezone(config('buttidesk.timezone'))->format('d') }}</div><div class="bd-item-body"><strong>{{ $event->title }}</strong><small>{{ $event->starts_at->copy()->timezone(config('buttidesk.timezone'))->format('d/m/Y H:i') }} » {{ $event->ends_at->copy()->timezone(config('buttidesk.timezone'))->format('d/m/Y H:i') }} {{ $event->location ? '· '.$event->location : '' }}</small><small>{{ $event->contact?->name ?? '' }} @if($event->ticket) · <a class="bd-link" href="{{ route('buttidesk.tickets.show', $event->ticket) }}">Ticket #{{ $event->ticket->id }}</a>@endif</small>@if($event->description)<p class="bd-help">{{ $event->description }}</p>@endif</div>
                    @can('team-write')<form method="POST" action="{{ route('buttidesk.calendar.destroy', $event) }}" onsubmit="return confirm('Eliminare questo impegno?')">@csrf @method('DELETE')<button class="bd-btn bd-btn--danger" type="submit" aria-label="Elimina impegno {{ $event->title }}">×</button></form>@endcan
                </div>
                @can('team-write')
                <details class="bd-details">
                    <summary>Modifica l'impegno · {{ $event->title }}</summary>
                    <form class="bd-form" method="POST" action="{{ route('buttidesk.calendar.update', $event) }}">@csrf @method('PUT')
                        <div class="bd-field"><label>Titolo *</label><input name="title" maxlength="180" required value="{{ $event->title }}"></div>
                        <div class="bd-form-grid">
                            <div class="bd-field"><label>Inizio *</label><input type="datetime-local" name="starts_at" required value="{{ $event->starts_at->copy()->timezone(config('buttidesk.timezone'))->format('Y-m-d\TH:i') }}"></div>
                            <div class="bd-field"><label>Fine *</label><input type="datetime-local" name="ends_at" required value="{{ $event->ends_at->copy()->timezone(config('buttidesk.timezone'))->format('Y-m-d\TH:i') }}"></div>
                        </div>
                        <div class="bd-field"><label>Luogo</label><input name="location" maxlength="200" value="{{ $event->location }}"></div>
                        <div class="bd-form-grid">
                            <div class="bd-field"><label>Ticket</label><select name="ticket_id"><option value="">Nessuno</option>@foreach($tickets as $ticket)<option value="{{ $ticket->id }}" @selected($event->ticket_id===$ticket->id)>#{{ $ticket->id }} · {{ $ticket->title }}</option>@endforeach</select></div>
                            <div class="bd-field"><label>Contatto</label><select name="contact_id"><option value="">Nessuno</option>@foreach($contacts as $contact)<option value="{{ $contact->id }}" @selected($event->contact_id===$contact->id)>{{ $contact->name }}</option>@endforeach</select></div>
                        </div>
                        <div class="bd-field"><label>Descrizione</label><textarea name="description">{{ $event->description }}</textarea></div>
                        <button class="bd-btn" type="submit">Salva appuntamento</button>
                    </form>
                </details>
                @endcan
            @empty<div class="bd-empty"><strong>Nessun impegno questo mese</strong>Aggiungi il primo appuntamento dal pannello accanto.</div>@endforelse
        </section>
        <div style="display:grid;gap:16px">
            <section class="bd-panel"><div class="bd-panel-heading"><h2>Nuovo impegno</h2></div><div class="bd-pad">
                @can('team-write')
                <form class="bd-form" method="POST" action="{{ route('buttidesk.calendar.store') }}">@csrf
                    <div class="bd-field"><label>Titolo *</label><input name="title" maxlength="180" required value="{{ old('title') }}" placeholder="Es. Intervento presso cliente"></div>
                    <div class="bd-form-grid"><div class="bd-field"><label>Inizio *</label><input type="datetime-local" name="starts_at" required value="{{ old('starts_at', now(config('buttidesk.timezone'))->addDay()->setTime(9,0)->format('Y-m-d\TH:i')) }}"></div><div class="bd-field"><label>Fine *</label><input type="datetime-local" name="ends_at" required value="{{ old('ends_at', now(config('buttidesk.timezone'))->addDay()->setTime(10,0)->format('Y-m-d\TH:i')) }}"></div></div>
                    <div class="bd-field"><label>Luogo</label><input name="location" maxlength="200" value="{{ old('location') }}"></div>
                    <div class="bd-field"><label>Ticket associato</label><select name="ticket_id"><option value="">Nessuno</option>@foreach($tickets as $ticket)<option value="{{ $ticket->id }}">#{{ $ticket->id }} · {{ $ticket->title }}</option>@endforeach</select></div>
                    <div class="bd-field"><label>Contatto</label><select name="contact_id"><option value="">Nessuno</option>@foreach($contacts as $contact)<option value="{{ $contact->id }}">{{ $contact->name }}</option>@endforeach</select></div>
                    <div class="bd-field"><label>Dettagli</label><textarea name="description" maxlength="10000">{{ old('description') }}</textarea></div>
                    <button class="bd-btn" type="submit">Aggiungi impegno »</button>
                </form>
                @else<p class="bd-help">Calendario in sola lettura per il tuo ruolo.</p>@endcan
            </div></section>
            <section class="bd-panel"><div class="bd-panel-heading"><h2>Collega il calendario</h2></div><div class="bd-pad">
                <p class="bd-help">Puoi importare il file .ics oppure generare un link privato da inserire in Google Calendar, Outlook o Apple Calendar come calendario da URL. Le app esterne decidono quanto spesso aggiornarsi.</p>
                @if(session('desk_calendar_url'))<div class="bd-note" style="margin:15px 0"><strong>Link segreto — copialo adesso</strong><div style="overflow-wrap:anywhere;margin-top:6px">{{ session('desk_calendar_url') }}</div></div>@endif
                @can('team-write')
                <div class="bd-actions" style="margin-top:15px">
                    <form action="{{ route('buttidesk.calendar.feed.create') }}" method="POST">@csrf<button class="bd-btn" type="submit">{{ $feedEnabled ? 'Rigenera link privato' : 'Crea link di sincronizzazione' }}</button></form>
                    @if($feedEnabled)<form action="{{ route('buttidesk.calendar.feed.remove') }}" method="POST">@csrf @method('DELETE')<button class="bd-btn bd-btn--danger" type="submit">Revoca link</button></form>@endif
                </div>
                @endcan
                <p class="bd-help" style="margin-top:15px">Chi conosce il link può leggere gli appuntamenti del workspace. Non condividerlo pubblicamente.</p>
            </div></section>
        </div>
    </div>
</x-buttidesk.shell>
