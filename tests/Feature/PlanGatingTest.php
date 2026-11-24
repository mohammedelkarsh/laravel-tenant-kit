<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Support\Kyc;
use App\Support\PlanFeatures;
use App\Support\SubscriptionPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanGatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_gating_disabled_allows_all_features(): void
    {
        config(['plan_gating.enabled' => false]);

        Tenant::withoutEvents(function (): void {
            Tenant::query()->create(['id' => 'opengate', 'name' => 'Open']);
        });

        $tenant = Tenant::query()->findOrFail('opengate');

        $this->assertTrue(PlanFeatures::allows($tenant, 'kyc'));
        $this->assertTrue(PlanFeatures::allows($tenant, 'agent'));
    }

    public function test_default_free_plan_denies_kyc_when_gating_enabled(): void
    {
        config([
            'plan_gating.enabled' => true,
            'plan_gating.default_plan' => 'free',
            'kyc.enabled' => true,
        ]);

        Tenant::withoutEvents(function (): void {
            Tenant::query()->create(['id' => 'freegate', 'name' => 'Free Gate']);
        });

        $tenant = Tenant::query()->findOrFail('freegate');

        $this->assertSame('free', PlanFeatures::planKeyFor($tenant));
        $this->assertFalse(PlanFeatures::allows($tenant, 'kyc'));
        $this->assertContains('teams', PlanFeatures::featuresFor($tenant));

        $tenant->run(function () use ($tenant): void {
            // Package may be missing in CI — ready requires package + plan.
            if (Kyc::packageInstalled()) {
                $this->assertFalse(Kyc::ready());
            }
            $this->assertFalse(PlanFeatures::allows($tenant, 'kyc'));
        });
    }

    public function test_subscription_presenter_includes_features(): void
    {
        config(['plan_gating.enabled' => true]);

        Tenant::withoutEvents(function (): void {
            Tenant::query()->create(['id' => 'featgate', 'name' => 'Feat Gate']);
        });

        $payload = SubscriptionPresenter::forTenant(Tenant::query()->findOrFail('featgate'));

        $this->assertTrue($payload['plan_gating']);
        $this->assertSame('free', $payload['plan']);
        $this->assertContains('api', $payload['features']);
        $this->assertNotContains('kyc', $payload['features']);
    }

    public function test_pro_features_include_kyc(): void
    {
        config([
            'plan_gating.enabled' => true,
            'plans.pro.stripe_price' => 'price_pro_test',
        ]);

        $this->assertContains('kyc', config('plans.pro.features'));
        $this->assertNotContains('kyc', config('plans.starter.features'));
        $this->assertNotContains('kyc', config('plans.free.features'));
    }
}
