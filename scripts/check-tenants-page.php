<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$host = config('app.central_domain');
$admin = App\Models\User::where('email', 'admin@laravel-tenant-kit.test')->first();

if (! $admin) {
    echo "FAIL: admin user missing\n";
    exit(1);
}

echo 'intl_loaded='.(extension_loaded('intl') ? 'yes' : 'no')."\n";

Illuminate\Support\Facades\Auth::guard('web')->login($admin);

$request = Illuminate\Http\Request::create("http://{$host}/admin/tenants", 'GET');
$request->headers->set('HOST', $host);

try {
    $response = $kernel->handle($request);
    $status = $response->getStatusCode();
    $body = $response->getContent();
    $kernel->terminate($request, $response);

    echo "status={$status}\n";

    if ($status >= 500 || str_contains($body, 'RuntimeException') || str_contains($body, 'intl')) {
        echo substr($body, 0, 800)."\n";
        exit(1);
    }

    echo str_contains($body, 'Workspaces') || str_contains($body, 'demo') ? "content=ok\n" : "content=unexpected\n";
    exit($status >= 200 && $status < 400 ? 0 : 1);
} catch (Throwable $e) {
    echo 'EXCEPTION: '.$e->getMessage()."\n";
    exit(1);
}
