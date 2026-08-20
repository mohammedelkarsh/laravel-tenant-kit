<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UsageRecord;
use App\Services\TenantProvisioner;
use App\Services\UsageMeter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Contracts\TenantDatabaseManager;
use Tests\TestCase;

class UsageBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_usage_endpoint_returns_meter_summary(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        Tenant::withoutEvents(function (): void {
            $tenant = Tenant::query()->create(['id' => 'usage', 'name' => 'Usage Test']);
            $tenant->domains()->create(['domain' => 'usage']);
        });

        $token = $user->createToken('test', ['workspaces:read'])->plainTextToken;

        $this->getJson($this->centralUrl('/api/workspaces/usage/usage'), [
            'Authorization' => 'Bearer '.$token,
        ])
            ->assertOk()
            ->assertJsonPath('data.workspace_id', 'usage')
            ->assertJsonStructure([
                'data' => [
                    'period_start',
                    'period_end',
                    'meters' => [
                        'api_calls' => ['label', 'quantity', 'event_name'],
                        'team_seats' => ['label', 'quantity', 'event_name'],
                        'agent_calls' => ['label', 'quantity', 'event_name'],
                    ],
                ],
            ]);
    }

    public function test_subscription_endpoint_includes_usage(): void
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
            ->assertJsonPath('data.usage.meters.api_calls.label', 'API calls');
    }

    public function test_workspace_api_call_increments_api_calls_meter(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        Tenant::withoutEvents(function (): void {
            $tenant = Tenant::query()->create(['id' => 'metered', 'name' => 'Metered']);
            $tenant->domains()->create(['domain' => 'metered']);
        });

        $token = $user->createToken('test', ['workspaces:read'])->plainTextToken;

        $this->getJson($this->centralUrl('/api/workspaces/metered/usage'), [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $record = UsageRecord::query()
            ->where('tenant_id', 'metered')
            ->where('meter', 'api_calls')
            ->first();

        $this->assertNotNull($record);
        $this->assertGreaterThanOrEqual(1, $record->quantity);
    }

    public function test_usage_meter_snapshots_team_seats_from_tenant_database(): void
    {
        $result = $this->provisionTenant(
            subdomain: 'seats',
            name: 'Seats Workspace',
            adminEmail: 'owner@seats.test',
        );

        $record = app(UsageMeter::class)->snapshotTeamSeats($result['tenant']);

        $this->assertNotNull($record);
        $this->assertSame(1, $record->quantity);
    }

    public function test_usage_billing_can_be_disabled(): void
    {
        config(['usage.enabled' => false]);

        $user = User::factory()->create(['password' => 'password']);

        Tenant::withoutEvents(function (): void {
            $tenant = Tenant::query()->create(['id' => 'off', 'name' => 'Off']);
            $tenant->domains()->create(['domain' => 'off']);
        });

        $token = $user->createToken('test', ['workspaces:read'])->plainTextToken;

        $this->getJson($this->centralUrl('/api/workspaces/off/usage'), [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $this->assertSame(0, UsageRecord::query()->count());
    }

    public function test_usage_endpoint_requires_authentication(): void
    {
        Tenant::withoutEvents(function (): void {
            $tenant = Tenant::query()->create(['id' => 'auth', 'name' => 'Auth Test']);
            $tenant->domains()->create(['domain' => 'auth']);
        });

        $this->getJson($this->centralUrl('/api/workspaces/auth/usage'))
            ->assertUnauthorized();
    }

    public function test_usage_endpoint_requires_workspaces_read_ability(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        Tenant::withoutEvents(function (): void {
            $tenant = Tenant::query()->create(['id' => 'scoped', 'name' => 'Scoped']);
            $tenant->domains()->create(['domain' => 'scoped']);
        });

        $token = $user->createToken('limited', ['user:read'])->plainTextToken;

        $this->getJson($this->centralUrl('/api/workspaces/scoped/usage'), [
            'Authorization' => 'Bearer '.$token,
        ])->assertForbidden();
    }

    public function test_usage_endpoint_returns_404_for_unknown_workspace(): void
    {
        $user = User::factory()->create(['password' => 'password']);
        $token = $user->createToken('test', ['workspaces:read'])->plainTextToken;

        $this->getJson($this->centralUrl('/api/workspaces/missing/usage'), [
            'Authorization' => 'Bearer '.$token,
        ])->assertNotFound();
    }

    public function test_central_workspace_list_does_not_increment_usage(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        Tenant::withoutEvents(function (): void {
            $tenant = Tenant::query()->create(['id' => 'listed', 'name' => 'Listed']);
            $tenant->domains()->create(['domain' => 'listed']);
        });

        $token = $user->createToken('test', ['workspaces:read'])->plainTextToken;

        $this->getJson($this->centralUrl('/api/workspaces'), [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $this->assertSame(0, UsageRecord::query()->count());
    }

    public function test_failed_api_response_does_not_increment_usage(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        Tenant::withoutEvents(function (): void {
            $tenant = Tenant::query()->create(['id' => 'fail', 'name' => 'Fail']);
            $tenant->domains()->create(['domain' => 'fail']);
        });

        $token = $user->createToken('limited', ['user:read'])->plainTextToken;

        $this->getJson($this->centralUrl('/api/workspaces/fail/usage'), [
            'Authorization' => 'Bearer '.$token,
        ])->assertForbidden();

        $this->assertSame(0, UsageRecord::query()->count());
    }

    public function test_tenant_api_call_increments_api_calls_meter(): void
    {
        $this->provisionTenant(
            subdomain: 'tenantapi',
            name: 'Tenant API Workspace',
            adminEmail: 'owner@tenantapi.test',
        );

        $tokenResponse = $this->postJson($this->tenantUrl('tenantapi', '/api/auth/token'), [
            'email' => 'owner@tenantapi.test',
            'password' => 'password',
            'device_name' => 'test',
        ], $this->tenantHeaders('tenantapi'));

        $token = $tokenResponse->json('token');

        $this->getJson($this->tenantUrl('tenantapi', '/api/team'), array_merge(
            $this->tenantHeaders('tenantapi'),
            ['Authorization' => 'Bearer '.$token],
        ))->assertOk();

        $record = UsageRecord::query()
            ->where('tenant_id', 'tenantapi')
            ->where('meter', 'api_calls')
            ->first();

        $this->assertNotNull($record);
        $this->assertGreaterThanOrEqual(1, $record->quantity);
    }

    public function test_billing_page_shows_usage_section(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        Tenant::withoutEvents(function (): void {
            $tenant = Tenant::query()->create(['id' => 'demo', 'name' => 'Demo']);
            $tenant->domains()->create(['domain' => 'demo']);
        });

        $response = $this->actingAs($user)->get($this->centralUrl('/billing/demo'));

        $response->assertOk()
            ->assertSee(__('app.billing.usage_title'), false);
    }

    public function test_summarize_returns_zero_quantities_without_records(): void
    {
        Tenant::withoutEvents(function (): void {
            $tenant = Tenant::query()->create(['id' => 'empty', 'name' => 'Empty']);
            $tenant->domains()->create(['domain' => 'empty']);
        });

        $tenant = Tenant::query()->find('empty');
        $summary = app(UsageMeter::class)->summarize($tenant);

        $this->assertSame(0, $summary['meters']['api_calls']['quantity']);
        $this->assertSame(0, $summary['meters']['team_seats']['quantity']);
        $this->assertSame(0, $summary['meters']['agent_calls']['quantity']);
        $this->assertSame('Agent calls', $summary['meters']['agent_calls']['label']);
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

    private function centralUrl(string $path): string
    {
        return 'http://'.config('app.central_domain').$path;
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
