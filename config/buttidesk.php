<?php

return [
    // Fail closed: the service desk is enabled only after the database is ready.
    'enabled' => (bool) env('BUTTIDESK_ENABLED', false),
    // Interactive dates are entered in local time; the database and iCal feeds use UTC.
    'bridge' => [
        'enabled' => (bool) env('BUTTIDESK_BRIDGE_ENABLED', false),
        'url' => env('BUTTIDESK_PRO_URL', ''),
        'secret' => env('BUTTIDESK_BRIDGE_SECRET', ''),
    ],
    'timezone' => env('BUTTIDESK_TIMEZONE', 'Europe/Rome'),
];
