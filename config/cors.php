<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing
    |--------------------------------------------------------------------------
    |
    | The mobile clients are native and never send an Origin header, so the only
    | consumer of these rules is a browser build. The production host currently
    | answers `Access-Control-Allow-Origin: *`; keep the same default here so no
    | web client breaks, but make it a single, explicit knob:
    |
    |   CORS_ALLOWED_ORIGINS=https://app.example.com,https://admin.example.com
    |
    | Credentials stay disabled: the API authenticates with bearer tokens, not
    | cookies, so a browser must never be allowed to send cookies cross-origin.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', '*'))
    ))) ?: ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
