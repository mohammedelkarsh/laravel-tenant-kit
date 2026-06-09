<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\PlatformAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_counts_workspaces_and_users(): void
    {
        User::factory()->count(2)->create();

        $this->createTenantRecord('acme', 'Acme Corp');
        $this->createTenantRecord('beta', 'Beta Inc');

        $analytics = app(PlatformAnalytics::class);

        $this->assertSame(2, $analytics->totalWorkspaces());
        $this->assertSame(2, $analytics->platformUsers());
        $this->assertSame(2, $analytics->workspacesThisMonth());
        $this->assertSame(0, $analytics->activeSubscriptions());
    }

    public function test_analytics_growth_chart_returns_six_months(): void
    {
        $old = $this->createTenantRecord('old', 'Old Workspace');
        $old->forceFill(['created_at' => now()->subMonths(2)])->save();

        $this->createTenantRecord('newco', 'New Co');

        $series = app(PlatformAnalytics::class)->workspacesPerMonth();

        $this->assertCount(6, $series);
        $this->assertTrue($series->sum('count') >= 2);
        $this->assertArrayHasKey('label', $series->first());
        $this->assertArrayHasKey('count', $series->first());
    }

    private function createTenantRecord(string $id, string $name): Tenant
    {
        return Tenant::withoutEvents(function () use ($id, $name): Tenant {
            $tenant = Tenant::query()->create([
                'id' => $id,
                'name' => $name,
            ]);

            $tenant->domains()->create(['domain' => $id]);

            return $tenant;
        });
    }
}
