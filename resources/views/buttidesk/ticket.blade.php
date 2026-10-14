<x-buttidesk.shell title="ButtiDesk-Pro | Ticket #{{ $ticket->id }}">
    <div class="bd-hero">
        <div><p class="bd-eyebrow">TICKET #{{ str_pad($ticket->id, 4, '0', STR_PAD_LEFT) }} / OPERATIVE FILE</p><h1 class="bd-title">{{ $ticket->title }}</h1><p class="bd-desc">{{ $ticket->client?->name ?? 'Nessun cliente' }} · Aperto il {{ $ticket->created_at->copy()->timezone(config('buttidesk.timezone'))->format('d/m/Y') }} · {{ $ticket->category }}</p></div>
        <div class="bd-actions"><a class="bd-btn bd-btn--ghost" href="{{ route('buttidesk.tickets.index') }}">← Ticket</a><a class="bd-btn" href="{{ route('buttidesk.tickets.report', $ticket) }}" target="_blank" rel="noopener">Stampa report ↗</a></div>
    </div>
    <div class="bd-grid">
        <div class="bd-stat"><div class="bd-stat-label">Stato</div><div style="margin-top:15px"><span class="bd-badge bd-badge--{{ $ticket->status }}">{{ ['open'=>'Aperto','progress'=>'In corso','waiting'=>'In attesa','done'=>'Completato','cancelled'=>'Annullato'][$ticket->status] ?? $ticket->status }}</span></div></div>
        <div class="bd-stat"><div class="bd-stat-label">Priorità</div><div style="margin-top:15px"><span class="bd-badge bd-badge--{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }}</span></div></div>
        <div class="bd-stat"><div class="bd-stat-label">Interventi</div><div class="bd-stat-number">{{ $ticket->worklogs->count() }}</div></div>
        <div class="bd-stat"><div class="bd-stat-label">Tempo impiegato</div><div class="bd-stat-number">{{ number_format($ticket->worklogs->sum('duration_minutes') / 60, 1, ',', '.') }}<span style="font-size:15px"> h</span></div></div>
    </div>
    <div class="bd-columns">
        <div style="display:grid;gap:16px">
            <section class="bd-panel"><div class="bd-panel-heading"><div><h2>Dettagli della richiesta</h2><p>Contatti e descrizione del problema</p></div></div><div class="bd-pad">
                <div class="bd-pillline"><span class="bd-badge">Cliente: {{ $ticket->client?->name ?? '—' }}</span><span class="bd-badge">Fornitore: {{ $ticket->supplier?->name ?? '—' }}</span><span class="bd-badge">Scadenza: {{ $ticket->due_at?->copy()->timezone(config('buttidesk.timezone'))->format('d/m/Y H:i') ?? '—' }}</span></div>
                <p style="white-space:pre-wrap;color:#c1cee4;line-height:1.8;font-size:14px;margin-top:18px">{{ $ticket->description ?: 'Nessuna descrizione aggiuntiva.' }}</p>
            </div></section>
            <section class="bd-panel"><div class="bd-panel-heading"><div><h2>Interventi effettuati</h2><p>Registro cronologico pronto per il report cliente</p></div></div><div class="bd-pad">
                @if($ticket->worklogs->isEmpty())<div class="bd-empty"><strong>Nessun intervento</strong>Registra cosa hai fatto, per quanto tempo e quando.</div>@else
                    <div class="bd-timeline">
                        @foreach($ticket->worklogs as $log)
                        <article><h3>{{ $log->summary }}</h3><div class="bd-datestamp">{{ $log->performed_at->copy()->timezone(config('buttidesk.timezone'))->format('d/m/Y H:i') }} · {{ $log->author?->name ?? 'Operatore' }} · {{ $log->duration_minutes }} min</div>@if($log->details)<p>{{ $log->details }}</p>@endif</article>
                        @endforeach
                    </div>
                @endif
            </div></section>
            <section class="bd-panel"><div class="bd-panel-heading"><h2>Collegamenti operativi</h2></div><div class="bd-pad">
                <div class="bd-pillline"><a class="bd-btn bd-btn--ghost" href="{{ route('buttidesk.calendar.index') }}">Apri calendario</a>@can('manage-updates')<a class="bd-btn bd-btn--ghost" href="{{ route('buttidesk.mail.index', ['ticket_id' => $ticket->id, 'to' => $ticket->client?->email]) }}">Invia mail</a>@endcan</div>
                @if($ticket->appointments->isNotEmpty())<div class="bd-section"><h2>Appuntamenti associati</h2>@foreach($ticket->appointments as $appointment)<div class="bd-item"><div class="bd-item-mark">{{ $appointment->starts_at->copy()->timezone(config('buttidesk.timezone'))->format('d') }}</div><div class="bd-item-body"><strong>{{ $appointment->title }}</strong><small>{{ $appointment->starts_at->copy()->timezone(config('buttidesk.timezone'))->format('d/m/Y H:i') }}</small></div></div>@endforeach</div>@endif
                @if($ticket->mailLogs->isNotEmpty())<div class="bd-section"><h2>Mail inviate dal desk</h2>@foreach($ticket->mailLogs as $mail)<div class="bd-item"><div class="bd-item-mark">@</div><div class="bd-item-body"><strong>{{ $mail->subject }}</strong><small>A: {{ $mail->recipient }} · {{ $mail->created_at->copy()->timezone(config('buttidesk.timezone'))->format('d/m/Y H:i') }}</small></div></div>@endforeach</div>@endif
            </div></section>
        </div>
        <div style="display:grid;gap:16px">
            @can('team-write')
            <section class="bd-panel"><div class="bd-panel-heading"><h2>Aggiorna ticket</h2></div><div class="bd-pad">
                <form class="bd-form" method="POST" action="{{ route('buttidesk.tickets.update', $ticket) }}">@csrf @method('PATCH')
                    <div class="bd-field"><label>Oggetto</label><input name="title" maxlength="180" required value="{{ $ticket->title }}"></div>
                    <div class="bd-field"><label>Descrizione</label><textarea name="description" maxlength="15000">{{ $ticket->description }}</textarea></div>
                    <div class="bd-field"><label>Categoria</label><input name="category" maxlength="60" required value="{{ $ticket->category }}"></div>
                    <div class="bd-form-grid"><div class="bd-field"><label>Stato</label><select name="status">@foreach(['open'=>'Aperto','progress'=>'In corso','waiting'=>'In attesa','done'=>'Completato','cancelled'=>'Annullato'] as $value=>$label)<option value="{{ $value }}" @selected($ticket->status===$value)>{{ $label }}</option>@endforeach</select></div><div class="bd-field"><label>Priorità</label><select name="priority">@foreach(['low'=>'Bassa','normal'=>'Normale','high'=>'Alta','urgent'=>'Urgente'] as $value=>$label)<option value="{{ $value }}" @selected($ticket->priority===$value)>{{ $label }}</option>@endforeach</select></div></div>
                    <div class="bd-field"><label>Cliente</label><select name="client_id"><option value="">Nessuno</option>@foreach($clients as $client)<option value="{{ $client->id }}" @selected($ticket->client_id===$client->id)>{{ $client->name }}</option>@endforeach</select></div>
                    <div class="bd-field"><label>Fornitore</label><select name="supplier_id"><option value="">Nessuno</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected($ticket->supplier_id===$supplier->id)>{{ $supplier->name }}</option>@endforeach</select></div>
                    <div class="bd-field"><label>Scadenza</label><input name="due_at" type="datetime-local" value="{{ $ticket->due_at?->copy()->timezone(config('buttidesk.timezone'))->format('Y-m-d\TH:i') }}"></div>
                    <button type="submit" class="bd-btn">Salva ticket</button>
                </form>
            </div></section>
            <section class="bd-panel"><div class="bd-panel-heading"><h2>Registra un intervento</h2></div><div class="bd-pad">
                <form class="bd-form" method="POST" action="{{ route('buttidesk.tickets.worklogs.store', $ticket) }}">@csrf
                    <div class="bd-field"><label>Attività svolta *</label><input name="summary" required maxlength="220" placeholder="Es. Installato e configurato software" value="{{ old('summary') }}"></div>
                    <div class="bd-field"><label>Descrizione / esito</label><textarea name="details" maxlength="15000" placeholder="Procedure, controlli, risultati...">{{ old('details') }}</textarea></div>
                    <div class="bd-form-grid">
                        <div class="bd-field"><label>Minuti</label><input type="number" name="duration_minutes" min="0" max="10080" required value="{{ old('duration_minutes', 30) }}"></div>
                        <div class="bd-field"><label>Quando</label><input type="datetime-local" name="performed_at" required value="{{ old('performed_at', now(config('buttidesk.timezone'))->format('Y-m-d\TH:i')) }}"></div>
                    </div>
                    <button class="bd-btn" type="submit">Aggiungi al registro →</button>
                </form>
            </div></section>
            @endcan
        </div>
    </div>
</x-buttidesk.shell>
