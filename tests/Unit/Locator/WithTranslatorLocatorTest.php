<?php declare(strict_types=1);

namespace Tests\Unit\Locator;

use Berry\Symfony\Locator\Trait\WithTranslatorLocator;
use Symfony\Contracts\Translation\TranslatorInterface;

class WithTranslatorLocatorImplementation
{
    use WithTranslatorLocator;

    public function setTranslatorLocator(?TranslatorInterface $locator): void
    {
        $this->translatorLocator = $locator;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function testTrans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $this->trans($id, $parameters, $domain, $locale);
    }
}

test('trans uses injected locator', function () {
    $stub = new class implements TranslatorInterface {
        /**
         * @param array<string, mixed> $parameters
         */
        public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
        {
            return 'translated:' . $id;
        }
        public function getLocale(): string { return 'en'; }
    };

    $impl = new WithTranslatorLocatorImplementation();
    $impl->setTranslatorLocator($stub);

    $result = $impl->testTrans('test.key');

    expect($result)->toBe('translated:test.key');
});
