<?php

return [
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],
    'razorpay' => [
        'key' => env('RAZORPAY_KEY_ID', env('RAZORPAY_TEST_KEY')),
        'secret' => env('RAZORPAY_KEY_SECRET', env('RAZORPAY_TEST_SECRET')),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
    ],
    'phonepe' => [
        'merchant_id' => env('PHONEPE_MERCHANT_ID'),
        'salt_key' => env('PHONEPE_SALT_KEY'),
        'salt_index' => env('PHONEPE_SALT_INDEX'),
        'env' => env('PHONEPE_ENV', 'sandbox'),
    ],
];
