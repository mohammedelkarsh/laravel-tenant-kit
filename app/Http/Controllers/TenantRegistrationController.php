<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTenantRequest;
use App\Services\TenantProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TenantRegistrationController extends Controller
{
    public function create(): View
    {
        return view('tenants.register', [
            'centralDomain' => config('app.central_domain'),
        ]);
    }

    public function store(StoreTenantRequest $request, TenantProvisioner $provisioner): RedirectResponse
    {
        $result = $provisioner->provision(
            subdomain: strtolower($request->validated('subdomain')),
            name: $request->validated('name'),
        );

        return redirect()
            ->away($result['url'])
            ->with('status', __('app.workspace.created'));
    }
}
