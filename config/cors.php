<?php

return [
    'paths' => ['api/*', '.well-known/jwks.json', 'sanctum/csrf-cookie'],
    'allowed_methods' => explode(',', env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS')),
    'allowed_origins' => array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', '*'))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => explode(',', env('CORS_ALLOWED_HEADERS', '*')),
    'exposed_headers' => array_filter(array_map('trim', explode(',', env('CORS_EXPOSED_HEADERS', '')))),
    'max_age' => (int) env('CORS_MAX_AGE', 3600),
    'supports_credentials' => filter_var(env('CORS_SUPPORTS_CREDENTIALS', false), FILTER_VALIDATE_BOOLEAN),
];
