<?php declare(strict_types=1);

namespace Berry\Symfony\UX;

use Berry\Element;
use Berry\UnsafeRawText;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\IconRendererInterface;

final class IconFactory implements IconFactoryInterface
{
    public function __construct(
        private IconRendererInterface $iconRenderer,
    ) {}

    /**
     * @param array<string, string|bool> $attributes an array of HTML attributes
     *
     * @throws IconNotFoundException
     */
    public function render(string $name, array $attributes = []): Element
    {
        return new UnsafeRawText(
            $this->iconRenderer->renderIcon($name, $attributes)
        );
    }
}
