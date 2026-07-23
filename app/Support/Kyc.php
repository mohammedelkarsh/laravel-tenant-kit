<?php

namespace App\Support;

class Kyc
{
    public static function enabled(): bool
    {
        return (bool) config('kyc.enabled');
    }
}
