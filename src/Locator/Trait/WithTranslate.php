<?php declare(strict_types=1);

namespace Berry\Symfony\Locator\Trait;

use Berry\Symfony\Locator\ComponentServiceLocator;
use Symfony\Contracts\Translation\TranslatorInterface;

trait WithTranslate
{
    protected ?TranslatorInterface $translator = null;

    /**
     * @param array<string, mixed> $parameters
     */
    protected function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return ($this->translator ?? ComponentServiceLocator::getTranslator())->trans($id, $parameters, $domain, $locale);
    }
}
