<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription plans
    |--------------------------------------------------------------------------
    |
    | `features` is used when PLAN_GATING_ENABLED=true (see config/plan_gating.php).
    | Known keys today: teams, api, agent, kyc
    |
    */

    'free' => [
        'name' => 'Free',
        'description' => 'Default when no paid subscription is active.',
        'price' => '$0',
        'stripe_price' => null,
        'features' => [
            'teams',
            'api',
        ],
    ],

    'starter' => [
        'name' => 'Starter',
        'description' => 'For small teams getting started.',
        'price' => '$19',
        'stripe_price' => env('STRIPE_PRICE_STARTER'),
        'features' => [
            'teams',
            'api',
            'agent',
        ],
    ],

    'pro' => [
        'name' => 'Pro',
        'description' => 'For growing teams that need more power.',
        'price' => '$49',
        'stripe_price' => env('STRIPE_PRICE_PRO'),
        'features' => [
            'teams',
            'api',
            'agent',
            'kyc',
        ],
    ],

];
