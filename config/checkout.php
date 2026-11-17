<?php

return [
    'currency' => 'PEN',
    'simulator' => [
        'enabled' => filter_var(env('PAYMENT_SIMULATOR_ENABLED', false), FILTER_VALIDATE_BOOL),
        'secret' => env('PAYMENT_SIMULATOR_SECRET'),
    ],
    'mercadopago' => [
        'enabled' => filter_var(env('MERCADOPAGO_ENABLED', false), FILTER_VALIDATE_BOOL),
        'mode' => strtolower((string) env('MERCADOPAGO_MODE', 'test')),
        'allow_live' => filter_var(env('MERCADOPAGO_ALLOW_LIVE', false), FILTER_VALIDATE_BOOL),
        'test' => [
            'public_key' => env('MERCADOPAGO_TEST_PUBLIC_KEY'),
            'access_token' => env('MERCADOPAGO_TEST_ACCESS_TOKEN'),
            'webhook_secret' => env('MERCADOPAGO_TEST_WEBHOOK_SECRET'),
        ],
    ],
];
