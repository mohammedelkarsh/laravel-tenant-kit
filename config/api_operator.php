<?php

return [

    /*
    |--------------------------------------------------------------------------
    | In-app API Operator chat widget
    |--------------------------------------------------------------------------
    |
    | Proxies browser chat to the Python api-operator HTTP server (api-operator serve).
    | See docs/api-operator.md for setup.
    |
    */

    'enabled' => (bool) env('API_OPERATOR_ENABLED', false),

    'url' => rtrim((string) env('API_OPERATOR_URL', 'http://127.0.0.1:8100'), '/'),

    /*
    | Path to adapter.yaml as seen by the api-operator process (not necessarily Laravel base_path).
    | Docker: /tenant-kit/integrations/api-operator/adapter.yaml
    | Local:  integrations/api-operator/adapter.yaml (relative to api-operator CWD)
    */
    'adapter_path' => env(
        'API_OPERATOR_ADAPTER_PATH',
        base_path('integrations/api-operator/adapter.yaml'),
    ),

    'planner' => env('API_OPERATOR_PLANNER', 'mock'),

    'timeout' => (int) env('API_OPERATOR_TIMEOUT', 60),

    /*
    | Base URL passed to the YAML adapter when calling Tenant Kit API.
    | Docker: http://laravel-tenant-kit.test:8080 (logical host for tenant subdomains)
    */
    'tenant_kit_base_url' => env('API_OPERATOR_TENANT_KIT_BASE_URL'),

    /*
    | Internal connect host for Docker (api-operator → nginx). Leave null on Laragon/host.
    */
    'connect_host' => env('API_OPERATOR_CONNECT_HOST'),

    'token' => env('TENANT_KIT_API_TOKEN'),

    'abilities' => [
        'workspaces:read',
        'workspaces:write',
        'team:read',
        'team:invite',
    ],

    /*
    | Workspace used to record agent_calls usage when the chat request does not
    | include workspace_id. Leave null to skip metering until a workspace is set.
    */
    'billing_workspace_id' => env('API_OPERATOR_BILLING_WORKSPACE'),

];
