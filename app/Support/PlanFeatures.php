<?php

namespace App\Support;

use App\Models\Tenant;

class PlanFeatures
{
    public static function enabled(): bool
    {
        return (bool) config('plan_gating.enabled');
    }

    public static function allows(?Tenant $tenant, string $feature): bool
    {
        if (! self::enabled()) {
            return true;
        }

        return in_array($feature, self::featuresFor($tenant), true);
    }

    /**
     * @return list<string>
     */
    public static function featuresFor(?Tenant $tenant): array
    {
        if (! self::enabled()) {
            return self::allFeatures();
        }

        $planKey = self::planKeyFor($tenant);
        $features = config("plans.{$planKey}.features", []);

        return array_values(array_unique(array_map('strval', $features)));
    }

    /**
     * @return list<string>
     */
    public static function allFeatures(): array
    {
        $features = [];

        foreach (config('plans', []) as $plan) {
            foreach ($plan['features'] ?? [] as $feature) {
                $features[] = (string) $feature;
            }
        }

        return array_values(array_unique($features));
    }

    public static function planKeyFor(?Tenant $tenant): string
    {
        $default = (string) config('plan_gating.default_plan', 'free');

        if ($tenant === null) {
            return $default;
        }

        if (! $tenant->subscribed('default')) {
            return $default;
        }

        $stripePrice = $tenant->subscription('default')?->items->first()?->stripe_price;

        if (! filled($stripePrice)) {
            return $default;
        }

        foreach (config('plans', []) as $key => $plan) {
            if (($plan['stripe_price'] ?? null) === $stripePrice) {
                return (string) $key;
            }
        }

        return $default;
    }
}
