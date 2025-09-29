<?php

return [
    'issuer' => env('JWT_ISSUER', config('app.url')),
    'default_audiences' => explode(',', env('JWT_DEFAULT_AUD', 'internal')),
    'access_ttl_minutes' => (int) env('JWT_ACCESS_TTL_MIN', 10),
    'refresh_ttl_days' => (int) env('JWT_REFRESH_TTL_DAYS', 14),
    'clock_skew' => (int) env('JWT_SKEW_SECONDS', 60),
];
