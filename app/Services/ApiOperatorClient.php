<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ApiOperatorClient
{
    /**
     * @return array{
     *     session_id: string,
     *     message: string,
     *     status: string,
     *     tool?: string|null,
     *     tool_result?: array<string, mixed>|null,
     *     metadata?: array<string, mixed>,
     *     dry_run_report?: array<string, mixed>|null
     * }
     */
    public function chat(
        string $message,
        ?string $sessionId,
        User $user,
        bool $dryRun = false,
        ?string $workspaceId = null,
    ): array {
        $payload = [
            'message' => $message,
            'session_id' => $sessionId,
            'adapter' => 'yaml',
            'config_path' => config('api_operator.adapter_path'),
            'abilities' => config('api_operator.abilities'),
            'adapter_config' => $this->adapterConfig($user),
            'dry_run' => $dryRun,
            'actor_id' => (string) $user->getAuthIdentifier(),
        ];

        if ($workspaceId) {
            $payload['tenant_id'] = $workspaceId;
        }

        $policyPath = config('api_operator.policy_path');
        if (is_string($policyPath) && $policyPath !== '') {
            $payload['policy_path'] = $policyPath;
        }

        $response = Http::timeout(config('api_operator.timeout'))
            ->post($this->baseUrl().'/v1/chat', $payload);

        $response->throw();

        /** @var array{session_id: string, message: string, status: string, tool?: string|null, tool_result?: array<string, mixed>|null} $body */
        $body = $response->json();

        return $body;
    }

    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl().'/health');

            return $response->successful();
        } catch (ConnectionException) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function adapterConfig(User $user): array
    {
        $config = [
            'token' => $this->resolveToken($user),
            'base_url' => rtrim(
                (string) (config('api_operator.tenant_kit_base_url') ?: config('app.url')),
                '/',
            ),
        ];

        if ($connectHost = config('api_operator.connect_host')) {
            $config['connect_host'] = $connectHost;
        }

        return $config;
    }

    private function resolveToken(User $user): string
    {
        $configured = config('api_operator.token');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $cached = session('api_operator_sanctum_token');

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $token = $user->createToken('api-operator-chat', config('api_operator.abilities'))->plainTextToken;
        session(['api_operator_sanctum_token' => $token]);

        return $token;
    }

    private function baseUrl(): string
    {
        $url = config('api_operator.url');

        if (! is_string($url) || $url === '') {
            throw new RuntimeException('API_OPERATOR_URL is not configured.');
        }

        return rtrim($url, '/');
    }
}
