<?php

return [
    'secret' => env('JWT_SECRET'),
    'ttl_minutes' => (int) env('JWT_TTL', 15),
    'refresh_ttl_days' => (int) env('JWT_REFRESH_TTL_DAYS', 30),
    'issuer' => env('JWT_ISSUER', env('APP_URL')),
    'audience' => env('JWT_AUDIENCE', 'archivenational'),
];

