<?php

/**
 * Full page & scenario audit — run inside Docker:
 * docker compose exec -T -e SYSTEM_TEST_HTTP_BASE=http://nginx app php scripts/page-audit.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$host = config('app.central_domain');
$tenantHost = 'demo.'.$host;
$httpBase = rtrim(getenv('SYSTEM_TEST_HTTP_BASE') ?: "http://{$host}", '/');
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$results = [];
$passed = 0;
$failed = 0;

function audit(array &$results, int &$passed, int &$failed, string $area, string $scenario, bool $ok, string $detail = ''): void
{
    $results[] = compact('area', 'scenario', 'ok', 'detail');
    $ok ? $passed++ : $failed++;
}

function http(string $method, string $url, ?string $host = null, ?string $token = null, ?array $json = null, bool $asHtml = false): array
{
    $headers = [$asHtml ? 'Accept: text/html' : 'Accept: application/json'];
    if ($host) {
        $headers[] = "Host: {$host}";
    }
    if ($token) {
        $headers[] = "Authorization: Bearer {$token}";
    }
    if ($json !== null) {
        $headers[] = 'Content-Type: application/json';
    }

    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_CUSTOMREQUEST => $method,
    ];
    if ($json !== null) {
        $opts[CURLOPT_POSTFIELDS] = json_encode($json);
    }
    curl_setopt_array($ch, $opts);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['status' => $status, 'body' => (string) $body, 'json' => json_decode((string) $body, true)];
}

function kernelRequest(Illuminate\Contracts\Http\Kernel $kernel, string $method, string $url, string $host, array $server = []): int
{
    $request = Illuminate\Http\Request::create($url, $method, [], [], [], array_merge(['HTTP_HOST' => $host], $server));
    $request->headers->set('HOST', $host);
    $response = $kernel->handle($request);
    $status = $response->getStatusCode();
    $kernel->terminate($request, $response);

    return $status;
}

// ── Runtime ───────────────────────────────────────────────────────────
audit($results, $passed, $failed, 'Runtime', 'PHP intl extension', extension_loaded('intl'));

$admin = App\Models\User::where('email', 'admin@laravel-tenant-kit.test')->first();
$tenant = App\Models\Tenant::find('demo');
audit($results, $passed, $failed, 'Data', 'Seeded admin user', $admin !== null);
audit($results, $passed, $failed, 'Data', 'Seeded demo tenant', $tenant !== null);

// ── Central — guest (HTTP via nginx) ──────────────────────────────────
$centralGuestPages = [
    'Landing' => '/',
    'Health /up' => '/up',
    'Workspace signup' => '/workspaces/create',
    'Login' => '/login',
    'Register' => '/register',
    'Forgot password' => '/forgot-password',
    'Admin login' => '/admin/login',
];

foreach ($centralGuestPages as $name => $path) {
    $r = http('GET', "{$httpBase}{$path}", $host, null, null, true);
    $ok = $path === '/up' ? $r['status'] === 204 : ($r['status'] >= 200 && $r['status'] < 400);
    audit($results, $passed, $failed, 'Central (guest)', $name, $ok, "HTTP {$r['status']}");
}

foreach (['Dashboard' => '/dashboard', 'Profile' => '/profile', 'Admin' => '/admin', 'Billing demo' => '/billing/demo'] as $name => $path) {
    $r = http('GET', "{$httpBase}{$path}", $host, null, null, true);
    audit($results, $passed, $failed, 'Central (guest)', "{$name} redirects", in_array($r['status'], [302, 303], true), "HTTP {$r['status']}");
}

$r = http('GET', "{$httpBase}/locale/ar", $host);
audit($results, $passed, $failed, 'Central (guest)', 'Locale switch', in_array($r['status'], [302, 303], true), "HTTP {$r['status']}");

$r = http('GET', "{$httpBase}/auth/google/redirect", $host);
audit($results, $passed, $failed, 'Central (guest)', 'OAuth Google disabled', $r['status'] === 404, "HTTP {$r['status']}");

// ── Tenant — guest ────────────────────────────────────────────────────
foreach (['Home' => '/', 'Login' => '/login', 'Register' => '/register'] as $name => $path) {
    $r = http('GET', "{$httpBase}{$path}", $tenantHost, null, null, true);
    audit($results, $passed, $failed, 'Tenant (guest)', $name, $r['status'] >= 200 && $r['status'] < 400, "HTTP {$r['status']}");
}

$r = http('GET', "{$httpBase}/dashboard", $tenantHost);
audit($results, $passed, $failed, 'Tenant (guest)', 'Dashboard redirects', in_array($r['status'], [302, 303], true), "HTTP {$r['status']}");

// ── Central — authenticated (kernel) ──────────────────────────────────
if ($admin) {
    Illuminate\Support\Facades\Auth::guard('web')->login($admin);

    foreach ([
        'Dashboard' => '/dashboard',
        'Profile' => '/profile',
        'Billing demo' => '/billing/demo',
    ] as $name => $path) {
        $status = kernelRequest($kernel, 'GET', "http://{$host}{$path}", $host);
        audit($results, $passed, $failed, 'Central (auth)', $name, $status >= 200 && $status < 400, "HTTP {$status}");
    }

    foreach ([
        'Filament dashboard' => '/admin',
        'Filament tenants list' => '/admin/tenants',
        'Filament create tenant' => '/admin/tenants/create',
        'Filament edit demo tenant' => '/admin/tenants/demo/edit',
    ] as $name => $path) {
        $status = kernelRequest($kernel, 'GET', "http://{$host}{$path}", $host);
        audit($results, $passed, $failed, 'Filament (admin)', $name, $status >= 200 && $status < 400, "HTTP {$status}");
    }

    Illuminate\Support\Facades\Auth::guard('web')->logout();
}

// ── Tenant — authenticated ────────────────────────────────────────────
if ($tenant) {
    tenancy()->initialize($tenant);
    $demoUser = App\Models\User::where('email', 'demo@demo.test')->first();
    if ($demoUser) {
        Illuminate\Support\Facades\Auth::guard('web')->login($demoUser);
        foreach (['Dashboard' => '/dashboard', 'Team' => '/team'] as $name => $path) {
            $status = kernelRequest($kernel, 'GET', "http://{$tenantHost}{$path}", $tenantHost);
            audit($results, $passed, $failed, 'Tenant (auth)', $name, $status >= 200 && $status < 400, "HTTP {$status}");
        }
        Illuminate\Support\Facades\Auth::guard('web')->logout();
    }
    tenancy()->end();
}

// ── API Central ───────────────────────────────────────────────────────
$badToken = http('POST', "{$httpBase}/api/auth/token", $host, null, ['email' => 'x@test.com', 'password' => 'wrong', 'device_name' => 'audit']);
audit($results, $passed, $failed, 'API Central', 'Invalid credentials → 422', $badToken['status'] === 422, "HTTP {$badToken['status']}");

if ($admin) {
    $tokenRes = http('POST', "{$httpBase}/api/auth/token", $host, null, [
        'email' => $admin->email,
        'password' => 'password',
        'device_name' => 'audit',
    ]);
    $token = $tokenRes['json']['token'] ?? null;
    audit($results, $passed, $failed, 'API Central', 'Issue token', $tokenRes['status'] === 200 && filled($token), "HTTP {$tokenRes['status']}");

    if ($token) {
        foreach ([
            'GET /api/user' => ['GET', '/api/user'],
            'GET /api/workspaces' => ['GET', '/api/workspaces'],
            'GET /api/workspaces/demo' => ['GET', '/api/workspaces/demo'],
            'GET /api/workspaces/demo/subscription' => ['GET', '/api/workspaces/demo/subscription'],
        ] as $label => [$method, $path]) {
            $r = http($method, "{$httpBase}{$path}", $host, $token);
            audit($results, $passed, $failed, 'API Central', $label, $r['status'] === 200, "HTTP {$r['status']}");
        }

        $limited = http('POST', "{$httpBase}/api/auth/token", $host, null, [
            'email' => $admin->email,
            'password' => 'password',
            'device_name' => 'limited',
            'abilities' => ['user:read'],
        ]);
        $limitedToken = $limited['json']['token'] ?? null;
        if ($limitedToken) {
            $r = http('GET', "{$httpBase}/api/workspaces", $host, $limitedToken);
            audit($results, $passed, $failed, 'API Central', 'Limited token → 403 workspaces', $r['status'] === 403, "HTTP {$r['status']}");
        }
    }
}

// ── API Tenant ────────────────────────────────────────────────────────
$tenantTokenRes = http('POST', "{$httpBase}/api/auth/token", $tenantHost, null, [
    'email' => 'demo@demo.test',
    'password' => 'password',
    'device_name' => 'audit',
]);
$tenantToken = $tenantTokenRes['json']['token'] ?? null;
audit($results, $passed, $failed, 'API Tenant', 'Issue token', $tenantTokenRes['status'] === 200 && filled($tenantToken), "HTTP {$tenantTokenRes['status']}");

if ($tenantToken) {
    $team = http('GET', "{$httpBase}/api/team", $tenantHost, $tenantToken);
    audit($results, $passed, $failed, 'API Tenant', 'GET /api/team', $team['status'] === 200, "HTTP {$team['status']}");

    $invite = http('POST', "{$httpBase}/api/team/invitations", $tenantHost, $tenantToken, [
        'email' => 'audit-'.uniqid().'@example.test',
        'role' => 'member',
    ]);
    audit($results, $passed, $failed, 'API Tenant', 'POST /api/team/invitations', $invite['status'] === 201, "HTTP {$invite['status']}");
}

// ── v1.2.1 suspend ────────────────────────────────────────────────────
if ($tenant) {
    $tenant->update(['suspended_at' => now()]);
    $suspended = http('POST', "{$httpBase}/api/auth/token", $tenantHost, null, [
        'email' => 'demo@demo.test',
        'password' => 'password',
        'device_name' => 'suspended',
    ]);
    audit($results, $passed, $failed, 'Suspend', 'Tenant API blocked when suspended', $suspended['status'] === 403, "HTTP {$suspended['status']}");

    $web = http('GET', "{$httpBase}/", $tenantHost, null, null, true);
    audit($results, $passed, $failed, 'Suspend', 'Tenant web blocked when suspended', $web['status'] === 503, "HTTP {$web['status']}");

    $tenant->update(['suspended_at' => null]);
    $restored = http('GET', "{$httpBase}/login", $tenantHost, null, null, true);
    audit($results, $passed, $failed, 'Suspend', 'Tenant accessible after unsuspend', $restored['status'] === 200, "HTTP {$restored['status']}");
}

// ── Rate limit ────────────────────────────────────────────────────────
Illuminate\Support\Facades\RateLimiter::clear('api-auth');
config(['api.rate_limit.auth_attempts' => 3, 'api.rate_limit.auth_decay_minutes' => 1]);
$hits = 0;
for ($i = 0; $i < 4; $i++) {
    $r = http('POST', "{$httpBase}/api/auth/token", $host, null, [
        'email' => 'nobody@test.com',
        'password' => 'wrong',
        'device_name' => 'rl',
    ]);
    if ($r['status'] === 429) {
        $hits++;
    }
}
audit($results, $passed, $failed, 'API Central', 'Rate limit triggers 429', $hits >= 1, "429 count={$hits}");

// ── Output ────────────────────────────────────────────────────────────
$reportPath = getenv('PAGE_AUDIT_REPORT') ?: null;
$lines = [];
$lines[] = '# Laravel Tenant Kit — Page Audit Report';
$lines[] = '';
$lines[] = 'Generated: '.now()->toIso8601String();
$lines[] = "HTTP base: {$httpBase}";
$lines[] = '';
$lines[] = "| Area | Scenario | Result | Detail |";
$lines[] = "|------|----------|--------|--------|";

$currentArea = '';
foreach ($results as $r) {
    $icon = $r['ok'] ? 'PASS' : '**FAIL**';
    $detail = $r['detail'] ? str_replace('|', '/', $r['detail']) : '';
    $lines[] = "| {$r['area']} | {$r['scenario']} | {$icon} | {$detail} |";
}

$total = $passed + $failed;
$pct = $total > 0 ? round(($passed / $total) * 100) : 0;
$lines[] = '';
$lines[] = "## Summary: {$passed}/{$total} passed ({$pct}%)";
$lines[] = $failed === 0 ? '**Status: ALL SCENARIOS PASSED**' : "**Status: {$failed} FAILURE(S)**";

$markdown = implode("\n", $lines)."\n";

if ($reportPath) {
    file_put_contents($reportPath, $markdown);
}

echo $markdown;

exit($failed > 0 ? 1 : 0);
