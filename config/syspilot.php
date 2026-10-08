<?php

return [
    // Configure these through Coolify's environment variables, never in Git.
    'allowed_emails' => env('SYSPILOT_ALLOWED_EMAILS', ''),
    'openai_api_key' => env('SYSPILOT_OPENAI_API_KEY', ''),
    'openai_model' => env('SYSPILOT_OPENAI_MODEL', 'gpt-4o-mini'),
];
