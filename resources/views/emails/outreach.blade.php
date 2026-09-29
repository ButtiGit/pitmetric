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
    <img src="{{ url('/brand/pitmetric-primary-dark.svg') }}" width="190" alt="PitMetric" style="display:block;width:190px;max-width:100%;height:auto;border:0;">
    <div style="margin-top:10px;font-size:10px;letter-spacing:2px;color:#a1a1aa;text-transform:uppercase;">Trackside operations</div>
</td>
</tr>
<tr>
<td style="padding:32px 26px 12px;">
    <div style="font-size:12px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;color:#E10600;">{{ $locale === 'it' ? 'PitMetric / contatto diretto' : 'PitMetric / direct contact' }}</div>
    <div style="margin-top:16px;white-space:pre-wrap;font-size:16px;line-height:1.7;color:#3f3f46;">{{ $messageBody }}</div>
    @if ($note !== '')
        <div style="margin:22px 0;padding:16px 18px;border-left:4px solid #E10600;background:#fafafa;font-size:15px;line-height:1.6;color:#27272a;">{{ $note }}</div>
    @endif
</td>
</tr>
<tr>
<td style="padding:8px 26px 28px;">
    <a href="{{ route('demo.public') }}" style="display:inline-block;background:#E10600;color:#ffffff;text-decoration:none;font-weight:800;font-size:15px;padding:14px 20px;">{{ $locale === 'it' ? 'Esplora la Demo' : 'Explore the Demo' }}</a>
    <p style="margin:22px 0 0;font-size:14px;line-height:1.7;color:#52525b;">{{ $locale === 'it' ? 'Se volete confrontarvi sul progetto, rispondete direttamente a questa email: il messaggio arriva a me nello Studio PitMetric, senza intermediari.' : 'If you would like to discuss the project, reply directly to this email. Your message reaches me in the PitMetric Studio, with no intermediaries.' }}</p>
    <div style="margin-top:22px;padding-top:20px;border-top:1px solid #e4e4e7;font-size:14px;line-height:1.6;color:#52525b;">
        <strong style="color:#18181b;">Simone Buttice</strong><br>
        {{ $locale === 'it' ? 'Sviluppatore e ideatore di PitMetric' : 'Developer and creator of PitMetric' }}<br>
        <a href="{{ route('home') }}" style="color:#E10600;text-decoration:none;font-weight:700;">pitmetric.it</a>
    </div>
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
