<?php

$editorEmails = array_values(array_filter(array_map(
    static fn (string $email): string => strtolower(trim($email)),
    explode(',', (string) env('PITMETRIC_EDITOR_EMAILS', '')),
)));

return [
    'update_editor_emails' => $editorEmails,
    'calendar_timezone' => env('PITMETRIC_CALENDAR_TIMEZONE', 'Europe/Rome'),
    'android_apk_url' => 'https://github.com/ButtiGit/pitmetric/releases/download/android-latest/PitMetric.apk',
];
