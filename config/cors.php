<?php

return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['capacitor://localhost', 'http://localhost', 'https://localhost'],
    'allowed_origins_patterns' => ['#^https?://localhost(?::\d+)?$#'],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 3600,
    'supports_credentials' => false,
];
