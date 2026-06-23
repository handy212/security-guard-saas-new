<?php

return [
    'public_key' => env('PAYSTACK_PUBLIC_KEY'),
    'secret_key' => env('PAYSTACK_SECRET_KEY'),
    'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
    'currency' => env('PAYSTACK_CURRENCY', 'NGN'),
    'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
];
