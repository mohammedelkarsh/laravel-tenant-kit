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

        return [
            'subscribed' => $tenant->subscribed('default'),
            'status' => $subscription?->stripe_status,
            'plan' => self::resolvePlanKey($stripePrice),
            'plan_name' => self::resolvePlanName($stripePrice),
            'stripe_price' => $stripePrice,
            'on_trial' => $subscription?->onTrial() ?? false,
            'cancelled' => $subscription?->cancelled() ?? false,
            'ends_at' => $subscription?->ends_at?->toIso8601String(),
            'trial_ends_at' => $subscription?->trial_ends_at?->toIso8601String(),
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

    private static function resolvePlanName(?string $stripePrice): ?string
    {
        $key = self::resolvePlanKey($stripePrice);

        return $key ? (config("plans.{$key}.name") ?? null) : null;
    }
}
