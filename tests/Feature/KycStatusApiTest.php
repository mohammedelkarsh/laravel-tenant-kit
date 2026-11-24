<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\TenantProvisioner;
use App\Support\Kyc;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Contracts\TenantDatabaseManager;
use Tests\TestCase;

class KycStatusApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_guest_cannot_read_kyc_status(): void
    {
        $this->provisionTenant(
            subdomain: 'kycguest',
            name: 'KYC Guest',
            adminEmail: 'owner@kycguest.test',
        );

        $this->getJson(
            $this->tenantUrl('kycguest', '/api/kyc/status'),
            $this->tenantHeaders('kycguest'),
        )->assertUnauthorized();
    }

    public function test_authenticated_tenant_user_gets_kyc_status_payload(): void
    {
        $this->provisionTenant(
            subdomain: 'kycok',
            name: 'KYC OK',
            adminEmail: 'owner@kycok.test',
        );

        $tokenResponse = $this->postJson($this->tenantUrl('kycok', '/api/auth/token'), [
            'email' => 'owner@kycok.test',
            'password' => 'password',
            'device_name' => 'test',
            'abilities' => ['kyc:read'],
        ], $this->tenantHeaders('kycok'));

        $token = $tokenResponse->json('token');
        $this->assertNotEmpty($token);

        $response = $this->getJson(
            $this->tenantUrl('kycok', '/api/kyc/status'),
            array_merge($this->tenantHeaders('kycok'), [
                'Authorization' => 'Bearer '.$token,
            ]),
        );

        $response->assertOk()
            ->assertJsonStructure([
                'enabled',
                'package_installed',
                'ready',
                'latest',
            ]);

        $this->assertSame(Kyc::enabled(), $response->json('enabled'));
        $this->assertSame(Kyc::packageInstalled(), $response->json('package_installed'));
        $this->assertSame(Kyc::ready(), $response->json('ready'));
    }

    public function test_kyc_status_requires_kyc_read_ability(): void
    {
        $this->provisionTenant(
            subdomain: 'kycdeny',
            name: 'KYC Deny',
            adminEmail: 'owner@kycdeny.test',
        );

        $tokenResponse = $this->postJson($this->tenantUrl('kycdeny', '/api/auth/token'), [
            'email' => 'owner@kycdeny.test',
            'password' => 'password',
            'device_name' => 'test',
            'abilities' => ['team:read'],
        ], $this->tenantHeaders('kycdeny'));

        $token = $tokenResponse->json('token');

        $this->getJson(
            $this->tenantUrl('kycdeny', '/api/kyc/status'),
            array_merge($this->tenantHeaders('kycdeny'), [
                'Authorization' => 'Bearer '.$token,
            ]),
        )->assertForbidden();
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
