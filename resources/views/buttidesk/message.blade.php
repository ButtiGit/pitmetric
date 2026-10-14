<x-buttidesk.shell title="ButtiDesk-Pro | Email ricevuta">
    <div class="bd-hero"><div><p class="bd-eyebrow">SHARED INBOX / MESSAGE</p><h1 class="bd-title">{{ $email['subject'] ?? '(senza oggetto)' }}</h1><p class="bd-desc">Da {{ $email['from'] ?? '—' }} · {{ !empty($email['created_at']) ? \Illuminate\Support\Carbon::parse($email['created_at'])->timezone(config('buttidesk.timezone'))->format('d/m/Y H:i') : '' }}</p></div><a class="bd-btn bd-btn--ghost" href="{{ route('buttidesk.mail.index') }}">« Posta in arrivo</a></div>
    <div class="bd-columns">
        <section class="bd-panel"><div class="bd-panel-heading"><h2>Contenuto del messaggio</h2></div><div class="bd-pad"><div class="bd-pillline"><span class="bd-badge">Da: {{ $email['from'] ?? '—' }}</span><span class="bd-badge">A: {{ implode(', ', is_array($email['to'] ?? null) ? $email['to'] : []) }}</span></div><p style="white-space:pre-wrap;overflow-wrap:anywhere;color:#c9d3e8;font-size:14px;line-height:1.9;margin-top:22px">{{ $body ?: 'Nessun testo leggibile.' }}</p>
            @if(!empty($email['attachments']) && is_array($email['attachments']))<hr class="bd-divider"><p class="bd-eyebrow">Allegati presenti</p>@foreach($email['attachments'] as $attachment)<span class="bd-badge">{{ $attachment['filename'] ?? 'Allegato' }}</span>@endforeach<p class="bd-help">Gli allegati non vengono scaricati automaticamente.</p>@endif
        </div></section>
        <section class="bd-panel"><div class="bd-panel-heading"><h2>Rispondi e collega a un ticket</h2></div><div class="bd-pad">
            <p class="bd-help" style="margin-bottom:15px">Destinatario: {{ $replyAddress ?? 'indirizzo non valido' }}. Il messaggio partirà dalla stessa casella usata in PitMetric Studio.</p>
            @php($incomingSubject = (string) ($email['subject'] ?? ''))
            <form class="bd-form" method="POST" action="{{ route('buttidesk.mail.reply', $email['id'] ?? '') }}">@csrf
                <div class="bd-field"><label>Oggetto</label><input name="subject" maxlength="180" required value="{{ old('subject', str_starts_with(strtolower($incomingSubject), 're:') ? $incomingSubject : 'Re: '.$incomingSubject) }}"></div>
                <div class="bd-field"><label>Ticket</label><select name="ticket_id"><option value="">Nessuno</option>@foreach($tickets as $ticket)<option value="{{ $ticket->id }}">#{{ $ticket->id }} · {{ $ticket->title }}</option>@endforeach</select></div>
                <div class="bd-field"><label>Risposta *</label><textarea name="message" rows="9" maxlength="12000" required>{{ old('message') }}</textarea></div>
                <button type="submit" class="bd-btn" @disabled($replyAddress === null)>Invia risposta »</button>
            </form>
        </div></section>
    </div>
</x-buttidesk.shell>
