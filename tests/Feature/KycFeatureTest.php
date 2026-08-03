<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantKycService;
use App\Services\TenantProvisioner;
use App\Support\Kyc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use KycAi\Laravel\Jobs\ProcessKycDocument;
use KycAi\Laravel\Models\KycVerification;
use Stancl\Tenancy\Contracts\TenantDatabaseManager;
use Tests\TestCase;

/**
 * End-to-end scenarios for optional KYC (v1.4).
 * Requires kyc-ai/laravel (require-dev / path install).
 */
class KycFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Kyc::packageInstalled()) {
            $this->markTestSkipped('kyc-ai/laravel is not installed.');
        }

        config([
            'kyc.enabled' => true,
            'kyc.audit.enabled' => true,
            'kyc.default_level' => 'standard',
            'kyc.extraction.default' => 'fake',
            'kyc.tenant.default_country' => 'sa',
            'kyc.external_verification.enabled' => false,
        ]);
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_package_ready_when_enabled(): void
    {
        $this->assertTrue(Kyc::ready());
    }

    public function test_workspace_panel_definition_when_ready(): void
    {
        $provider = new \App\Providers\Filament\WorkspacePanelProvider(app());
        $panel = $provider->panel(\Filament\Panel::make());

        $this->assertSame('workspace', $panel->getId());
        $this->assertSame('workspace', $panel->getPath());
        $this->assertContains(
            \App\Filament\Workspace\Resources\KycVerifications\KycVerificationResource::class,
            $panel->getResources(),
        );
    }

    public function test_guest_cannot_open_kyc_onboarding(): void
    {
        $this->provisionTenant('kyguest', 'KYC Guest');

        $this->withServerVariables($this->tenantHeaders('kyguest'))
            ->get($this->tenantUrl('kyguest', '/kyc'))
            ->assertRedirect();
    }

    public function test_onboarding_page_renders_for_owner(): void
    {
        [$tenant, $owner] = $this->provisionWithOwner('kycon', 'KYC Onboarding');

        $this->withServerVariables($this->tenantHeaders('kycon'))
            ->actingAs($owner)
            ->get($this->tenantUrl('kycon', '/kyc'))
            ->assertOk()
            ->assertSee(__('app.kyc.onboarding_title'), false);
    }

    public function test_onboarding_returns_404_when_flag_disabled(): void
    {
        config(['kyc.enabled' => false]);

        [$tenant, $owner] = $this->provisionWithOwner('kycoff', 'KYC Off');

        $this->withServerVariables($this->tenantHeaders('kycoff'))
            ->actingAs($owner)
            ->get($this->tenantUrl('kycoff', '/kyc'))
            ->assertNotFound();
    }

    public function test_sync_verify_creates_audit_record(): void
    {
        [$tenant, $owner] = $this->provisionWithOwner('kycsync', 'KYC Sync');

        $file = UploadedFile::fake()->create('id_front.jpg', 120, 'image/jpeg');

        $this->withServerVariables($this->tenantHeaders('kycsync'))
            ->actingAs($owner)
            ->post($this->tenantUrl('kycsync', '/kyc'), [
                'document' => $file,
            ])
            ->assertOk()
            ->assertSee(__('app.kyc.result_title'), false);

        $tenant->run(function () use ($owner): void {
            $this->assertDatabaseCount('kyc_verifications', 1);

            $row = KycVerification::query()->first();
            $this->assertNotNull($row);
            $this->assertSame((int) $owner->id, (int) $row->user_id);
            $this->assertSame('sa', $row->country);
            $this->assertContains($row->status, ['passed', 'failed', 'pending_review']);
        });
    }

    public function test_queued_verify_dispatches_process_job(): void
    {
        Queue::fake();

        [$tenant, $owner] = $this->provisionWithOwner('kycqueue', 'KYC Queue');

        $file = UploadedFile::fake()->create('id_queue.jpg', 120, 'image/jpeg');

        $this->withServerVariables($this->tenantHeaders('kycqueue'))
            ->actingAs($owner)
            ->post($this->tenantUrl('kycqueue', '/kyc'), [
                'document' => $file,
                'queue' => '1',
            ])
            ->assertRedirect(route('tenant.kyc.onboarding', absolute: false));

        Queue::assertPushed(ProcessKycDocument::class);
    }

    public function test_tenant_kyc_service_applies_per_tenant_settings(): void
    {
        $result = $this->provisionTenant('kycset', 'KYC Settings', 'owner@kycset.test');
        $tenant = $result['tenant'];

        $tenant->kyc_country = 'sa';
        $tenant->kyc_level = 'internal';
        $tenant->kyc_extraction_driver = 'fake';
        $tenant->save();

        $settings = $tenant->fresh()->kycSettings();

        $this->assertSame('sa', $settings['country']);
        $this->assertSame('internal', $settings['level']);
        $this->assertSame('fake', $settings['extraction_driver']);

        $tenant->run(function () use ($tenant): void {
            $applied = app(TenantKycService::class)->applyTenantConfig($tenant);

            $this->assertSame('fake', config('kyc.extraction.default'));
            $this->assertSame('internal', config('kyc.default_level'));
            $this->assertSame($tenant->kycSettings(), $applied);
        });
    }

    public function test_service_submit_verifies_inside_tenant_context(): void
    {
        [$tenant, $owner] = $this->provisionWithOwner('kycsvc', 'KYC Service');

        $file = UploadedFile::fake()->create('1001244084.jpg', 100, 'image/jpeg');

        $tenant->run(function () use ($file, $owner): void {
            $result = app(TenantKycService::class)->submit($file, $owner, queue: false);

            $this->assertTrue(method_exists($result, 'status'));
            $this->assertContains($result->status(), ['passed', 'failed', 'pending_review']);
            $this->assertGreaterThanOrEqual(1, KycVerification::query()->count());
        });
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function provisionWithOwner(string $subdomain, string $name): array
    {
        $result = $this->provisionTenant($subdomain, $name, $subdomain.'@example.test');
        $tenant = $result['tenant'];

        tenancy()->initialize($tenant);
        $owner = User::query()->where('email', $subdomain.'@example.test')->firstOrFail();
        tenancy()->end();

        // Re-initialize for actingAs against tenant DB user id space on next request via middleware.
        return [$tenant, $owner];
    }

    /**
     * @return array{tenant: Tenant, url: string}
     */
    private function provisionTenant(
        string $subdomain,
        string $name,
        ?string $adminEmail = null,
    ): array {
        $this->dropOrphanTenantDatabase($subdomain);

        return app(TenantProvisioner::class)->provision(
            subdomain: $subdomain,
            name: $name,
            adminEmail: $adminEmail,
            adminName: 'Workspace Owner',
            adminPassword: 'password',
        );
    }

    private function dropOrphanTenantDatabase(string $tenantId): void
    {
        $dbName = config('tenancy.database.prefix').$tenantId.config('tenancy.database.suffix', '');
        $connection = config('tenancy.database.central_connection', config('database.default'));
        $driver = config("database.connections.{$connection}.driver");
        $managerClass = config("tenancy.database.managers.{$driver}");

        if (! $managerClass) {
            return;
        }

        /** @var TenantDatabaseManager $manager */
        $manager = app($managerClass);
        $manager->setConnection($connection);

        if (! $manager->databaseExists($dbName)) {
            return;
        }

        if ($driver === 'sqlite') {
            $path = database_path($dbName);
            if (is_file($path)) {
                unlink($path);
            }

            return;
        }

        DB::connection($connection)->statement(
            $driver === 'pgsql'
                ? "DROP DATABASE \"{$dbName}\" WITH (FORCE)"
                : "DROP DATABASE `{$dbName}`"
        );
    }

    private function tenantUrl(string $subdomain, string $path): string
    {
        return 'http://'.$subdomain.'.'.config('app.central_domain').$path;
    }

    /**
     * @return array<string, string>
     */
    private function tenantHeaders(string $subdomain): array
    {
        return [
            'HTTP_HOST' => $subdomain.'.'.config('app.central_domain'),
        ];
    }
}
