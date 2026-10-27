<?php

declare(strict_types=1);

namespace Berry\Symfony\Locator\Trait;

use Berry\Symfony\Locator\ComponentServiceLocator;
use Berry\Element;
use Twig\Environment;

use function Berry\unsafeRawText;

trait WithTwigRendering
{
    protected ?Environment $twigEnvironmentLocator = null;

    /**
     * @param array<string, mixed> $params
     */
    protected function twig(string $path, array $params = []): Element
    {
        $template = ($this->twigEnvironmentLocator ?? ComponentServiceLocator::getTwigEnvironment())->load($path);
        return unsafeRawText($template->render($params));
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function twigBlock(string $path, string $block, array $params = []): Element
    {
        $template = ($this->twigEnvironmentLocator ?? ComponentServiceLocator::getTwigEnvironment())->load($path);
        return unsafeRawText($template->renderBlock($block, $params));
    }
}
