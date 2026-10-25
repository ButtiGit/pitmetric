<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <meta name="supported-color-schemes" content="dark">
    <title>PitMetric</title>
</head>
<body bgcolor="#080a0d" style="margin:0;background:#080a0d;color:#f4f4f5;">
    @php($logoSrc = $message->embed(public_path('brand/pitmetric-primary-dark-email.png')))
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" bgcolor="#080a0d" style="background:#080a0d;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <x-emails.outreach :locale="$locale" :message-body="$messageBody" :note="$note" :unsubscribe-url="$unsubscribeUrl" :logo-src="$logoSrc" />
            </td>
        </tr>
    </table>
</body>
</html>
