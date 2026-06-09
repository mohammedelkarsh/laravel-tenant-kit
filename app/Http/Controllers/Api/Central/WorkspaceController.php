<?php

namespace App\Http\Controllers\Api\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantRequest;
use App\Models\Tenant;
use App\Services\TenantProvisioner;
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
            ->map(fn (Tenant $tenant): array => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'url' => $tenant->url(),
                'domains' => $tenant->domains->pluck('domain'),
                'created_at' => $tenant->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $workspaces]);
    }

    public function show(Tenant $tenant): JsonResponse
    {
        $tenant->load('domains');

        return response()->json([
            'data' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'url' => $tenant->url(),
                'domains' => $tenant->domains->pluck('domain'),
                'subscribed' => $tenant->subscribed('default'),
                'created_at' => $tenant->created_at?->toIso8601String(),
            ],
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
}
