<?php

return [

    'starter' => [
        'name' => 'Starter',
        'description' => 'For small teams getting started.',
        'price' => '$19',
        'stripe_price' => env('STRIPE_PRICE_STARTER'),
    ],

    'pro' => [
        'name' => 'Pro',
        'description' => 'For growing teams that need more power.',
        'price' => '$49',
        'stripe_price' => env('STRIPE_PRICE_PRO'),
    ],

];
