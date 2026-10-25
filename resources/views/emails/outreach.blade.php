<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PitMetric</title>
</head>
<body style="margin:0;background:#f4f4f5;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f4f5;">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <x-emails.outreach :locale="$locale" :message-body="$messageBody" :note="$note" :unsubscribe-url="$unsubscribeUrl" />
            </td>
        </tr>
    </table>
</body>
</html>
