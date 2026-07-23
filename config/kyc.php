<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Optional KYC module
    |--------------------------------------------------------------------------
    |
    | Feature flag only in Phase A (v1.4 prep). When false, no KYC routes or
    | Filament panels are registered. Full opt-in via kyc-ai/laravel comes in
    | Phase B — see docs/kyc.md.
    |
    */

    'enabled' => (bool) env('KYC_ENABLED', false),

];
