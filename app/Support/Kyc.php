<?php

namespace App\Support;

class Kyc
{
    public static function enabled(): bool
    {
        return (bool) config('kyc.enabled');
    }

    public static function packageInstalled(): bool
    {
        return class_exists(\KycAi\Laravel\Kyc::class);
    }

    /**
     * Feature flag on, package available, and (when plan gating is on) plan allows `kyc`.
     */
    public static function ready(): bool
    {
        if (! self::enabled() || ! self::packageInstalled()) {
            return false;
        }

        if (! PlanFeatures::enabled()) {
            return true;
        }

        // Panel providers boot without tenancy; enforce plan only in tenant context.
        if (! tenancy()->initialized) {
            return true;
        }

        $tenant = tenant();

        return $tenant instanceof \App\Models\Tenant
            && PlanFeatures::allows($tenant, 'kyc');
    }
}
