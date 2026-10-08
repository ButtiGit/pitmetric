<?php

return [
    // Server-side OpenAI settings. Authorization uses PitMetric's database.access middleware.
    'openai_api_key' => env('SYSPILOT_OPENAI_API_KEY', ''),
    'openai_model' => env('SYSPILOT_OPENAI_MODEL', 'gpt-4o-mini'),
];
