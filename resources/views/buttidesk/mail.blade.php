<x-buttidesk.shell title="ButtiDesk-Pro | Mail">
    <div class="bd-hero"><div><p class="bd-eyebrow">COMMUNICATION / SHARED INBOX</p><h1 class="bd-title">Posta PitMetric</h1><p class="bd-desc">La stessa casella dello Studio PitMetric: ricevi tramite Resend e invia con il mittente e il reply-to già configurati. Le mail inviate da ButtiDesk vengono registrate nel workspace.</p></div><a class="bd-btn bd-btn--ghost" href="{{ route('studio.mail.index') }}">Apri Studio PitMetric </a></div>
    @if($error)<div class="bd-error">{{ $error }}</div>@endif
    <div class="bd-columns">
        <div style="display:grid;gap:16px">
            <section class="bd-panel"><div class="bd-panel-heading"><div><h2>Posta ricevuta</h2><p>{{ count($received) }} messaggi dalla inbox condivisa</p></div></div>
                @forelse($received as $email)
                    @if(!empty($email['id']))
                        <a class="bd-item" href="{{ route('buttidesk.mail.show', $email['id']) }}">
                            <div class="bd-item-mark">@</div><div class="bd-item-body"><strong>{{ (string) ($email['subject'] ?? '(senza oggetto)') }}</strong><small>{{ (string) ($email['from'] ?? '') }} · {{ !empty($email['created_at']) ? \Illuminate\Support\Carbon::parse($email['created_at'])->timezone(config('buttidesk.timezone'))->format('d/m/Y H:i') : '' }}</small></div><span class="bd-link">Apri »</span>
                        </a>
                    @endif
                @empty<div class="bd-empty"><strong>Inbox vuota</strong>Le email ricevute via Resend saranno visibili anche dallo Studio di PitMetric.</div>@endforelse
            </section>
            <section class="bd-panel"><div class="bd-panel-heading"><div><h2>Inviate da ButtiDesk-Pro</h2><p>Storico locale delle comunicazioni</p></div></div>
                @forelse($sent as $mail)
                    <div class="bd-item"><div class="bd-item-mark"></div><div class="bd-item-body"><strong>{{ $mail->subject }}</strong><small>A: {{ $mail->recipient }} · {{ $mail->created_at->copy()->timezone(config('buttidesk.timezone'))->format('d/m/Y H:i') }} @if($mail->ticket_id) · Ticket #{{ $mail->ticket_id }}@endif</small></div></div>
                @empty<div class="bd-empty">Nessuna email inviata da questo modulo.</div>@endforelse
            </section>
        </div>
        <section class="bd-panel"><div class="bd-panel-heading"><h2>Nuova email</h2></div><div class="bd-pad">
            <form class="bd-form" method="POST" action="{{ route('buttidesk.mail.send') }}">@csrf
                <div class="bd-note">Mittente e indirizzo di risposta sono gestiti dalla configurazione comune di PitMetric. L'invio richiede un provider email attivo.</div>
                <div class="bd-field"><label>A *</label><input name="to" type="email" required maxlength="254" value="{{ old('to', $prefillEmail) }}" placeholder="cliente@azienda.it"></div>
                <div class="bd-field"><label>Oggetto *</label><input name="subject" required maxlength="180" value="{{ old('subject') }}"></div>
                <div class="bd-field"><label>Collega a un ticket</label><select name="ticket_id"><option value="">Non collegare</option>@foreach($tickets as $ticket)<option value="{{ $ticket->id }}" @selected(old('ticket_id', $prefillTicket)==$ticket->id)>#{{ $ticket->id }} · {{ $ticket->title }}</option>@endforeach</select></div>
                <div class="bd-field"><label>Messaggio *</label><textarea name="message" required maxlength="12000" rows="9" placeholder="Scrivi qui la mail...">{{ old('message') }}</textarea></div>
                <button type="submit" class="bd-btn">Invia email »</button>
            </form>
        </div></section>
    </div>
</x-buttidesk.shell>
