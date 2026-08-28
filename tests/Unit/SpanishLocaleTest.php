<?php

namespace Tests\Unit;

use Tests\TestCase;

class SpanishLocaleTest extends TestCase
{
    public function test_spanish_locale_is_registered_with_ltr_direction(): void
    {
        $this->assertSame([
            'name' => 'Spanish',
            'native' => 'Español',
            'dir' => 'ltr',
        ], config('locales.definitions.es'));
    }

    public function test_spanish_translations_match_english_keys_and_placeholders(): void
    {
        $english = $this->flatten(require lang_path('en/app.php'));
        $spanish = $this->flatten(require lang_path('es/app.php'));

        $this->assertSame(array_keys($english), array_keys($spanish));

        foreach ($english as $key => $value) {
            if (! is_string($value)) {
                continue;
            }

            $this->assertSame(
                $this->placeholders($value),
                $this->placeholders($spanish[$key]),
                "Translation placeholders differ for [{$key}].",
            );
        }
    }

    private function flatten(array $translations, string $prefix = ''): array
    {
        $flattened = [];

        foreach ($translations as $key => $value) {
            $path = $prefix === '' ? (string) $key : "{$prefix}.{$key}";

            if (is_array($value)) {
                $flattened += $this->flatten($value, $path);
            } else {
                $flattened[$path] = $value;
            }
        }

        return $flattened;
    }

    private function placeholders(string $translation): array
    {
        preg_match_all('/:[A-Za-z_][A-Za-z0-9_]*/', $translation, $matches);

        $placeholders = array_values(array_unique($matches[0]));
        sort($placeholders);

        return $placeholders;
    }
}
