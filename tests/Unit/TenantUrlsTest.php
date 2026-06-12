<?php

namespace Tests\Unit;

use App\Support\TenantUrls;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class TenantUrlsTest extends TestCase
{
    public function test_demo_url_includes_app_url_port(): void
    {
        Config::set('app.url', 'http://laravel-tenant-kit.test:8080');
        Config::set('app.central_domain', 'laravel-tenant-kit.test');

        $this->assertSame(
            'http://demo.laravel-tenant-kit.test:8080',
            TenantUrls::demo(),
        );
    }

    public function test_demo_url_omits_port_when_app_url_has_none(): void
    {
        Config::set('app.url', 'http://laravel-tenant-kit.test');
        Config::set('app.central_domain', 'laravel-tenant-kit.test');

        putenv('APP_PORT_ALT');
        putenv('APP_PORT');
        unset($_ENV['APP_PORT_ALT'], $_SERVER['APP_PORT_ALT'], $_ENV['APP_PORT'], $_SERVER['APP_PORT']);

        $this->assertSame(
            'http://demo.laravel-tenant-kit.test',
            TenantUrls::demo(),
        );
    }

    public function test_host_label_includes_app_url_port(): void
    {
        Config::set('app.url', 'http://laravel-tenant-kit.test:8080');
        Config::set('app.central_domain', 'laravel-tenant-kit.test');

        $this->assertSame(
            'demo.laravel-tenant-kit.test:8080',
            TenantUrls::hostLabel('demo'),
        );
    }

    public function test_demo_url_uses_app_port_alt_when_app_url_has_no_port(): void
    {
        Config::set('app.url', 'http://laravel-tenant-kit.test');
        Config::set('app.central_domain', 'laravel-tenant-kit.test');

        putenv('APP_PORT_ALT=8080');
        $_ENV['APP_PORT_ALT'] = '8080';
        $_SERVER['APP_PORT_ALT'] = '8080';

        $this->assertSame(
            'http://demo.laravel-tenant-kit.test:8080',
            TenantUrls::demo(),
        );
    }
}
