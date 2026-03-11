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

    /*
     * When we force INR for Razorpay/PhonePe we assume the original plan
     * pricing was listed in USD.  Rather than hard-code a rate throughout the
     * codebase we store a simple conversion factor here; it can be overridden
     * in env for testing or production adjustments.
     */
    'usd_to_inr_rate' => env('USD_TO_INR_RATE', 83.0),
];
