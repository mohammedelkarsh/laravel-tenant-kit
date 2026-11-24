<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Plan feature gating (v1.6)
    |--------------------------------------------------------------------------
    |
    | When enabled, workspace features (e.g. KYC) require the current Stripe
    | subscription plan to list that feature. Disabled by default so local
    | demos keep working without Stripe.
    |
    | Feature keys live under each plan in config/plans.php → features[].
    |
    */

    'enabled' => (bool) env('PLAN_GATING_ENABLED', false),

    /*
    | Plan key used when the workspace has no active default subscription.
    | Must exist in config/plans.php.
    */
    'default_plan' => env('PLAN_GATING_DEFAULT_PLAN', 'free'),

];
