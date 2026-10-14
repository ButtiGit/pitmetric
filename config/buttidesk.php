<?php

return [
    // Interactive dates are entered in local time; the database and iCal feeds use UTC.
    'timezone' => env('BUTTIDESK_TIMEZONE', 'Europe/Rome'),
];
