<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PitMetric</title>
</head>
<body style="margin:0;background:#f4f4f5;font-family:Arial,Helvetica,sans-serif;color:#171717;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f4f5;padding:28px 12px;">
<tr><td align="center">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #e4e4e7;">
<tr>
<td style="padding:22px 26px;background:#090a0c;color:#ffffff;border-bottom:4px solid #E10600;">
    <div style="font-size:11px;letter-spacing:2px;color:#a1a1aa;text-transform:uppercase;">PitMetric / Trackside operations</div>
    <div style="margin-top:6px;font-size:28px;font-weight:800;letter-spacing:-1px;">PIT<span style="color:#E10600;">METRIC</span></div>
</td>
</tr>
<tr>
<td style="padding:32px 26px 12px;">
@if ($locale === 'it')
    <div style="font-size:12px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:#E10600;">Per piloti e team motorsport</div>
    <h1 style="margin:12px 0 16px;font-size:30px;line-height:1.08;letter-spacing:-1px;">Quanto del vostro weekend finisce ancora tra note, chat e memoria?</h1>
    <p style="margin:0 0 18px;font-size:16px;line-height:1.65;color:#3f3f46;">PitMetric riunisce in un unico posto tempi, sessioni, setup, configurazioni, componenti, manutenzione, costi e storico tecnico del mezzo.</p>
    @if ($note !== '')
        <div style="margin:22px 0;padding:16px 18px;border-left:4px solid #E10600;background:#fafafa;font-size:15px;line-height:1.6;color:#27272a;">{{ $note }}</div>
    @endif
    <p style="margin:0 0 18px;font-size:16px;line-height:1.65;color:#3f3f46;">In questa fase non stiamo cercando semplici iscritti: cerchiamo piloti e team che vogliano provarlo davvero in pista e dirci cosa manca. Per chi collabora attivamente allo sviluppo, PitMetric è gratuito.</p>
@else
    <div style="font-size:12px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:#E10600;">For motorsport drivers and teams</div>
    <h1 style="margin:12px 0 16px;font-size:30px;line-height:1.08;letter-spacing:-1px;">How much of your race weekend still lives in notes, chats and memory?</h1>
    <p style="margin:0 0 18px;font-size:16px;line-height:1.65;color:#3f3f46;">PitMetric brings lap times, sessions, setups, configurations, components, maintenance, costs and technical history into one trackside workspace.</p>
    @if ($note !== '')
        <div style="margin:22px 0;padding:16px 18px;border-left:4px solid #E10600;background:#fafafa;font-size:15px;line-height:1.6;color:#27272a;">{{ $note }}</div>
    @endif
    <p style="margin:0 0 18px;font-size:16px;line-height:1.65;color:#3f3f46;">At this stage we are not looking for passive signups. We are looking for drivers and teams willing to test PitMetric in real trackside workflows and tell us what is missing. Active development partners use PitMetric free of charge.</p>
@endif
</td>
</tr>
<tr>
<td style="padding:8px 26px 28px;">
    <a href="{{ route('home') }}" style="display:inline-block;background:#E10600;color:#ffffff;text-decoration:none;font-weight:800;font-size:15px;padding:14px 20px;">{{ $locale === 'it' ? 'Scopri PitMetric' : 'Explore PitMetric' }}</a>
    <p style="margin:22px 0 0;font-size:14px;line-height:1.6;color:#52525b;">{{ $locale === 'it' ? 'Se preferite, rispondete direttamente a questa email con due righe sul vostro team: ci interessa capire come lavorate davvero in pista.' : 'If you prefer, reply directly to this email with a few lines about your team. We want to understand how you actually work at the track.' }}</p>
</td>
</tr>
<tr>
<td style="padding:20px 26px;border-top:1px solid #e4e4e7;background:#fafafa;font-size:11px;line-height:1.55;color:#71717a;">
    {{ $locale === 'it' ? 'Messaggio professionale inviato da PitMetric. Se questa comunicazione non è pertinente o non vuoi riceverne altre, puoi disiscriverti qui:' : 'Professional message from PitMetric. If this is not relevant or you do not want further outreach, you can opt out here:' }}
    <a href="{{ $unsubscribeUrl }}" style="color:#52525b;">{{ $locale === 'it' ? 'non ricevere altre email' : 'stop future emails' }}</a>.
</td>
</tr>
</table>
</td></tr>
</table>
</body>
</html>
