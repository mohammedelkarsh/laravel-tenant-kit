<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class ApiAbilities
{
    /**
     * @return list<string>
     */
    public static function central(): array
    {
        return config('api.central_abilities', []);
    }

    /**
     * @return list<string>
     */
    public static function tenant(): array
    {
        return config('api.tenant_abilities', []);
    }

    /**
     * @param  list<string>|null  $requested
     * @return list<string>
     */
    public static function resolve(?array $requested, string $context): array
    {
        $allowed = $context === 'tenant' ? self::tenant() : self::central();

        if ($requested === null || $requested === []) {
            return $allowed;
        }

        return array_values(array_intersect($requested, $allowed));
    }

    /**
     * @return list<\Illuminate\Contracts\Validation\ValidationRule|string>
     */
    public static function validationRules(string $context): array
    {
        $allowed = $context === 'tenant' ? self::tenant() : self::central();

        return [
            'abilities' => ['sometimes', 'array'],
            'abilities.*' => ['string', Rule::in($allowed)],
        ];
    }
}
