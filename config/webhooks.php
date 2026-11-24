<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Platform outbound webhooks (v1.6)
    |--------------------------------------------------------------------------
    |
    | When enabled, Tenant Kit POSTs signed JSON events to PLATFORM_WEBHOOKS_URL
    | (workspace created / suspended / invite sent). Disabled by default.
    |
    */

    'enabled' => (bool) env('PLATFORM_WEBHOOKS_ENABLED', false),

    'url' => env('PLATFORM_WEBHOOKS_URL'),

    'secret' => env('PLATFORM_WEBHOOKS_SECRET'),

    'timeout' => (int) env('PLATFORM_WEBHOOKS_TIMEOUT', 5),

    /*
    | When true, deliveries run on the queue (recommended in production).
    | When false, HTTP is attempted inline (handy for local demos/tests).
    */
    'queue' => (bool) env('PLATFORM_WEBHOOKS_QUEUE', true),

];
