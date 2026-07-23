<?php

namespace Tests\Feature;

use App\Support\Kyc;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class KycPrepTest extends TestCase
{
    public function test_kyc_is_disabled_by_default(): void
    {
        $this->assertFalse(config('kyc.enabled'));
        $this->assertFalse(Kyc::enabled());
        $this->assertFalse(Kyc::ready());
    }

    public function test_kyc_helper_respects_config(): void
    {
        config(['kyc.enabled' => true]);

        $this->assertTrue(Kyc::enabled());
        $this->assertSame(Kyc::packageInstalled(), Kyc::ready());
    }

    public function test_kyc_routes_only_when_package_installed(): void
    {
        $kycRoutes = collect(Route::getRoutes())->filter(function ($route) {
            $name = (string) $route->getName();

            return str_starts_with($name, 'tenant.kyc.');
        });

        if (Kyc::packageInstalled()) {
            $this->assertFalse($kycRoutes->isEmpty(), 'Expected KYC routes when package is installed.');
        } else {
            $this->assertTrue($kycRoutes->isEmpty(), 'Expected no KYC routes when package is missing.');
        }
    }

    public function test_no_workspace_panel_when_not_ready(): void
    {
        config(['kyc.enabled' => false]);

        $panelIds = array_keys(Filament::getPanels());

        $this->assertNotContains('kyc', $panelIds);
        $this->assertNotContains('workspace', $panelIds);

        foreach (Filament::getPanels() as $panel) {
            foreach ($panel->getPlugins() as $plugin) {
                $this->assertStringNotContainsStringIgnoringCase(
                    'kyc',
                    $plugin::class,
                    'Expected no KYC Filament plugin when KYC is not ready.',
                );
            }
        }
    }

    public function test_tenant_migration_for_kyc_verifications_exists(): void
    {
        $path = database_path('migrations/tenant/2026_07_23_000001_create_kyc_verifications_table.php');

        $this->assertFileExists($path);
    }
}
