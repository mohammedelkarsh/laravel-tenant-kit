<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\UsageRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ApiOperatorChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_use_api_operator_chat(): void
    {
        config(['api_operator.enabled' => true]);

        $this->postJson(route('api-operator.chat'), ['message' => 'list workspaces'])
            ->assertRedirect();
    }

    public function test_authenticated_user_can_chat_when_service_is_up(): void
    {
        config([
            'api_operator.enabled' => true,
            'api_operator.url' => 'http://api-operator.test',
            'api_operator.token' => 'test-token',
        ]);

        Http::fake([
            'http://api-operator.test/health' => Http::response(['status' => 'ok']),
            'http://api-operator.test/v1/chat' => Http::response([
                'session_id' => 'sess-1',
                'message' => 'list_workspaces succeeded',
                'status' => 'ok',
            ]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api-operator.chat'), ['message' => 'list workspaces'])
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        Http::assertSent(function ($request) {
            return $request->url() === 'http://api-operator.test/v1/chat'
                && $request['message'] === 'list workspaces'
                && $request['adapter'] === 'yaml'
                && $request['adapter_config']['token'] === 'test-token';
        });
    }

    public function test_chat_returns_503_when_operator_unreachable(): void
    {
        config([
            'api_operator.enabled' => true,
            'api_operator.url' => 'http://127.0.0.1:1',
            'api_operator.token' => 'test-token',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api-operator.chat'), ['message' => 'list workspaces'])
            ->assertStatus(503);
    }

    public function test_status_reports_health(): void
    {
        config([
            'api_operator.enabled' => true,
            'api_operator.url' => 'http://api-operator.test',
        ]);

        Http::fake([
            'http://api-operator.test/health' => Http::response(['status' => 'ok']),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('api-operator.status'))
            ->assertOk()
            ->assertJson(['enabled' => true, 'healthy' => true]);
    }

    public function test_dashboard_renders_widget_with_quick_action_buttons(): void
    {
        config(['api_operator.enabled' => true]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('http://'.config('app.central_domain').'/dashboard')
            ->assertOk()
            ->assertSee('data-api-operator-fab', false)
            ->assertSee('data-guided-agent="1"', false)
            ->assertSee('data-api-operator-quick-actions', false);
    }

    public function test_chat_forwards_confirm_status_from_operator(): void
    {
        config([
            'api_operator.enabled' => true,
            'api_operator.url' => 'http://api-operator.test',
            'api_operator.token' => 'test-token',
        ]);

        Http::fake([
            'http://api-operator.test/health' => Http::response(['status' => 'ok']),
            'http://api-operator.test/v1/chat' => Http::response([
                'session_id' => 'sess-confirm',
                'message' => "Confirm create_workspace(subdomain='acme', name='Acme')? Reply yes to proceed or no to cancel.",
                'status' => 'confirm',
                'tool' => 'create_workspace',
            ]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api-operator.chat'), [
                'message' => 'create workspace Acme subdomain acme',
                'session_id' => 'sess-confirm',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'confirm')
            ->assertJsonPath('tool', 'create_workspace');
    }

    public function test_widget_includes_guided_menu_config(): void
    {
        config(['api_operator.enabled' => true]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('http://'.config('app.central_domain').'/dashboard');

        $response->assertOk();
        $response->assertSee('"menus"', false);
        $response->assertSee('create_workspace', false);
        $response->assertSee('chip_yes', false);
    }

    public function test_built_widget_asset_exists(): void
    {
        $manifest = json_decode(
            file_get_contents(public_path('build/manifest.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $entry = $manifest['resources/js/api-operator-widget.js']['file'] ?? null;
        $this->assertNotNull($entry);
        $this->assertFileExists(public_path('build/'.$entry));

        $js = file_get_contents(public_path('build/'.$entry));
        $this->assertStringContainsString('api-confirm', $js);
        $this->assertStringContainsString('confirm', $js);
        $this->assertStringContainsString('data-agent-chip', $js);
    }

    public function test_successful_chat_increments_agent_calls_for_workspace(): void
    {
        config([
            'api_operator.enabled' => true,
            'api_operator.url' => 'http://api-operator.test',
            'api_operator.token' => 'test-token',
            'usage.enabled' => true,
        ]);

        Tenant::withoutEvents(function (): void {
            $tenant = Tenant::query()->create(['id' => 'agentws', 'name' => 'Agent WS']);
            $tenant->domains()->create(['domain' => 'agentws']);
        });

        Http::fake([
            'http://api-operator.test/health' => Http::response(['status' => 'ok']),
            'http://api-operator.test/v1/chat' => Http::response([
                'session_id' => 'sess-usage',
                'message' => 'ok',
                'status' => 'ok',
            ]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api-operator.chat'), [
                'message' => 'list workspaces',
                'workspace_id' => 'agentws',
            ])
            ->assertOk();

        $record = UsageRecord::query()
            ->where('tenant_id', 'agentws')
            ->where('meter', 'agent_calls')
            ->first();

        $this->assertNotNull($record);
        $this->assertSame(1, (int) $record->quantity);
    }

    public function test_chat_uses_configured_billing_workspace_when_omitted(): void
    {
        config([
            'api_operator.enabled' => true,
            'api_operator.url' => 'http://api-operator.test',
            'api_operator.token' => 'test-token',
            'api_operator.billing_workspace_id' => 'billdemo',
            'usage.enabled' => true,
        ]);

        Tenant::withoutEvents(function (): void {
            $tenant = Tenant::query()->create(['id' => 'billdemo', 'name' => 'Bill Demo']);
            $tenant->domains()->create(['domain' => 'billdemo']);
        });

        Http::fake([
            'http://api-operator.test/health' => Http::response(['status' => 'ok']),
            'http://api-operator.test/v1/chat' => Http::response([
                'session_id' => 'sess-cfg',
                'message' => 'ok',
                'status' => 'ok',
            ]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api-operator.chat'), ['message' => 'hello'])
            ->assertOk();

        $this->assertSame(1, (int) UsageRecord::query()
            ->where('tenant_id', 'billdemo')
            ->where('meter', 'agent_calls')
            ->value('quantity'));
    }

    public function test_chat_skips_agent_calls_without_billing_workspace(): void
    {
        config([
            'api_operator.enabled' => true,
            'api_operator.url' => 'http://api-operator.test',
            'api_operator.token' => 'test-token',
            'api_operator.billing_workspace_id' => null,
            'usage.enabled' => true,
        ]);

        Http::fake([
            'http://api-operator.test/health' => Http::response(['status' => 'ok']),
            'http://api-operator.test/v1/chat' => Http::response([
                'session_id' => 'sess-skip',
                'message' => 'ok',
                'status' => 'ok',
            ]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api-operator.chat'), ['message' => 'hello'])
            ->assertOk();

        $this->assertSame(0, UsageRecord::query()->where('meter', 'agent_calls')->count());
    }

    public function test_failed_chat_does_not_increment_agent_calls(): void
    {
        config([
            'api_operator.enabled' => true,
            'api_operator.url' => 'http://127.0.0.1:1',
            'api_operator.token' => 'test-token',
            'api_operator.billing_workspace_id' => 'failws',
            'usage.enabled' => true,
        ]);

        Tenant::withoutEvents(function (): void {
            $tenant = Tenant::query()->create(['id' => 'failws', 'name' => 'Fail WS']);
            $tenant->domains()->create(['domain' => 'failws']);
        });

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api-operator.chat'), ['message' => 'hello'])
            ->assertStatus(503);

        $this->assertSame(0, UsageRecord::query()->where('meter', 'agent_calls')->count());
    }
}
