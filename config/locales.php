<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Locale definitions
    |--------------------------------------------------------------------------
    |
    | Register every language your app can support here. Enable a subset via
    | APP_AVAILABLE_LOCALES in .env (comma-separated codes).
    |
    */

    'definitions' => [
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'dir' => 'ltr',
        ],
        'ar' => [
            'name' => 'Arabic',
            'native' => 'العربية',
            'dir' => 'rtl',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Enabled locales
    |--------------------------------------------------------------------------
    */

    'enabled' => array_values(array_filter(array_map(
        trim(...),
        explode(',', env('APP_AVAILABLE_LOCALES', 'en,ar'))
    ))),

    'session_key' => 'locale',

];
