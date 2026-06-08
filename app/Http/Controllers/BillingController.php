<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function show(Tenant $tenant): View
    {
        return view('billing.show', [
            'tenant' => $tenant,
            'plans' => config('plans'),
            'subscription' => $tenant->subscription('default'),
            'stripeConfigured' => filled(config('cashier.key')),
        ]);
    }

    public function checkout(Request $request, Tenant $tenant, string $plan): RedirectResponse
    {
        abort_unless(filled(config('cashier.key')), 503, __('app.billing.stripe_not_configured_error'));

        $priceId = config("plans.{$plan}.stripe_price");

        abort_unless(filled($priceId), 404);

        return $tenant
            ->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('billing.show', $tenant).'?checkout=success',
                'cancel_url' => route('billing.show', $tenant).'?checkout=cancelled',
            ])
            ->redirect();
    }

    public function portal(Tenant $tenant): RedirectResponse
    {
        abort_unless(filled(config('cashier.key')), 503, __('app.billing.stripe_not_configured_error'));

        return $tenant->redirectToBillingPortal(route('billing.show', $tenant));
    }
}
