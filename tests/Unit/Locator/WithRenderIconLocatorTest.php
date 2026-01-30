<?php declare(strict_types=1);

namespace Tests\Unit\Locator;

use Berry\Element;
use Berry\Symfony\Locator\Trait\WithRenderIconLocator;
use Berry\Symfony\UX\IconFactoryInterface;

class WithRenderIconLocatorImplementation
{
    use WithRenderIconLocator;

    public function setIconFactoryLocator(?IconFactoryInterface $locator): void
    {
        $this->iconFactoryLocator = $locator;
    }

    /**
     * @param array<string, bool|string> $attributes
     */
    public function testRenderIcon(string $name, array $attributes = []): Element
    {
        return $this->renderIcon($name, $attributes);
    }
}

test('renderIcon uses injected locator', function () {
    $stub = new class implements IconFactoryInterface {
        /**
         * @param array<string, bool|string> $attributes
         */
        public function render(string $name, array $attributes = []): Element
        {
            return new class implements Element {
                public function toString(): string { return '<svg>icon</svg>'; }
                public function __toString(): string { return $this->toString(); }
            };
        }
    };

    $impl = new WithRenderIconLocatorImplementation();
    $impl->setIconFactoryLocator($stub);

    $element = $impl->testRenderIcon('check');

    expect($element->toString())->toBe('<svg>icon</svg>');
});
