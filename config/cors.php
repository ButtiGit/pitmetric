<?php

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://localhost',
        'http://localhost',
        'capacitor://localhost',
    ],

    'allowed_origins_patterns' => [
        '#^https?://localhost(?::\\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,
];
