<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The API is consumed cross-origin by the Angular client (localhost:4200)
    | and the Vue hangman game (localhost:8080). Authentication uses Bearer
    | tokens (not cookies), so a wildcard origin is safe and credentials are
    | disabled. Tighten 'allowed_origins' before deploying to production.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Comma-separated list in FRONTEND_ORIGINS (e.g. "https://lynx1097.github.io").
    // Defaults to '*' for local dev. Bearer-token auth (no cookies) means a
    // wildcard is safe, but restrict it in production for hygiene.
    'allowed_origins' => explode(',', env('FRONTEND_ORIGINS', '*')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
