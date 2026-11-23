<?php

namespace Tests\Unit;

use App\Support\Locales;
use Tests\TestCase;

class LocalesTest extends TestCase
{
    public function test_french_locale_is_defined(): void
    {
        $this->assertArrayHasKey('fr', Locales::definitions());
        $this->assertSame('ltr', Locales::definitions()['fr']['dir']);
        $this->assertSame('Français', Locales::native('fr'));
    }

    public function test_french_can_be_enabled(): void
    {
        config([
            'locales.enabled' => ['en', 'ar', 'fr'],
        ]);

        $this->assertTrue(Locales::isEnabled('fr'));
        $this->assertContains('fr', Locales::enabled());
    }

    public function test_french_translation_file_exists(): void
    {
        $path = lang_path('fr/app.php');

        $this->assertFileExists($path);

        /** @var array<string, mixed> $strings */
        $strings = require $path;

        $this->assertSame('Tableau de bord', data_get($strings, 'nav.dashboard'));
        $this->assertSame('Assistant Tenant', data_get($strings, 'api_operator.title'));
    }
}
