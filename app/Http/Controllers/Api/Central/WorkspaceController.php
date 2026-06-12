<?php

namespace App\Http\Controllers\Api\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantRequest;
use App\Models\Tenant;
use App\Services\TenantProvisioner;
use App\Support\SubscriptionPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function index(): JsonResponse
    {
        $workspaces = Tenant::query()
            ->with('domains')
            ->orderBy('name')
            ->get()
            ->map(fn (Tenant $tenant): array => $this->workspacePayload($tenant));

        return response()->json(['data' => $workspaces]);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load('domains');

        return response()->json([
            'data' => $this->workspacePayload($tenant),
        ]);
    }

    public function subscription(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => array_merge(
                ['workspace_id' => $tenant->id],
                SubscriptionPresenter::forTenant($tenant),
            ),
        ]);
    }

    public function store(StoreTenantRequest $request, TenantProvisioner $provisioner): JsonResponse
    {
        $result = $provisioner->provision(
            subdomain: strtolower($request->validated('subdomain')),
            name: $request->validated('name'),
        );

        $tenant = $result['tenant'];

        return response()->json([
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'url' => $result['url'],
            ],
        ], 201);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->only(['id', 'name', 'email']),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function workspacePayload(Tenant $tenant): array
    {
        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'url' => $tenant->url(),
            'domains' => $tenant->domains->pluck('domain'),
            'suspended' => $tenant->isSuspended(),
            'subscribed' => $tenant->subscribed('default'),
            'created_at' => $tenant->created_at?->toIso8601String(),
        ];
    }
}
