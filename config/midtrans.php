<?php

return [
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized' => true,
    'is_3ds' => true,
    'subscription' => [
        'plan_name' => env('SUBSCRIPTION_PLAN_NAME', 'Langganan Bulanan PawPaw'),
        'amount' => (int) env('SUBSCRIPTION_AMOUNT', 100000),
        'currency' => env('SUBSCRIPTION_CURRENCY', 'IDR'),
        'duration_days' => (int) env('SUBSCRIPTION_DURATION_DAYS', 30),
    ],
];
