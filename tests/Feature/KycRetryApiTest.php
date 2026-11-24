<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\TenantProvisioner;
use App\Support\Kyc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenantDatabaseManager;
use Tests\TestCase;

class KycRetryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_guest_cannot_retry_kyc(): void
    {
        $this->provisionTenant(
            subdomain: 'kycretryguest',
            name: 'KYC Retry Guest',
            adminEmail: 'owner@kycretryguest.test',
        );

        $this->postJson(
            $this->tenantUrl('kycretryguest', '/api/kyc/verifications/'.Str::uuid().'/retry'),
            [],
            $this->tenantHeaders('kycretryguest'),
        )->assertUnauthorized();
    }

    public function test_retry_requires_kyc_retry_ability(): void
    {
        $this->provisionTenant(
            subdomain: 'kycretrydeny',
            name: 'KYC Retry Deny',
            adminEmail: 'owner@kycretrydeny.test',
        );

        $tokenResponse = $this->postJson($this->tenantUrl('kycretrydeny', '/api/auth/token'), [
            'email' => 'owner@kycretrydeny.test',
            'password' => 'password',
            'device_name' => 'test',
            'abilities' => ['kyc:read'],
        ], $this->tenantHeaders('kycretrydeny'));

        $token = $tokenResponse->json('token');

        $this->postJson(
            $this->tenantUrl('kycretrydeny', '/api/kyc/verifications/'.Str::uuid().'/retry'),
            [],
            array_merge($this->tenantHeaders('kycretrydeny'), [
                'Authorization' => 'Bearer '.$token,
            ]),
        )->assertForbidden();
    }

    public function test_retry_returns_not_found_when_kyc_not_ready(): void
    {
        config(['kyc.enabled' => false]);

        $this->provisionTenant(
            subdomain: 'kycretryoff',
            name: 'KYC Retry Off',
            adminEmail: 'owner@kycretryoff.test',
        );

        $tokenResponse = $this->postJson($this->tenantUrl('kycretryoff', '/api/auth/token'), [
            'email' => 'owner@kycretryoff.test',
            'password' => 'password',
            'device_name' => 'test',
            'abilities' => ['kyc:retry'],
        ], $this->tenantHeaders('kycretryoff'));

        $token = $tokenResponse->json('token');

        $this->postJson(
            $this->tenantUrl('kycretryoff', '/api/kyc/verifications/'.Str::uuid().'/retry'),
            [],
            array_merge($this->tenantHeaders('kycretryoff'), [
                'Authorization' => 'Bearer '.$token,
            ]),
        )->assertNotFound();
    }

    public function test_retry_queues_national_id_job_when_package_ready(): void
    {
        if (! Kyc::packageInstalled()) {
            $this->markTestSkipped('kyc-ai/laravel is not installed.');
        }

        config([
            'kyc.enabled' => true,
            'kyc.audit.enabled' => true,
            'kyc.default_level' => 'internal',
            'kyc.tenant.default_country' => 'sa',
            'kyc.external_verification.enabled' => false,
        ]);

        Queue::fake();

        $provisioned = $this->provisionTenant(
            subdomain: 'kycretryok',
            name: 'KYC Retry OK',
            adminEmail: 'owner@kycretryok.test',
        );

        $uuid = (string) Str::uuid();

        $provisioned['tenant']->run(function () use ($uuid): void {
            \KycAi\Laravel\Models\KycVerification::query()->create([
                'uuid' => $uuid,
                'user_id' => 1,
                'country' => 'sa',
                'national_id' => '1234567890',
                'level' => 'internal',
                'status' => 'failed',
                'passed' => false,
            ]);
        });

        $tokenResponse = $this->postJson($this->tenantUrl('kycretryok', '/api/auth/token'), [
            'email' => 'owner@kycretryok.test',
            'password' => 'password',
            'device_name' => 'test',
            'abilities' => ['kyc:retry'],
        ], $this->tenantHeaders('kycretryok'));

        $token = $tokenResponse->json('token');

        $response = $this->postJson(
            $this->tenantUrl('kycretryok', '/api/kyc/verifications/'.$uuid.'/retry'),
            ['queue' => true],
            array_merge($this->tenantHeaders('kycretryok'), [
                'Authorization' => 'Bearer '.$token,
            ]),
        );

        $response->assertAccepted()
            ->assertJsonPath('queued', true)
            ->assertJsonPath('mode', 'national_id_only')
            ->assertJsonPath('source_uuid', $uuid);

        Queue::assertPushed(\KycAi\Laravel\Jobs\ProcessKycDocument::class);
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
