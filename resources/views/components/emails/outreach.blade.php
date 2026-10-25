@props(['locale', 'messageBody', 'note' => '', 'unsubscribeUrl' => '#'])

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" lang="{{ $locale }}" style="width:100%;max-width:640px;table-layout:fixed;background:#ffffff;border:1px solid #e4e4e7;font-family:Arial,Helvetica,sans-serif;color:#171717;overflow-wrap:break-word;">
    <tr>
        <td style="padding:24px;background:#090a0c;border-bottom:4px solid #FF5A36;">
            <a href="{{ route('localized.home', ['locale' => $locale]) }}" data-outreach-link="home" style="font-size:28px;font-weight:800;letter-spacing:-1px;color:#ffffff;text-decoration:none;">Pit<span style="color:#FF5A36;">Metric</span></a>
            <div style="margin-top:8px;font-size:11px;letter-spacing:2px;color:#a1a1aa;text-transform:uppercase;">Trackside operations</div>
        </td>
    </tr>
    <tr>
        <td style="padding:28px 24px 8px;">
            <div data-outreach-label="eyebrow" style="margin-bottom:18px;font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#c63b1c;">{{ $locale === 'it' ? 'Un invito a provare PitMetric' : 'An invitation to try PitMetric' }}</div>
            <div data-outreach-message>
                @foreach (preg_split('/\R\s*\R/', trim($messageBody)) ?: [] as $paragraph)
                    <p style="margin:0 0 16px;font-size:15px;line-height:1.75;color:#3f3f46;">{!! nl2br(e($paragraph)) !!}</p>
                @endforeach
            </div>
            <div data-outreach-note @if ($note === '') hidden @endif style="margin:20px 0;padding:14px 16px;border-left:3px solid #FF5A36;background:#fafafa;font-size:14px;line-height:1.7;color:#3f3f46;white-space:pre-wrap;">{{ $note }}</div>
        </td>
    </tr>
    <tr>
        <td style="padding:8px 24px 28px;">
            <a href="{{ route('demo.public') }}" data-outreach-label="demo" style="display:inline-block;margin:0 12px 12px 0;padding:14px 20px;background:#FF5A36;border-radius:6px;color:#161616;text-decoration:none;font-size:15px;font-weight:800;">{{ $locale === 'it' ? 'Esplora la demo' : 'Explore the demo' }}</a>
            <a href="{{ route('localized.app', ['locale' => $locale]) }}" data-outreach-link="app" data-outreach-label="app" style="display:inline-block;margin-bottom:12px;color:#b93419;text-decoration:underline;font-size:14px;font-weight:700;">{{ $locale === 'it' ? 'Scopri l’app Android' : 'Discover the Android app' }}</a>
            <p data-outreach-label="reply" style="margin:8px 0 0;font-size:14px;line-height:1.7;color:#52525b;">{{ $locale === 'it' ? 'Per un confronto o per iniziare la prova, rispondete direttamente a questa email: vi risponderò personalmente.' : 'To discuss the project or start testing, reply directly to this email. I will get back to you personally.' }}</p>
            <div style="margin-top:24px;padding-top:20px;border-top:1px solid #e4e4e7;font-size:14px;line-height:1.7;color:#52525b;">
                <strong style="color:#18181b;">Simone Buttice</strong><br>
                <span data-outreach-label="signature">{{ $locale === 'it' ? 'Sviluppatore e ideatore di PitMetric' : 'Developer and creator of PitMetric' }}</span><br>
                <a href="{{ route('localized.home', ['locale' => $locale]) }}" data-outreach-link="home" style="color:#b93419;text-decoration:none;font-weight:700;">pitmetric.it</a>
            </div>
        </td>
    </tr>
    <tr>
        <td style="padding:18px 24px;border-top:1px solid #e4e4e7;background:#fafafa;font-size:12px;line-height:1.6;color:#71717a;">
            <span data-outreach-label="footer">{{ $locale === 'it' ? 'Messaggio inviato da PitMetric. Se il progetto non è pertinente o preferisci non ricevere altre comunicazioni, puoi usare questo link:' : 'A message from PitMetric. If the project is not relevant or you prefer not to receive further messages, use this link:' }}</span>
            <a href="{{ $unsubscribeUrl }}" data-outreach-label="unsubscribe" style="color:#52525b;text-decoration:underline;">{{ $locale === 'it' ? 'non ricevere altre email' : 'stop future emails' }}</a>.
        </td>
    </tr>
</table>
