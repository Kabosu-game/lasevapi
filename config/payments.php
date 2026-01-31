<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Methods Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration des moyens de paiement acceptés par l'application
    |
    */

    'stripe' => [
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'public_key' => env('STRIPE_PUBLIC_KEY'),
        'currency' => 'usd',
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'mode' => env('PAYPAL_MODE', 'live'), // live ou sandbox
        'currency' => 'USD',
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retreat Plans Pricing
    |--------------------------------------------------------------------------
    |
    | Prix des plans de retraite
    |
    */

    'retreat_plans' => [
        [
            'name' => 'Plan Essentiel',
            'price' => 2000,
            'currency' => 'USD',
            'description' => 'Plan de retraite de base',
        ],
        [
            'name' => 'Plan Standard',
            'price' => 3000,
            'currency' => 'USD',
            'description' => 'Plan de retraite standard',
        ],
        [
            'name' => 'Plan Premium',
            'price' => 5000,
            'currency' => 'USD',
            'description' => 'Plan de retraite premium avec tous les services',
        ],
    ],
];
