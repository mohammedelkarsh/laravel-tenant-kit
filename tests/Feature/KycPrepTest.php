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
    }

    public function test_kyc_helper_respects_config(): void
    {
        config(['kyc.enabled' => true]);

        $this->assertTrue(Kyc::enabled());
    }

    public function test_no_kyc_routes_when_disabled(): void
    {
        config(['kyc.enabled' => false]);

        $kycRoutes = collect(Route::getRoutes())->filter(function ($route) {
            $name = (string) $route->getName();
            $uri = (string) $route->uri();

            return str_contains(strtolower($name), 'kyc')
                || str_contains(strtolower($uri), 'kyc');
        });

        $this->assertTrue($kycRoutes->isEmpty(), 'Expected no KYC routes when KYC is disabled.');
    }

    public function test_no_kyc_filament_panel_when_disabled(): void
    {
        config(['kyc.enabled' => false]);

        $panelIds = collect(Filament::getPanels())->keys();

        $this->assertFalse($panelIds->contains('kyc'));

        foreach (Filament::getPanels() as $panel) {
            foreach ($panel->getPlugins() as $plugin) {
                $this->assertStringNotContainsStringIgnoringCase(
                    'kyc',
                    $plugin::class,
                    'Expected no KYC Filament plugin when KYC is disabled.',
                );
            }
        }
    }
}
