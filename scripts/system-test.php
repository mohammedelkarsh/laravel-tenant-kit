<?php

/**
 * Full system smoke test — run: php scripts/system-test.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$host = config('app.central_domain');
$defaultHttpBase = env('DB_HOST') === 'mysql'
    ? 'http://nginx'
    : rtrim((string) config('app.url'), '/');
$httpBase = rtrim(getenv('SYSTEM_TEST_HTTP_BASE') ?: $defaultHttpBase, '/');
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$results = [];
$passed = 0;
$failed = 0;

function record(array &$results, int &$passed, int &$failed, string $group, string $name, bool $ok, string $detail = ''): void
{
    $results[] = compact('group', 'name', 'ok', 'detail');
    $ok ? $passed++ : $failed++;
}

function httpGet(string $url, ?string $host = null, ?string $token = null): array
{
    $headers = $host ? ["Host: {$host}"] : [];
    if ($token) {
        $headers[] = 'Accept: application/json';
        $headers[] = "Authorization: Bearer {$token}";
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['status' => $status, 'body' => (string) $body, 'json' => json_decode((string) $body, true)];
}

function httpPostJson(string $url, array $payload, ?string $host = null, ?string $token = null): array
{
    $headers = ['Content-Type: application/json', 'Accept: application/json'];
    if ($host) {
        $headers[] = "Host: {$host}";
    }
    if ($token) {
        $headers[] = "Authorization: Bearer {$token}";
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['status' => $status, 'body' => (string) $body, 'json' => json_decode((string) $body, true)];
}

function resetSmokeTestState(): void
{
    if (tenancy()->initialized) {
        tenancy()->end();
    }

    Illuminate\Support\Facades\Auth::guard('web')->logout();
}

// ── PHP runtime ───────────────────────────────────────────────────────
record($results, $passed, $failed, 'PHP', 'intl extension loaded', extension_loaded('intl'));

// ── Database ──────────────────────────────────────────────────────────
try {
    \Illuminate\Support\Facades\DB::connection()->getPdo();
    record($results, $passed, $failed, 'Database', 'Central MySQL connection', true);
} catch (Throwable $e) {
    record($results, $passed, $failed, 'Database', 'Central MySQL connection', false, $e->getMessage());
}

$tenant = \App\Models\Tenant::find('demo');
if ($tenant) {
    try {
        tenancy()->initialize($tenant);
        \Illuminate\Support\Facades\DB::connection('tenant')->getPdo();
        tenancy()->end();
        record($results, $passed, $failed, 'Database', 'Demo tenant DB (tenantdemo)', true);
    } catch (Throwable $e) {
        tenancy()->end();
        record($results, $passed, $failed, 'Database', 'Demo tenant DB (tenantdemo)', false, $e->getMessage());
    }
} else {
    record($results, $passed, $failed, 'Database', 'Demo tenant exists', false, 'missing');
}

// ── Data integrity ────────────────────────────────────────────────────
$admin = \App\Models\User::where('email', 'admin@laravel-tenant-kit.test')->first();
record($results, $passed, $failed, 'Data', 'Platform admin user', $admin !== null);

if ($tenant) {
    tenancy()->initialize($tenant);
    $demoUser = \App\Models\User::where('email', 'demo@demo.test')->first();
    $hasOwner = $demoUser?->hasRole('owner') ?? false;
    tenancy()->end();
    record($results, $passed, $failed, 'Data', 'Demo user with owner role', $demoUser !== null && $hasOwner);
    record($results, $passed, $failed, 'Data', 'Demo domain record', $tenant->domains()->where('domain', 'demo')->exists());
}

record($results, $passed, $failed, 'Data', 'Tenants table has workspaces', \App\Models\Tenant::count() >= 1);

// ── HTTP — Central (guest) ────────────────────────────────────────────
$centralUrls = [
    'Landing page' => '/',
    'Admin login' => '/admin/login',
    'Workspace signup' => '/workspaces/create',
    'Health check' => '/up',
];

foreach ($centralUrls as $name => $path) {
    $r = httpGet("{$httpBase}{$path}", $host);
    $ok = $r['status'] >= 200 && $r['status'] < 400;
    record($results, $passed, $failed, 'HTTP Central', $name, $ok, "HTTP {$r['status']}");
}

// Protected central routes should redirect (302)
foreach (['Admin dashboard' => '/admin', 'Billing page' => '/billing/demo'] as $name => $path) {
    $r = httpGet("{$httpBase}{$path}", $host);
    $ok = in_array($r['status'], [302, 303], true);
    record($results, $passed, $failed, 'HTTP Central', "{$name} (guest → redirect)", $ok, "HTTP {$r['status']}");
}

// Landing content checks
$landing = httpGet("{$httpBase}/", $host);
record($results, $passed, $failed, 'Content', 'Landing has hero text', stripos($landing['body'], 'multi-tenant') !== false);
record($results, $passed, $failed, 'Content', 'Landing has architecture section', str_contains($landing['body'], 'tenant:provision'));
record($results, $passed, $failed, 'Content', 'Landing CSS assets', str_contains($landing['body'], '/build/assets/'));

// Locale switch + Arabic translations
$localeSwitch = httpGet("{$httpBase}/locale/ar", $host);
record($results, $passed, $failed, 'Localization', 'Locale switch route', in_array($localeSwitch['status'], [302, 303], true), "HTTP {$localeSwitch['status']}");
record($results, $passed, $failed, 'Localization', 'Enabled locales include en,ar', \App\Support\Locales::isEnabled('en') && \App\Support\Locales::isEnabled('ar'));
app()->setLocale('ar');
record($results, $passed, $failed, 'Localization', 'Arabic app translation', __('app.landing.architecture') === 'البنية');
record($results, $passed, $failed, 'Localization', 'Arabic RTL direction', \App\Support\Locales::direction('ar') === 'rtl');
app()->setLocale('en');

// ── Admin panel (fresh install path — before API mutations) ───────────
resetSmokeTestState();

if ($admin) {
    Illuminate\Support\Facades\Auth::guard('web')->login($admin);

    $request = Illuminate\Http\Request::create("http://{$host}/billing/demo", 'GET');
    $request->headers->set('HOST', $host);
    $response = $kernel->handle($request);
    $status = $response->getStatusCode();
    $kernel->terminate($request, $response);
    record($results, $passed, $failed, 'Auth Central', 'Billing demo', $status >= 200 && $status < 400, "HTTP {$status}");

    $request = Illuminate\Http\Request::create("http://{$host}/admin/tenants", 'GET');
    $request->headers->set('HOST', $host);
    $response = $kernel->handle($request);
    $status = $response->getStatusCode();
    $kernel->terminate($request, $response);
    record($results, $passed, $failed, 'Filament', 'Tenants list (admin)', $status >= 200 && $status < 400, "HTTP {$status}");

    Illuminate\Support\Facades\Auth::guard('web')->logout();
}

$adminDash = httpGet("{$httpBase}/admin", $host);
record($results, $passed, $failed, 'Filament', 'Admin reachable (login or dashboard)', in_array($adminDash['status'], [200, 302], true), 'test in browser after login');

// ── API (Sanctum) ─────────────────────────────────────────────────────
$apiTokenUrl = "{$httpBase}/api/auth/token";
$invalidToken = httpPostJson($apiTokenUrl, [], $host);
record($results, $passed, $failed, 'API Central', 'Token endpoint validates input', $invalidToken['status'] === 422, "HTTP {$invalidToken['status']}");

if ($admin) {
    $tokenResponse = httpPostJson($apiTokenUrl, [
        'email' => $admin->email,
        'password' => 'password',
        'device_name' => 'system-test',
    ], $host);
    $hasToken = $tokenResponse['status'] === 200 && filled($tokenResponse['json']['token'] ?? null);
    record($results, $passed, $failed, 'API Central', 'Issue token for platform admin', $hasToken, "HTTP {$tokenResponse['status']}");
    record($results, $passed, $failed, 'API Central', 'Token response includes abilities', filled($tokenResponse['json']['abilities'] ?? null));

    if ($hasToken) {
        $bearer = 'Bearer '.$tokenResponse['json']['token'];

        // httpGet does not send bearer — use kernel for authenticated API
        $request = Illuminate\Http\Request::create("http://{$host}/api/workspaces", 'GET');
        $request->headers->set('HOST', $host);
        $request->headers->set('Authorization', $bearer);
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        $kernel->terminate($request, $response);
        record($results, $passed, $failed, 'API Central', 'List workspaces with token', $status === 200, "HTTP {$status}");

        $request = Illuminate\Http\Request::create("http://{$host}/api/workspaces/demo/subscription", 'GET');
        $request->headers->set('HOST', $host);
        $request->headers->set('Authorization', $bearer);
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        $kernel->terminate($request, $response);
        record($results, $passed, $failed, 'API Central', 'Workspace subscription endpoint', $status === 200, "HTTP {$status}");
    }
}

Illuminate\Support\Facades\Auth::shouldUse('web');

// ── HTTP — Tenant (guest) ─────────────────────────────────────────────
$tenantHost = "demo.{$host}";
foreach (['Tenant home' => '/', 'Tenant login' => '/login', 'Tenant register' => '/register'] as $name => $path) {
    $r = httpGet("{$httpBase}{$path}", $tenantHost);
    $ok = $r['status'] >= 200 && $r['status'] < 400;
    record($results, $passed, $failed, 'HTTP Tenant', $name, $ok, "HTTP {$r['status']}");
}

$dash = httpGet("{$httpBase}/dashboard", $tenantHost);
record($results, $passed, $failed, 'HTTP Tenant', 'Dashboard (guest → redirect)', in_array($dash['status'], [302, 303], true), "HTTP {$dash['status']}");

$tenantHome = httpGet("{$httpBase}/", $tenantHost);
record($results, $passed, $failed, 'Content', 'Tenant CSS via /build/', str_contains($tenantHome['body'], '/build/assets/'));

// ── API (Tenant / Sanctum) ────────────────────────────────────────────
$tenantApiTokenUrl = "{$httpBase}/api/auth/token";
if (isset($demoUser) && $demoUser) {
    $tenantTokenResponse = httpPostJson($tenantApiTokenUrl, [
        'email' => 'demo@demo.test',
        'password' => 'password',
        'device_name' => 'system-test-tenant',
    ], $tenantHost);
    $hasTenantToken = $tenantTokenResponse['status'] === 200 && filled($tenantTokenResponse['json']['token'] ?? null);
    record($results, $passed, $failed, 'API Tenant', 'Issue token for demo user', $hasTenantToken, "HTTP {$tenantTokenResponse['status']}");

    if ($hasTenantToken) {
        $tenantToken = $tenantTokenResponse['json']['token'];

        $teamList = httpGet("{$httpBase}/api/team", $tenantHost, $tenantToken);
        record($results, $passed, $failed, 'API Tenant', 'List team with token', $teamList['status'] === 200, "HTTP {$teamList['status']}");

        $invite = httpPostJson("{$httpBase}/api/team/invitations", [
            'email' => 'invite-'.uniqid().'@example.test',
            'role' => 'member',
        ], $tenantHost, $tenantToken);
        record($results, $passed, $failed, 'API Tenant', 'Invite teammate via API', $invite['status'] === 201, "HTTP {$invite['status']}");

        // v1.2.1 — suspended workspace blocks tenant API
        if ($tenant) {
            $tenant->update(['suspended_at' => now()]);
            $suspendedAuth = httpPostJson($tenantApiTokenUrl, [
                'email' => 'demo@demo.test',
                'password' => 'password',
                'device_name' => 'system-test-suspended',
            ], $tenantHost);
            record($results, $passed, $failed, 'API Tenant', 'Suspended workspace blocks auth', $suspendedAuth['status'] === 403, "HTTP {$suspendedAuth['status']}");
            $tenant->update(['suspended_at' => null]);
        }
    }
}

// OAuth routes disabled without credentials
$oauthGoogle = httpGet("{$httpBase}/auth/google/redirect", $host);
record($results, $passed, $failed, 'OAuth', 'Google redirect (disabled → 404)', $oauthGoogle['status'] === 404, "HTTP {$oauthGoogle['status']}");

resetSmokeTestState();

if ($tenant && isset($demoUser) && $demoUser) {
    $tenant = $tenant->fresh();
    tenancy()->initialize($tenant);
    Illuminate\Support\Facades\Auth::guard('web')->login($demoUser);
    foreach (['Tenant dashboard' => '/dashboard', 'Tenant team' => '/team'] as $name => $path) {
        $request = Illuminate\Http\Request::create("http://{$tenantHost}{$path}", 'GET');
        $request->headers->set('HOST', $tenantHost);
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        $kernel->terminate($request, $response);
        $ok = $status >= 200 && $status < 400;
        record($results, $passed, $failed, 'Auth Tenant', $name, $ok, "HTTP {$status}");
    }
    tenancy()->end();
    Illuminate\Support\Facades\Auth::guard('web')->logout();
}

// ── CLI ───────────────────────────────────────────────────────────────
$artisanList = shell_exec('php '.escapeshellarg(__DIR__.'/../artisan').' list --raw 2>&1') ?: '';
record($results, $passed, $failed, 'CLI', 'tenant:provision registered', str_contains($artisanList, 'tenant:provision'));
record($results, $passed, $failed, 'CLI', 'tenants:migrate registered', str_contains($artisanList, 'tenants:migrate'));

// ── Output ────────────────────────────────────────────────────────────
echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║           LARAVEL TENANT KIT — SYSTEM TEST REPORT        ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n\n";

$currentGroup = '';
foreach ($results as $r) {
    if ($r['group'] !== $currentGroup) {
        $currentGroup = $r['group'];
        echo "\n── {$currentGroup} ".str_repeat('─', max(0, 50 - strlen($currentGroup)))."\n";
    }
    $icon = $r['ok'] ? '✅' : '❌';
    $detail = $r['detail'] ? " ({$r['detail']})" : '';
    echo "  {$icon} {$r['name']}{$detail}\n";
}

$total = $passed + $failed;
$pct = $total > 0 ? round(($passed / $total) * 100) : 0;

echo "\n══════════════════════════════════════════════════════════\n";
echo "  Result: {$passed}/{$total} passed ({$pct}%)\n";
echo $failed === 0
    ? "  Status: ALL TESTS PASSED ✅\n"
    : "  Status: {$failed} FAILURE(S) — review above ❌\n";
echo "══════════════════════════════════════════════════════════\n\n";

exit($failed > 0 ? 1 : 0);
