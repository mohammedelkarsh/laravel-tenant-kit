<?php

return [

    'enabled' => (bool) env('USAGE_BILLING_ENABLED', true),

    /*
    | When true, each recorded event is also sent to Stripe Billing Meters
    | via Cashier's reportMeterEvent(). Keep false in local/tests unless
    | Stripe meters are configured.
    */
    'sync_to_stripe' => (bool) env('USAGE_SYNC_TO_STRIPE', false),

    'meters' => [
        'api_calls' => [
            'label' => 'API calls',
            'description' => 'Authenticated API requests (central + tenant).',
            'event_name' => env('STRIPE_METER_API_CALLS', 'api_calls'),
        ],
        'team_seats' => [
            'label' => 'Team seats',
            'description' => 'Active members in the workspace.',
            'event_name' => env('STRIPE_METER_TEAM_SEATS', 'team_seats'),
        ],
        'agent_calls' => [
            'label' => 'Agent calls',
            'description' => 'Successful in-app guided agent / api-operator chat proxy requests.',
            'event_name' => env('STRIPE_METER_AGENT_CALLS', 'agent_calls'),
        ],
    ],

];
