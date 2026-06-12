<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Stancl\Tenancy\Contracts\TenantDatabaseManager;
use Tests\TestCase;

class ApiV121Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('api-auth');
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_central_token_includes_default_abilities(): void
    {
        $user = User::factory()->create([
            'email' => 'api@example.test',
            'password' => 'password',
        ]);

        $response = $this->postJson($this->centralUrl('/api/auth/token'), [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'test',
        ]);

        $response->assertOk()
            ->assertJsonPath('abilities', config('api.central_abilities'));
    }

    public function test_limited_token_cannot_list_workspaces(): void
    {
        $user = User::factory()->create([
            'email' => 'limited@example.test',
            'password' => 'password',
        ]);

        $token = $user->createToken('limited', ['user:read'])->plainTextToken;

        $this->getJson($this->centralUrl('/api/workspaces'), [
            'Authorization' => 'Bearer '.$token,
        ])->assertForbidden();
    }

    public function test_api_auth_is_rate_limited(): void
    {
        config([
            'api.rate_limit.auth_attempts' => 2,
            'api.rate_limit.auth_decay_minutes' => 1,
        ]);

        RateLimiter::clear('api-auth');

        $payload = [
            'email' => 'nobody@example.test',
            'password' => 'wrong',
            'device_name' => 'test',
        ];

        $this->postJson($this->centralUrl('/api/auth/token'), $payload)->assertStatus(422);
        $this->postJson($this->centralUrl('/api/auth/token'), $payload)->assertStatus(422);
        $this->postJson($this->centralUrl('/api/auth/token'), $payload)->assertStatus(429);
    }

    public function test_workspace_subscription_endpoint_returns_payload(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        Tenant::withoutEvents(function (): void {
            $tenant = Tenant::query()->create(['id' => 'billing', 'name' => 'Billing Test']);
            $tenant->domains()->create(['domain' => 'billing']);
        });

        $token = $user->createToken('test', ['workspaces:read'])->plainTextToken;

        $this->getJson($this->centralUrl('/api/workspaces/billing/subscription'), [
            'Authorization' => 'Bearer '.$token,
        ])
            ->assertOk()
            ->assertJsonPath('data.workspace_id', 'billing')
            ->assertJsonPath('data.subscribed', false);
    }

    public function test_tenant_can_invite_teammate_via_api(): void
    {
        $this->provisionTenant(
            subdomain: 'invite',
            name: 'Invite Workspace',
            adminEmail: 'owner@invite.test',
            adminPassword: 'password',
        );

        $tokenResponse = $this->postJson($this->tenantUrl('invite', '/api/auth/token'), [
            'email' => 'owner@invite.test',
            'password' => 'password',
            'device_name' => 'test',
        ], $this->tenantHeaders('invite'));

        $token = $tokenResponse->json('token');

        $this->postJson($this->tenantUrl('invite', '/api/team/invitations'), [
            'email' => 'new@invite.test',
            'role' => 'member',
        ], array_merge($this->tenantHeaders('invite'), [
            'Authorization' => 'Bearer '.$token,
        ]))
            ->assertCreated()
            ->assertJsonPath('data.email', 'new@invite.test');
    }

    public function test_suspended_workspace_blocks_tenant_api(): void
    {
        $result = $this->provisionTenant(
            subdomain: 'paused',
            name: 'Paused Workspace',
            adminEmail: 'owner@paused.test',
            adminPassword: 'password',
        );

        $result['tenant']->update(['suspended_at' => now()]);

        $this->postJson($this->tenantUrl('paused', '/api/auth/token'), [
            'email' => 'owner@paused.test',
            'password' => 'password',
            'device_name' => 'test',
        ], $this->tenantHeaders('paused'))
            ->assertForbidden();
    }

    /**
     * @return array{tenant: Tenant, url: string}
     */
    private function provisionTenant(
        string $subdomain,
        string $name,
        ?string $adminEmail = null,
        ?string $adminPassword = null,
    ): array {
        $this->dropOrphanTenantDatabase($subdomain);

        return app(TenantProvisioner::class)->provision(
            subdomain: $subdomain,
            name: $name,
            adminEmail: $adminEmail,
            adminName: 'Workspace Owner',
            adminPassword: $adminPassword ?? 'password',
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

    private function centralUrl(string $path): string
    {
        return 'http://'.config('app.central_domain').$path;
    }

    private function tenantUrl(string $subdomain, string $path): string
    {
        $host = $subdomain.'.'.config('app.central_domain');

        return 'http://'.$host.$path;
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
