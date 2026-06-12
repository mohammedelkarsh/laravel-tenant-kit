<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentTenantsTest extends TestCase
{
    use RefreshDatabase;

    public function test_intl_extension_is_available(): void
    {
        $this->assertTrue(
            extension_loaded('intl'),
            'The intl PHP extension is required for Filament tables and Laravel Number formatting.',
        );
    }

    public function test_filament_tenants_page_renders_for_admin(): void
    {
        config(['app.url' => 'http://laravel-tenant-kit.test:8080']);

        $admin = User::factory()->create();

        Tenant::withoutEvents(function (): void {
            $tenant = Tenant::query()->create(['id' => 'demo', 'name' => 'Demo Workspace']);
            $tenant->domains()->create(['domain' => 'demo']);
        });

        $response = $this->actingAs($admin)->get('/admin/tenants');

        $response->assertOk();
        $response->assertSee('Demo Workspace', false);
        $response->assertSee('demo.laravel-tenant-kit.test:8080', false);
        $response->assertSee('http://demo.laravel-tenant-kit.test:8080', false);
    }

}
