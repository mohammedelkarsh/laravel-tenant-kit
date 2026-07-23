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
     * Feature flag on and kyc-ai/laravel available.
     */
    public static function ready(): bool
    {
        return self::enabled() && self::packageInstalled();
    }
}
