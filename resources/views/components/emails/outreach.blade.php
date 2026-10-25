@props(['locale', 'messageBody', 'note' => '', 'unsubscribeUrl' => '#', 'logoSrc' => null, 'preview' => false])

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" lang="{{ $locale }}" bgcolor="#111318" style="width:100%;max-width:640px;table-layout:fixed;background:#111318;border:1px solid #272d36;font-family:'Instrument Sans',Arial,Helvetica,sans-serif;color:#f4f4f5;overflow-wrap:break-word;color-scheme:dark;">
    <tr>
        <td bgcolor="#090A0C" style="padding:28px 24px 24px;background:#090A0C;border-bottom:3px solid #E10600;">
            <a href="{{ route('localized.home', ['locale' => $locale]) }}" data-outreach-link="home" style="display:inline-block;color:#f4f4f5;text-decoration:none;">
                <img src="{{ $logoSrc ?? asset('brand/pitmetric-primary-dark-email.png') }}" width="300" height="55" alt="PitMetric" style="display:block;width:100%;max-width:300px;height:auto;border:0;color:#f4f4f5;font-size:24px;font-weight:900;font-style:italic;">
            </a>
            <div style="margin-top:14px;font-family:'Courier New',Courier,monospace;font-size:10px;line-height:1.6;letter-spacing:2px;color:#a1a1aa;text-transform:uppercase;">Trackside operations / Technical history</div>
        </td>
    </tr>
    <tr>
        <td style="padding:28px 24px 8px;">
            <div data-outreach-label="eyebrow" style="margin-bottom:20px;font-family:'Courier New',Courier,monospace;font-size:11px;line-height:1.6;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#ff625e;">{{ $locale === 'it' ? 'Un invito a provare PitMetric' : 'An invitation to try PitMetric' }}</div>
            <div data-outreach-message>
                @foreach (preg_split('/\R\s*\R/', trim($messageBody)) ?: [] as $paragraph)
                    <p style="margin:0 0 18px;font-size:15px;line-height:1.8;color:#d4d4d8;">{!! nl2br(e($paragraph)) !!}</p>
                @endforeach
            </div>
            @if ($preview || $note !== '')
                <div data-outreach-note @if ($note === '') hidden @endif style="{{ $note === '' ? 'display:none;' : '' }}margin:20px 0;padding:14px 16px;border-left:3px solid #E10600;background:#1b2027;font-size:14px;line-height:1.7;color:#d4d4d8;white-space:pre-wrap;">{{ $note }}</div>
            @endif
        </td>
    </tr>
    <tr>
        <td style="padding:8px 24px 28px;">
            <a href="{{ route('demo.public') }}" data-outreach-label="demo" style="display:inline-block;margin:0 16px 14px 0;padding:14px 22px;background:#E10600;border:1px solid #E10600;color:#ffffff;text-decoration:none;font-size:14px;font-weight:800;">{{ $locale === 'it' ? 'Esplora la demo' : 'Explore the demo' }}</a>
            <a href="{{ route('localized.app', ['locale' => $locale]) }}" data-outreach-link="app" data-outreach-label="app" style="display:inline-block;margin-bottom:14px;color:#f4f4f5;text-decoration:underline;font-size:14px;font-weight:700;">{{ $locale === 'it' ? 'Scopri l’app Android' : 'Discover the Android app' }}</a>
            <p data-outreach-label="reply" style="margin:8px 0 0;font-size:14px;line-height:1.8;color:#a9b1bd;">{{ $locale === 'it' ? 'Per un confronto o per iniziare la prova, rispondete direttamente a questa email: vi risponderò personalmente.' : 'To discuss the project or start testing, reply directly to this email. I will get back to you personally.' }}</p>
            <div style="margin-top:26px;padding-top:22px;border-top:1px solid #272d36;font-size:14px;line-height:1.8;color:#a9b1bd;">
                <strong style="color:#f4f4f5;">Simone Buttice</strong><br>
                <span data-outreach-label="signature">{{ $locale === 'it' ? 'Sviluppatore e ideatore di PitMetric' : 'Developer and creator of PitMetric' }}</span><br>
                <a href="{{ route('localized.home', ['locale' => $locale]) }}" data-outreach-link="home" style="color:#ff625e;text-decoration:none;font-weight:700;">pitmetric.it</a>
            </div>
        </td>
    </tr>
    <tr>
        <td bgcolor="#0b0d10" style="padding:20px 24px;border-top:1px solid #272d36;background:#0b0d10;font-size:12px;line-height:1.7;color:#a1a1aa;">
            <span data-outreach-label="footer">{{ $locale === 'it' ? 'Messaggio inviato da PitMetric. Se il progetto non è pertinente o preferisci non ricevere altre comunicazioni, puoi usare questo link:' : 'A message from PitMetric. If the project is not relevant or you prefer not to receive further messages, use this link:' }}</span>
            <a href="{{ $unsubscribeUrl }}" data-outreach-label="unsubscribe" style="color:#d4d4d8;text-decoration:underline;">{{ $locale === 'it' ? 'non ricevere altre email' : 'stop future emails' }}</a>.
        </td>
    </tr>
</table>
