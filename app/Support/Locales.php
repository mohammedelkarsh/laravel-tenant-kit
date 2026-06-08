<?php

namespace App\Support;

class Locales
{
    public static function definitions(): array
    {
        return config('locales.definitions', []);
    }

    /** @return list<string> */
    public static function enabled(): array
    {
        $enabled = config('locales.enabled', ['en']);

        return array_values(array_filter(
            $enabled,
            fn (string $code): bool => isset(static::definitions()[$code])
        ));
    }

    public static function isEnabled(string $locale): bool
    {
        return in_array($locale, static::enabled(), true);
    }

    public static function resolve(?string $locale = null): string
    {
        $locale ??= config('app.locale');
        $enabled = static::enabled();

        if (static::isEnabled($locale)) {
            return $locale;
        }

        if (static::isEnabled(config('app.locale'))) {
            return config('app.locale');
        }

        return $enabled[0] ?? 'en';
    }

    public static function direction(?string $locale = null): string
    {
        $locale = static::resolve($locale);

        return static::definitions()[$locale]['dir'] ?? 'ltr';
    }

    public static function native(string $locale): string
    {
        return static::definitions()[$locale]['native'] ?? $locale;
    }
}
