<?php

return [

    'rate_limit' => [
        'auth_attempts' => (int) env('API_AUTH_RATE_LIMIT', 5),
        'auth_decay_minutes' => (int) env('API_AUTH_RATE_DECAY', 1),
    ],

    'central_abilities' => [
        'user:read',
        'workspaces:read',
        'workspaces:write',
    ],

    'tenant_abilities' => [
        'user:read',
        'team:read',
        'team:invite',
    ],

];
