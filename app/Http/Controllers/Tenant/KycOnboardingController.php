<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\TenantKycService;
use App\Support\Kyc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KycOnboardingController extends Controller
{
    public function create(): View
    {
        abort_unless(Kyc::ready(), 404);

        $settings = tenant()->kycSettings();

        return view('tenant.kyc.onboarding', [
            'tenantName' => tenant('name'),
            'country' => $settings['country'],
            'level' => $settings['level'],
            'queue' => request()->boolean('queue'),
        ]);
    }

    public function store(Request $request, TenantKycService $kyc): RedirectResponse|View
    {
        abort_unless(Kyc::ready(), 404);

        $validated = $request->validate([
            'document' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf'],
            'queue' => ['sometimes', 'boolean'],
        ]);

        $queue = $request->boolean('queue');

        $outcome = $kyc->submit(
            $validated['document'],
            $request->user(),
            $queue,
        );

        if ($queue) {
            return redirect()
                ->route('tenant.kyc.onboarding')
                ->with('status', __('app.kyc.queued'));
        }

        return view('tenant.kyc.result', [
            'tenantName' => tenant('name'),
            'result' => $outcome->toArray(),
            'message' => $outcome->userMessage(),
            'reviewUrl' => url('/workspace/kyc-verifications'),
        ]);
    }
}
