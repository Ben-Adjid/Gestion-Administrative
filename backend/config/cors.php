<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Production : CORS_ALLOWED_ORIGINS="https://demarches.td,https://www.demarches.td"
    // (à défaut FRONTEND_URL). Les adresses localhost ne sont ajoutées qu'en local.
    'allowed_origins' => array_values(array_unique(array_filter(array_merge(
        array_map(
            fn (string $origin) => rtrim(trim($origin), '/'),
            explode(',', (string) env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', 'http://localhost:3000')))
        ),
        env('APP_ENV', 'production') === 'local'
            ? ['http://localhost:3000', 'http://127.0.0.1:3000', 'http://localhost:3001']
            : []
    )))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Accept-Language', 'Authorization', 'Content-Type', 'X-Requested-With', 'X-XSRF-TOKEN', 'X-Api-Key'],

    'exposed_headers' => ['Content-Disposition', 'X-RateLimit-Limit', 'X-RateLimit-Remaining', 'Retry-After'],

    'max_age' => 3600,

    'supports_credentials' => true,

];
