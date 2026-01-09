<?php declare(strict_types=1);

namespace Berry\Symfony\Locator\Trait;

use Berry\Symfony\Locator\ComponentServiceLocator;
use Berry\Symfony\UX\IconFactoryInterface;
use Berry\Element;
use Symfony\UX\Icons\Exception\IconNotFoundException;

trait WithRenderIcon
{
    protected ?IconFactoryInterface $iconFactory = null;

    /**
     * @param array<string, string|bool> $attributes an array of HTML attributes
     *
     * @throws IconNotFoundException
     */
    protected function renderIcon(string $name, array $attributes = []): Element
    {
        return ($this->iconFactory ?? ComponentServiceLocator::getIconFactory())->render($name, $attributes);
    }
}
