<?php

declare(strict_types=1);

namespace Berry\Symfony\Locator\Trait;

use Berry\Symfony\Locator\ComponentServiceLocator;
use Symfony\Contracts\Translation\TranslatorInterface;

trait WithTranslatorLocator
{
    protected ?TranslatorInterface $translatorLocator = null;

    /**
     * @param array<string, mixed> $parameters
     */
    protected function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return ($this->translatorLocator ?? ComponentServiceLocator::getTranslator())->trans(
            $id,
            $parameters,
            $domain,
            $locale,
        );
    }
}
