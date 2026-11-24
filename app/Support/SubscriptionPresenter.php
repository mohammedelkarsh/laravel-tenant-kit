<?php

namespace App\Support;

use App\Models\Tenant;

class SubscriptionPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function forTenant(Tenant $tenant): array
    {
        $subscription = $tenant->subscription('default');
        $stripePrice = $subscription?->items->first()?->stripe_price;
        $resolved = self::resolvePlanKey($stripePrice);
        $planKey = PlanFeatures::enabled()
            ? PlanFeatures::planKeyFor($tenant)
            : $resolved;

        return [
            'subscribed' => $tenant->subscribed('default'),
            'status' => $subscription?->stripe_status,
            'plan' => $planKey,
            'plan_name' => $planKey ? (config("plans.{$planKey}.name") ?? null) : null,
            'stripe_price' => $stripePrice,
            'on_trial' => $subscription?->onTrial() ?? false,
            'cancelled' => $subscription?->cancelled() ?? false,
            'ends_at' => $subscription?->ends_at?->toIso8601String(),
            'trial_ends_at' => $subscription?->trial_ends_at?->toIso8601String(),
            'features' => PlanFeatures::featuresFor($tenant),
            'plan_gating' => PlanFeatures::enabled(),
        ];
    }

    private static function resolvePlanKey(?string $stripePrice): ?string
    {
        if (! filled($stripePrice)) {
            return null;
        }

        foreach (config('plans', []) as $key => $plan) {
            if (($plan['stripe_price'] ?? null) === $stripePrice) {
                return $key;
            }
        }

        return null;
    }

}
