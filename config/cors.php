<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter([
        env('FRONTEND_URL', 'http://localhost:3000'),
        env('APP_URL'),
        'https://abo7tb.alwaysdata.net',
    ])),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'Origin',
        'X-Device-Token',
    ],

    'exposed_headers' => [
        'X-Response-Time',
        'X-Request-Id',
    ],

    'max_age' => 86400,

    'supports_credentials' => true,
];
