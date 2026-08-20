<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\ApiOperatorClient;
use App\Services\UsageMeter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiOperatorChatController extends Controller
{
    public function status(ApiOperatorClient $client): JsonResponse
    {
        if (! config('api_operator.enabled')) {
            return response()->json(['enabled' => false, 'healthy' => false]);
        }

        return response()->json([
            'enabled' => true,
            'healthy' => $client->isHealthy(),
        ]);
    }

    public function store(Request $request, ApiOperatorClient $client, UsageMeter $usageMeter): JsonResponse
    {
        if (! config('api_operator.enabled')) {
            return response()->json(['message' => __('app.api_operator.disabled')], 503);
        }

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'session_id' => ['nullable', 'string', 'max:64'],
            'workspace_id' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            $response = $client->chat(
                message: $data['message'],
                sessionId: $data['session_id'] ?? null,
                user: $request->user(),
            );
        } catch (ConnectionException) {
            return response()->json(['message' => __('app.api_operator.unavailable')], 503);
        } catch (\Illuminate\Http\Client\RequestException $exception) {
            $detail = $exception->response?->json('detail') ?? $exception->getMessage();

            return response()->json(['message' => (string) $detail], 502);
        }

        $this->recordAgentCall($request, $usageMeter, $data['workspace_id'] ?? null);

        return response()->json($response);
    }

    private function recordAgentCall(Request $request, UsageMeter $usageMeter, ?string $workspaceId): void
    {
        $tenant = $this->resolveBillingWorkspace($workspaceId);

        if (! $tenant instanceof Tenant) {
            Log::debug('usage.agent_calls_skipped', [
                'reason' => 'no_billing_workspace',
                'workspace_id' => $workspaceId,
                'user_id' => $request->user()?->id,
            ]);

            return;
        }

        $usageMeter->record($tenant, 'agent_calls');
    }

    private function resolveBillingWorkspace(?string $workspaceId): ?Tenant
    {
        $id = $workspaceId ?: config('api_operator.billing_workspace_id');

        if (! is_string($id) || $id === '') {
            return null;
        }

        $tenant = Tenant::query()->find($id);

        return $tenant instanceof Tenant ? $tenant : null;
    }
}
