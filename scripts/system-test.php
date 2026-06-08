<?php

/**
 * Full system smoke test — run: php scripts/system-test.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$host = config('app.central_domain');
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$results = [];
$passed = 0;
$failed = 0;

function record(array &$results, int &$passed, int &$failed, string $group, string $name, bool $ok, string $detail = ''): void
{
    $results[] = compact('group', 'name', 'ok', 'detail');
    $ok ? $passed++ : $failed++;
}

function httpGet(string $url, ?string $host = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $host ? ["Host: {$host}"] : [],
    ]);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['status' => $status, 'body' => (string) $body];
}

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
    $r = httpGet("http://{$host}{$path}", $host);
    $ok = $r['status'] >= 200 && $r['status'] < 400;
    record($results, $passed, $failed, 'HTTP Central', $name, $ok, "HTTP {$r['status']}");
}

// Protected central routes should redirect (302)
foreach (['Admin dashboard' => '/admin', 'Billing page' => '/billing/demo'] as $name => $path) {
    $r = httpGet("http://{$host}{$path}", $host);
    $ok = in_array($r['status'], [302, 303], true);
    record($results, $passed, $failed, 'HTTP Central', "{$name} (guest → redirect)", $ok, "HTTP {$r['status']}");
}

// Landing content checks
$landing = httpGet("http://{$host}/", $host);
record($results, $passed, $failed, 'Content', 'Landing has hero text', stripos($landing['body'], 'multi-tenant') !== false);
record($results, $passed, $failed, 'Content', 'Landing has architecture section', str_contains($landing['body'], 'tenant:provision'));
record($results, $passed, $failed, 'Content', 'Landing CSS assets', str_contains($landing['body'], '/build/assets/'));

// Locale switch + Arabic translations
$localeSwitch = httpGet("http://{$host}/locale/ar", $host);
record($results, $passed, $failed, 'Localization', 'Locale switch route', in_array($localeSwitch['status'], [302, 303], true), "HTTP {$localeSwitch['status']}");
record($results, $passed, $failed, 'Localization', 'Enabled locales include en,ar', \App\Support\Locales::isEnabled('en') && \App\Support\Locales::isEnabled('ar'));
app()->setLocale('ar');
record($results, $passed, $failed, 'Localization', 'Arabic app translation', __('app.landing.architecture') === 'البنية');
record($results, $passed, $failed, 'Localization', 'Arabic RTL direction', \App\Support\Locales::direction('ar') === 'rtl');
app()->setLocale('en');

// ── HTTP — Tenant (guest) ─────────────────────────────────────────────
$tenantHost = "demo.{$host}";
foreach (['Tenant home' => '/', 'Tenant login' => '/login', 'Tenant register' => '/register'] as $name => $path) {
    $r = httpGet("http://{$tenantHost}{$path}", $tenantHost);
    $ok = $r['status'] >= 200 && $r['status'] < 400;
    record($results, $passed, $failed, 'HTTP Tenant', $name, $ok, "HTTP {$r['status']}");
}

$dash = httpGet("http://{$tenantHost}/dashboard", $tenantHost);
record($results, $passed, $failed, 'HTTP Tenant', 'Dashboard (guest → redirect)', in_array($dash['status'], [302, 303], true), "HTTP {$dash['status']}");

$tenantHome = httpGet("http://{$tenantHost}/", $tenantHost);
record($results, $passed, $failed, 'Content', 'Tenant CSS via /build/', str_contains($tenantHome['body'], '/build/assets/'));

// ── Authenticated kernel tests (non-Livewire routes) ─────────────────
if ($admin) {
    Illuminate\Support\Facades\Auth::login($admin);
    $request = Illuminate\Http\Request::create("http://{$host}/billing/demo", 'GET');
    $request->headers->set('HOST', $host);
    $response = $kernel->handle($request);
    $status = $response->getStatusCode();
    $kernel->terminate($request, $response);
    record($results, $passed, $failed, 'Auth Central', 'Billing demo', $status >= 200 && $status < 400, "HTTP {$status}");
    Illuminate\Support\Facades\Auth::logout();
}

// Filament admin uses Livewire — verify via HTTP redirect chain instead
$adminDash = httpGet("http://{$host}/admin", $host);
record($results, $passed, $failed, 'Filament', 'Admin reachable (login or dashboard)', in_array($adminDash['status'], [200, 302], true), 'test in browser after login');

if ($tenant && isset($demoUser) && $demoUser) {
    tenancy()->initialize($tenant);
    Illuminate\Support\Facades\Auth::login($demoUser);
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
    Illuminate\Support\Facades\Auth::logout();
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
