<?php

namespace Tests\Feature;

use App\Jobs\DeliverPlatformWebhook;
use App\Models\Tenant;
use App\Services\PlatformWebhookDispatcher;
use App\Services\TenantProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
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
}
