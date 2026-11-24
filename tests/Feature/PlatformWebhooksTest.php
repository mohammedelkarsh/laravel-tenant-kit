<?php

namespace Tests\Feature;

use App\Jobs\DeliverPlatformWebhook;
use App\Listeners\DispatchKycPlatformWebhook;
use App\Models\Tenant;
use App\Services\PlatformWebhookDispatcher;
use App\Services\TenantProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenantDatabaseManager;
use Tests\TestCase;

class PlatformWebhooksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'webhooks.enabled' => true,
            'webhooks.url' => 'https://hooks.example.test/tenant-kit',
            'webhooks.secret' => 'test-secret',
            'webhooks.queue' => true,
            'webhooks.timeout' => 5,
        ]);
    }

    public function test_dispatcher_is_noop_when_disabled(): void
    {
        config(['webhooks.enabled' => false]);
        Queue::fake();

        app(PlatformWebhookDispatcher::class)->dispatch('workspace.created', [
            'workspace_id' => 'demo',
        ]);

        Queue::assertNothingPushed();
    }

    public function test_provisioning_queues_workspace_created_webhook(): void
    {
        Queue::fake();
        $this->dropOrphanTenantDatabase('hookcreate');

        app(TenantProvisioner::class)->provision(
            subdomain: 'hookcreate',
            name: 'Hook Create',
            adminEmail: 'owner@hookcreate.test',
        );

        Queue::assertPushed(DeliverPlatformWebhook::class, function (DeliverPlatformWebhook $job): bool {
            return $job->event === 'workspace.created'
                && ($job->envelope['data']['workspace_id'] ?? null) === 'hookcreate';
        });
    }

    public function test_suspend_queues_workspace_suspended_webhook(): void
    {
        Queue::fake();

        Tenant::withoutEvents(function (): void {
            Tenant::query()->create(['id' => 'hooksuspend', 'name' => 'Hook Suspend']);
        });

        $tenant = Tenant::query()->findOrFail('hooksuspend');
        $tenant->update(['suspended_at' => now()]);

        Queue::assertPushed(DeliverPlatformWebhook::class, function (DeliverPlatformWebhook $job): bool {
            return $job->event === 'workspace.suspended'
                && ($job->envelope['data']['workspace_id'] ?? null) === 'hooksuspend';
        });
    }

    public function test_delivery_posts_signed_json_payload(): void
    {
        Http::fake([
            'hooks.example.test/*' => Http::response(['ok' => true], 200),
        ]);

        $envelope = [
            'id' => '11111111-1111-1111-1111-111111111111',
            'event' => 'workspace.created',
            'created_at' => now()->toIso8601String(),
            'data' => ['workspace_id' => 'demo', 'name' => 'Demo'],
        ];

        (new DeliverPlatformWebhook('workspace.created', $envelope))->handle();

        Http::assertSent(function ($request) use ($envelope): bool {
            $body = $request->body();
            $expected = 'sha256='.hash_hmac('sha256', $body, 'test-secret');
            $decoded = json_decode($body, true);

            return $request->url() === 'https://hooks.example.test/tenant-kit'
                && $request->hasHeader('X-Tenant-Kit-Event', 'workspace.created')
                && $request->hasHeader('X-Tenant-Kit-Delivery', $envelope['id'])
                && $request->hasHeader('X-Tenant-Kit-Signature', $expected)
                && ($decoded['event'] ?? null) === 'workspace.created';
        });
    }

    public function test_kyc_verification_status_update_queues_webhook(): void
    {
        if (! class_exists(\KycAi\Laravel\Models\KycVerification::class)) {
            $this->markTestSkipped('kyc-ai/laravel is not installed.');
        }

        Queue::fake();

        $result = $this->provisionTenant('kycwebhooks', 'KYC Webhooks', 'owner@kycwebhooks.test');

        $result['tenant']->run(function (): void {
            $row = \KycAi\Laravel\Models\KycVerification::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => 1,
                'country' => 'sa',
                'national_id' => '1234567890',
                'level' => 'internal',
                'status' => 'pending_review',
                'passed' => false,
            ]);

            $row->update([
                'status' => 'passed',
                'passed' => true,
            ]);
        });

        Queue::assertPushed(DeliverPlatformWebhook::class, function (DeliverPlatformWebhook $job): bool {
            return $job->event === 'kyc.verification.passed'
                && ($job->envelope['data']['workspace_id'] ?? null) === 'kycwebhooks'
                && ! array_key_exists('national_id', $job->envelope['data'] ?? []);
        });
    }

    public function test_kyc_event_listener_maps_pending_review(): void
    {
        if (! class_exists(\KycAi\Laravel\Events\KycFailed::class)) {
            $this->markTestSkipped('kyc-ai/laravel is not installed.');
        }

        Queue::fake();

        $result = new \KycAi\Laravel\Results\KycResult(
            passed: true,
            country: 'sa',
            level: \KycAi\Laravel\KycLevel::Standard,
            extraction: null,
            internal: null,
            external: null,
            warnings: ['manual_review_recommended'],
            failureReason: null,
            needsManualReview: true,
        );

        $provisioned = $this->provisionTenant('kycpending', 'KYC Pending', 'owner@kycpending.test');

        tenancy()->initialize($provisioned['tenant']);
        try {
            app(DispatchKycPlatformWebhook::class)->handle(
                new \KycAi\Laravel\Events\KycFailed($result),
            );
        } finally {
            tenancy()->end();
        }

        Queue::assertPushed(DeliverPlatformWebhook::class, function (DeliverPlatformWebhook $job): bool {
            return $job->event === 'kyc.verification.pending_review'
                && ($job->envelope['data']['workspace_id'] ?? null) === 'kycpending';
        });
    }

    /**
     * @return array{tenant: Tenant, url: string}
     */
    private function provisionTenant(string $subdomain, string $name, string $adminEmail): array
    {
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
}
