<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Optional KYC module (v1.4)
    |--------------------------------------------------------------------------
    |
    | Master switch for Tenant Kit wiring. Install the package separately:
    |   composer require kyc-ai/laravel
    |
    | See docs/kyc.md. Do not add kyc-ai/laravel to this project's default require.
    |
    */

    'enabled' => (bool) env('KYC_ENABLED', false),

    /*
    | Default verification level for workspaces (internal | standard | full).
    | Prefer "standard" until external drivers (e.g. Shufti) are production-ready.
    */
    'default_level' => env('KYC_LEVEL', 'standard'),

    'extraction' => [
        'default' => env('KYC_EXTRACTION_DRIVER', 'fake'),

        'drivers' => [
            'fake' => [],

            'openai' => [
                'api_key' => env('OPENAI_API_KEY'),
                'model' => env('KYC_OPENAI_MODEL', 'gpt-4o'),
                'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            ],

            'tesseract' => [
                'binary' => env('TESSERACT_BINARY', 'tesseract'),
                'language' => env('TESSERACT_LANGUAGE', 'ara+eng'),
                'timeout' => (int) env('TESSERACT_TIMEOUT', 30),
            ],
        ],
    ],

    'internal_verification' => true,

    'external_verification' => [
        'enabled' => (bool) env('KYC_EXTERNAL_ENABLED', false),
        'default' => env('KYC_EXTERNAL_DRIVER'),
        'drivers' => [
            // Satellite packages register here (e.g. kyc-ai/external-shufti).
        ],
    ],

    'confidence_threshold' => (float) env('KYC_CONFIDENCE_THRESHOLD', 0.75),

    'manual_review_below' => (float) env('KYC_MANUAL_REVIEW_BELOW', 0.60),

    'delete_document_after_verify' => (bool) env('KYC_DELETE_AFTER_VERIFY', false),

    'audit' => [
        'enabled' => (bool) env('KYC_AUDIT_ENABLED', false),
    ],

    'routes' => [
        'api' => (bool) env('KYC_ROUTES_API', false),
        'demo' => (bool) env('KYC_ROUTES_DEMO', false),
    ],

    /*
    | Per-workspace defaults (override via Tenant virtual attributes:
    | kyc_country, kyc_level, kyc_extraction_driver).
    */
    'tenant' => [
        'default_country' => env('KYC_DEFAULT_COUNTRY', 'sa'),
    ],

];
