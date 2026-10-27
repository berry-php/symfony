<?php

declare(strict_types=1);

namespace Berry\Symfony\Locator\Trait;

use Berry\Element;
use Berry\Symfony\Locator\ComponentServiceLocator;
use Symfony\Component\AssetMapper\ImportMap\ImportMapRenderer;

use function Berry\unsafeRawText;

trait WithImportMapLocator
{
    protected ?ImportMapRenderer $importMapRendererLocator = null;

    /**
     * @param string|string[] $entryPoint
     * @param array<string, mixed> $attributes
     */
    protected function importmap(string|array $entryPoint = 'app', array $attributes = []): Element
    {
        return unsafeRawText(($this->importMapRendererLocator ?? ComponentServiceLocator::getImportMapRenderer())->render(
            $entryPoint,
            $attributes,
        ));
    }
}
